<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Services\Menu\MenuCatalog;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SettingSeeder::class);
    }

    public function test_shows_menu_grouped_by_category(): void
    {
        $drinks = Category::factory()->create(['name' => 'Đồ uống', 'slug' => 'do-uong']);
        MenuItem::factory()->for($drinks)->create(['name' => 'Trà đá', 'price' => 5000, 'description' => 'Mát lạnh']);
        MenuItem::factory()->for($drinks)->soldOut()->create(['name' => 'Nước dừa']);
        MenuItem::factory()->for($drinks)->hidden()->create(['name' => 'Món ẩn']);
        MenuItem::factory()->for(Category::factory()->state(['is_active' => false]))->create(['name' => 'Danh mục tắt']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Đồ uống')
            ->assertSee('id="danh-muc-do-uong"', false)
            ->assertSee('Trà đá')
            ->assertSee('5.000 ₫')
            ->assertSee('Mát lạnh')
            ->assertSee('Nước dừa')
            ->assertSee('Tạm hết')
            ->assertDontSee('Món ẩn')
            ->assertDontSee('Danh mục tắt');
    }

    public function test_featured_section(): void
    {
        MenuItem::factory()->create(['name' => 'Phở đặc biệt', 'is_featured' => true]);

        $this->get('/')->assertSee('Món nổi bật')->assertSee('Phở đặc biệt');

        Setting::set('home.show_featured', false);

        $this->get('/')->assertDontSee('Món nổi bật');
    }

    public function test_empty_menu(): void
    {
        $this->get('/')->assertOk()->assertSee('Thực đơn đang được cập nhật');
    }

    public function test_menu_cache_is_refreshed_when_menu_changes(): void
    {
        $item = MenuItem::factory()->create(['name' => 'Bún chả', 'price' => 50000]);
        $catalog = app(MenuCatalog::class);

        $this->assertSame(50000, $catalog->categories()->first()->menuItems->first()->price);
        $cached = $catalog->categories()->first()->menuItems->first();
        $this->assertTrue($cached->is($item), 'Model đọc lại từ cache vẫn đúng');
        $this->assertTrue($cached->is_available);

        $item->update(['price' => 55000]);
        $this->assertSame(55000, $catalog->categories()->first()->menuItems->first()->price);

        $item->category->update(['is_active' => false]);
        $this->assertCount(0, $catalog->categories());
    }

    public function test_branding_seo_and_contact(): void
    {
        Setting::set('restaurant.name', 'Quán Ngon');
        Setting::set('seo.title', 'Quán Ngon - Món Việt Quận 1');
        Setting::set('seo.description', 'Phở, bún chả ngon nhất Quận 1');
        Setting::set('seo.keywords', ['phở', 'bún chả']);
        Setting::set('brand.color', '#1d4ed8');
        Setting::set('brand.logo', 'branding/logo.png');
        Setting::set('seo.google_analytics_id', 'G-ABC123XYZ');
        Setting::set('home.hero_title', 'Hương vị Hà Nội');
        Setting::set('home.cta_text', 'Đặt bàn qua Zalo');
        Setting::set('home.cta_url', 'https://zalo.me/0901234567');
        Setting::set('home.menu_note', 'Giá đã bao gồm VAT');
        Setting::set('home.about_text', "Mở cửa từ 1998.\nGia truyền ba đời.");
        Setting::set('restaurant.phone', '0901 234 567');
        Setting::set('social.facebook', 'https://facebook.com/quanngon');

        $this->get('/')
            ->assertOk()
            ->assertSee('<title>Quán Ngon - Món Việt Quận 1</title>', false)
            ->assertSee('<meta name="description" content="Phở, bún chả ngon nhất Quận 1">', false)
            ->assertSee('<meta name="keywords" content="phở, bún chả">', false)
            ->assertSee('<meta name="robots" content="index, follow">', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('--brand: #1d4ed8', false)
            ->assertSee('/storage/branding/logo.png', false)
            ->assertSee('gtag/js?id=G-ABC123XYZ', false)
            ->assertSee('Hương vị Hà Nội')
            ->assertSee('href="https://zalo.me/0901234567"', false)
            ->assertSee('Đặt bàn qua Zalo')
            ->assertSee('Giá đã bao gồm VAT')
            ->assertSee('Gia truyền ba đời.')
            ->assertSee('href="tel:0901234567"', false)
            ->assertSee('https://facebook.com/quanngon', false)
            ->assertSee('"@type":"Restaurant"', false);
    }

    public function test_default_color_is_not_overridden(): void
    {
        $this->get('/')->assertDontSee('--brand:', false);
    }

    public function test_robots_sitemap_and_indexing_toggle(): void
    {
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap:');
        $this->get('/sitemap.xml')->assertOk()->assertSee('<loc>', false);

        Setting::set('seo.allow_indexing', false);

        $this->get('/')->assertSee('noindex, nofollow', false);
        $this->get('/robots.txt')->assertSee("Disallow: /\n", false);
    }

    public function test_vietnamese_error_page(): void
    {
        $this->get('/khong-ton-tai')->assertNotFound()->assertSee('Không tìm thấy trang');
    }
}
