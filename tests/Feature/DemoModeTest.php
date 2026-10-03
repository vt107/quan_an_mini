<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\EditMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Providers\DemoServiceProvider;
use App\Support\Demo\DemoMode;
use App\Support\Demo\DemoModeException;
use Database\Seeders\DemoSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\UserSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Chế độ demo chỉ xem (config/demo.php). PHPUnit chạy CLI nên phải forceGuard() để guard SQL hoạt động.
 */
class DemoModeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([SettingSeeder::class, UserSeeder::class]);
        $this->admin = User::firstWhere('email', 'admin@quanan.test');

        // Provider đã boot khi DEMO_MODE=false: bật config rồi đăng ký lại để gắn guard / middleware / route.
        config(['demo.enabled' => true]);
        $this->app->register(DemoServiceProvider::class, force: true);
        Route::getRoutes()->refreshNameLookups();
        DemoMode::forceGuard();
    }

    protected function tearDown(): void
    {
        DemoMode::forceGuard(false);

        parent::tearDown();
    }

    public function test_sql_writes_are_blocked_except_infrastructure_and_login(): void
    {
        $this->assertSame(1, User::count(), 'Đọc vẫn được');

        try {
            Category::create(['name' => 'Thử', 'slug' => 'thu']);
            $this->fail('Lệnh ghi phải bị chặn');
        } catch (DemoModeException) {
            $this->assertSame(0, Category::count());
        }

        DemoMode::guardQuery('insert into `sessions` (`id`, `payload`) values (?, ?)');
        DemoMode::guardQuery('update `users` set `remember_token` = ? where `id` = ?');
        DemoMode::guardQuery('update `users` set `last_login_at` = ?, `users`.`updated_at` = ? where `id` = ?');

        $this->expectException(DemoModeException::class);
        DemoMode::guardQuery('update `users` set `last_login_at` = ?, `password` = ? where `id` = ?');
    }

    public function test_bypass_allows_writes(): void
    {
        DemoMode::bypass(fn () => Category::create(['name' => 'Thử', 'slug' => 'thu']));

        $this->assertSame(1, Category::count());
    }

    public function test_post_forms_are_blocked_before_controller(): void
    {
        $called = false;
        Route::post('/_demo-test', function () use (&$called) {
            $called = true;

            return 'ok';
        })->middleware('web');

        $this->from('/')->post('/_demo-test')
            ->assertRedirect('/')
            ->assertSessionHas('demo_blocked');
        $this->postJson('/_demo-test')->assertForbidden()->assertJsonPath('message', DemoMode::message());
        $this->assertFalse($called);

        $this->post(route('livewire.upload-file'))->assertSessionHas('demo_blocked');
    }

    public function test_filament_save_delete_bulk_and_settings_are_blocked(): void
    {
        $this->actingAs($this->admin);
        $item = DemoMode::bypass(fn () => MenuItem::factory()->create(['price' => 50000]));

        Livewire::test(EditMenuItem::class, ['record' => $item->getRouteKey()])
            ->fillForm(['price' => 99000])
            ->call('save')
            ->assertDispatched('demo-blocked');

        Livewire::test(ListMenuItems::class)
            ->callAction(TestAction::make('delete')->table($item))
            ->assertDispatched('demo-blocked');

        Livewire::test(ListMenuItems::class)
            ->selectTableRecords([$item->id])
            ->callAction(TestAction::make('markSoldOut')->table()->bulk())
            ->assertDispatched('demo-blocked');

        Livewire::test(ManageSettings::class)
            ->fillForm(['restaurant' => ['name' => 'Quán bị đổi tên']])
            ->call('save')
            ->assertDispatched('demo-blocked');

        $item->refresh();
        $this->assertSame(50000, $item->price);
        $this->assertTrue($item->is_available);
        $this->assertFalse($item->trashed());
        $this->assertSame('Quán Ăn Mini', Setting::get('restaurant.name'));
    }

    public function test_file_upload_is_stopped_before_upload_url(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(CreateMenuItem::class)
            ->call('_startUpload', 'data.image.abc', [['name' => 'pho.jpg', 'size' => 1000, 'type' => 'image/jpeg']], false)
            ->assertDispatched('upload:errored')
            ->assertDispatched('demo-blocked')
            ->assertNotDispatched('upload:generatedSignedUrl');
    }

    public function test_widget_is_injected_into_site_and_filament_pages(): void
    {
        $this->get('/')->assertOk()->assertSee('id="dmw"', false)->assertSee('admin@quanan.test');
        $this->get('/admin/login')->assertOk()->assertSee('id="dmw"', false);
        $this->get('/robots.txt')->assertDontSee('id="dmw"', false);
    }

    public function test_login_is_prefilled_and_works(): void
    {
        $this->get('/admin/login?demo=admin')->assertSee('admin@quanan.test');

        Livewire::test(Login::class)
            ->assertSet('data.email', 'admin@quanan.test')
            ->assertSet('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors()
            ->assertNotDispatched('demo-blocked');

        $this->assertAuthenticatedAs($this->admin);
        $this->assertNotNull($this->admin->fresh()->last_login_at);
    }

    public function test_switch_account_logs_out_and_opens_login(): void
    {
        $this->actingAs($this->admin)
            ->get('/demo/switch/admin')
            ->assertRedirect(url('/admin/login').'?demo=admin');

        $this->assertGuest();
        $this->get('/demo/switch/khong-co')->assertNotFound();
    }

    public function test_demo_seeder_covers_every_state_and_is_rerunnable(): void
    {
        DemoMode::forceGuard(false);
        Storage::fake('public');

        $this->seed(DemoSeeder::class);
        $this->seed(DemoSeeder::class);

        $items = MenuItem::withTrashed()->get();
        $this->assertGreaterThanOrEqual(40, $items->count());
        $this->assertTrue($items->contains('is_active', false));
        $this->assertTrue($items->contains('is_available', false));
        $this->assertTrue($items->contains('is_featured', true));
        $this->assertTrue($items->contains(fn (MenuItem $item) => $item->trashed()));
        $this->assertTrue($items->contains('image', null));
        $this->assertTrue(Category::where('is_active', false)->exists());
        $this->assertTrue(Category::doesntHave('menuItems')->exists());
        Storage::disk('public')->assertExists(Setting::get('brand.logo'));
        Storage::disk('public')->assertExists($items->firstWhere('slug', 'pho-bo-tai-nam')->image);

        $this->get('/')
            ->assertOk()
            ->assertSee('Bếp Nhà Mây')
            ->assertSee('Món nổi bật')
            ->assertSee('Tạm hết')
            ->assertSee('noindex, nofollow', false)
            ->assertDontSee('Bò lúc lắc khoai tây')
            ->assertDontSee('Bánh canh ghẹ')
            ->assertDontSee('Nem nướng Nha Trang');
    }

    public function test_reset_command_refuses_when_demo_is_off(): void
    {
        config(['demo.enabled' => false]);

        $this->artisan('demo:reset', ['--force' => true])->assertFailed();
    }
}
