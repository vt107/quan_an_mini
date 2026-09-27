<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\MenuItems\Pages\CreateMenuItem;
use App\Filament\Resources\MenuItems\Pages\ListMenuItems;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminUrls(): array
    {
        return [
            'tổng quan' => ['/admin'],
            'danh mục' => ['/admin/categories'],
            'món ăn' => ['/admin/menu-items'],
            'thêm món' => ['/admin/menu-items/create'],
            'cài đặt' => ['/admin/settings'],
        ];
    }

    #[DataProvider('adminUrls')]
    public function test_admin_pages_render(string $url): void
    {
        MenuItem::factory()->count(2)->create();

        $this->actingAs(User::factory()->create())->get($url)->assertOk();
    }

    public function test_guests_and_inactive_users_cannot_open_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->inactive()->create())->get('/admin')->assertForbidden();
    }

    public function test_removed_features_are_gone(): void
    {
        foreach (['/menu', '/staff', '/kitchen', '/dat-ban', '/admin/dining-tables', '/admin/users'] as $url) {
            $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
        }
    }

    public function test_create_menu_item_shows_on_landing_page(): void
    {
        $this->actingAs(User::factory()->create());
        $category = Category::factory()->create();

        $this->get('/')->assertDontSee('Phở bò');

        Livewire::test(CreateMenuItem::class)
            ->fillForm([
                'name' => 'Phở bò',
                'slug' => 'pho-bo',
                'category_id' => $category->id,
                'price' => 65000,
                'image' => UploadedFile::fake()->image('pho.jpg'),
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->get('/')->assertSee('Phở bò')->assertSee('65.000 ₫');
    }

    public function test_category_slug_is_generated_from_vietnamese_name(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateCategory::class)
            ->fillForm(['name' => 'Đồ uống'])
            ->assertSchemaStateSet(['slug' => 'do-uong']);
    }

    public function test_sold_out_bulk_action(): void
    {
        $this->actingAs(User::factory()->create());
        $items = MenuItem::factory()->count(2)->create();

        Livewire::test(ListMenuItems::class)
            ->selectTableRecords($items->pluck('id')->all())
            ->callAction(TestAction::make('markSoldOut')->table()->bulk());

        $this->assertSame(0, MenuItem::where('is_available', true)->count());
    }

    public function test_settings_save_with_uploads(): void
    {
        Storage::fake('public');
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)
            ->assertSee('Thương hiệu')
            ->assertDontSee('Chuyển khoản')
            ->fillForm([
                'restaurant' => ['name' => 'Quán Ngon', 'phone' => '0901234567'],
                'brand' => ['color' => '#1d4ed8', 'logo' => UploadedFile::fake()->image('logo.png', 300, 120)],
                'seo' => ['keywords' => ['phở', 'bún chả']],
                'home' => ['cta_text' => 'Đặt bàn', 'cta_url' => 'https://zalo.me/0901234567'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Quán Ngon', Setting::get('restaurant.name'));
        $this->assertSame('0901234567', Setting::get('restaurant.phone'));
        $this->assertSame(['phở', 'bún chả'], Setting::get('seo.keywords'));
        Storage::disk('public')->assertExists(Setting::get('brand.logo'));

        $this->get('/admin')->assertSee('Quán Ngon');
    }

    public function test_settings_validation(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageSettings::class)
            ->fillForm([
                'brand' => ['color' => 'đỏ'],
                'seo' => ['google_analytics_id' => 'UA-123'],
                'home' => ['cta_text' => 'Đặt bàn', 'cta_url' => ''],
            ])
            ->call('save')
            ->assertHasFormErrors(['brand.color', 'seo.google_analytics_id', 'home.cta_url']);
    }
}
