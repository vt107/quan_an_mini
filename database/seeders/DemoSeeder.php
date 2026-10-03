<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Setting;
use App\Models\User;
use App\Services\Menu\MenuCatalog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dữ liệu bản demo (DEMO_MODE=true, gọi từ DatabaseSeeder thay cho MenuSeeder): quán mẫu "Bếp Nhà Mây"
 * với đủ trường hợp hiển thị: món ẩn / tạm hết / nổi bật, danh mục tắt, danh mục chưa có món, món trong thùng rác,
 * món có / chưa có ảnh, cài đặt đủ 4 tab. Ảnh (CC0 / public domain, nguồn ở demo/sources.json) chép từ
 * database/seeders/demo sang storage public. Không dùng factory / Faker (image production cài --no-dev).
 * Chạy lại được: món / danh mục khớp theo slug, ảnh ghi đè.
 */
class DemoSeeder extends Seeder
{
    private const SOURCE_DIRECTORY = __DIR__.'/demo';

    public function run(): void
    {
        $this->seedSettings();
        $this->seedMenu();

        User::where('email', 'admin@quanan.test')->update([
            'name' => 'Trần Thu Mây',
            'last_login_at' => now()->subHours(3),
        ]);
    }

    private function seedSettings(): void
    {
        $settings = [
            'restaurant.name' => 'Bếp Nhà Mây',
            'restaurant.slogan' => 'Cơm nhà, phở sáng, lẩu tối – nấu từ nguyên liệu chợ mỗi sáng',
            'brand.logo' => $this->image('logo.png', 'branding'),
            'brand.favicon' => $this->image('favicon.png', 'branding'),
            'brand.color' => '#c2410c',

            'home.hero_eyebrow' => 'Quán ăn gia đình · Phú Nhuận',
            'home.hero_title' => 'Bữa cơm nhà giữa lòng Sài Gòn',
            'home.hero_subtitle' => 'Phở bò ninh xương 12 tiếng, bún chả nướng than hoa, lẩu nồi đồng cho cả nhà. Mở cửa từ 6:30 sáng đến 22:00.',
            'home.hero_image' => $this->image('hero.jpg', 'branding'),
            'home.cta_text' => 'Đặt bàn qua Zalo',
            'home.cta_url' => 'https://zalo.me/0900000000',
            'home.show_featured' => true,
            'home.menu_note' => 'Giá đã bao gồm VAT. Nhận đặt tiệc 20 – 60 khách, vui lòng báo trước một ngày.',
            'home.about_title' => 'Câu chuyện của Bếp Nhà Mây',
            'home.about_text' => "Bếp Nhà Mây bắt đầu từ gánh phở buổi sáng của mẹ ở đầu hẻm, nay là quán nhỏ 12 bàn cho những ai muốn ăn một bữa cơm đúng vị nhà.\n"
                ."Mỗi sáng bếp đi chợ từ 5 giờ: xương bò ninh cho nồi phở, thịt ba chỉ ướp cho bún chả, rau thơm hái trong ngày. Hết nguyên liệu là hết món, nên thỉnh thoảng bạn sẽ thấy nhãn \"Tạm hết\".\n"
                .'Quán có phòng lạnh trên lầu cho nhóm 20 – 60 khách, nhận nấu cơm trưa văn phòng và đặt tiệc sinh nhật, họp lớp.',

            'restaurant.phone' => '0900 000 000',
            'restaurant.email' => 'datban@bepnhamay.test',
            'restaurant.address' => '123 Hoa Sứ, Phường 7, Quận Phú Nhuận, TP. Hồ Chí Minh',
            'restaurant.opening_hours' => '06:30 - 22:00 (cả tuần)',
            'restaurant.map_url' => 'https://maps.google.com/?q=Ph%C3%BA+Nhu%E1%BA%ADn%2C+H%E1%BB%93+Ch%C3%AD+Minh',
            'social.facebook' => 'https://www.facebook.com/bepnhamay.demo',
            'social.zalo' => '0900000000',
            'social.instagram' => 'https://www.instagram.com/bepnhamay.demo',
            'social.tiktok' => 'https://www.tiktok.com/@bepnhamay.demo',

            'seo.title' => 'Bếp Nhà Mây – Phở, bún chả, lẩu ngon Phú Nhuận',
            'seo.cuisine' => 'Việt Nam',
            'seo.description' => 'Quán ăn gia đình ở Phú Nhuận: phở bò ninh xương 12 tiếng, bún chả than hoa, cơm trưa văn phòng, lẩu nồi đồng. Mở cửa 6:30 – 22:00, đặt bàn qua Zalo.',
            'seo.keywords' => ['phở bò Phú Nhuận', 'bún chả', 'cơm trưa văn phòng', 'lẩu nồi đồng', 'quán ăn gia đình'],
            'seo.og_image' => null,
            // Bản demo là quán giả: không cho Google index (robots.txt "Disallow: /", meta noindex, không nạp GA).
            'seo.allow_indexing' => false,
            'seo.google_analytics_id' => 'G-DEMO2026',
            'seo.google_site_verification' => 'demo-site-verification',
        ];

        foreach ($settings as $name => $value) {
            Setting::set($name, $value);
        }
    }

    private function seedMenu(): void
    {
        // [tên, giá, mô tả, ảnh, cờ]; cờ: featured, sold_out (Tạm hết), hidden (ẩn khỏi web), trashed (đã xóa, số ngày trước)
        $menu = [
            ['Khai vị', 'Món nhẹ mở vị, gọi chung cho cả bàn', true, [
                ['Gỏi cuốn tôm thịt', 45000, 'Tôm, ba chỉ, bún, rau thơm cuốn bánh tráng; chấm tương đậu phộng.', 'goi-cuon-tom-thit.jpg', ['featured']],
                ['Chả giò rế', 55000, 'Nhân thịt heo, khoai môn, nấm mèo; chiên giòn, ăn kèm rau sống.', 'cha-gio-re.jpg'],
                ['Gỏi đu đủ khô bò', 65000, 'Đu đủ bào, khô bò xé, rau răm, đậu phộng rang, nước mắm chua ngọt.', 'goi-du-du-kho-bo.jpg'],
                ['Gỏi ngó sen tôm thịt', 85000, 'Ngó sen giòn trộn tôm, thịt, cà rốt và hành phi.'],
                ['Đậu hũ chiên sả ớt', 45000, 'Đậu hũ non chiên vàng, sả ớt phi thơm.', null, ['sold_out']],
                ['Nem nướng Nha Trang', 65000, 'Nem nướng, bánh tráng chiên, nước chấm tương gan.', null, ['trashed' => 30]],
            ]],
            ['Phở & Bún', 'Nước dùng ninh xương bò 12 tiếng mỗi sáng', true, [
                ['Phở bò tái nạm', 65000, 'Bò tái, nạm gầu, bánh phở tươi; kèm quẩy và rau thơm.', 'pho-bo-tai-nam.jpg', ['featured']],
                ['Phở bò đặc biệt', 85000, 'Tái, nạm, gầu, gân, bò viên; tô lớn cho người ăn khỏe.', 'pho-bo-dac-biet.jpg'],
                ['Bún chả Hà Nội', 60000, 'Chả viên và ba chỉ nướng than hoa, nước chấm chua ngọt, bún rối.', 'bun-cha-ha-noi.jpg', ['featured']],
                ['Bún bò Huế', 65000, 'Giò heo, bò nạm, chả cua; nước dùng sả ruốc cay nồng.', 'bun-bo-hue.jpg'],
                ['Hủ tiếu bò kho', 70000, 'Bò kho mềm với cà rốt, sả, quế hồi; chọn hủ tiếu hoặc bánh mì.', 'hu-tieu-bo-kho.jpg'],
                ['Bún riêu cua', 55000, 'Riêu cua đồng, đậu hũ chiên, cà chua; chỉ bán buổi sáng.', null, ['sold_out']],
            ]],
            ['Cơm', 'Cơm trưa phục vụ 10:30 – 14:00, kèm canh và đồ chua', true, [
                ['Cơm tấm sườn bì chả', 60000, 'Sườn nướng mật ong, bì, chả trứng, mỡ hành.'],
                ['Cơm gà nướng sả', 65000, 'Đùi gà ướp sả nướng, cơm trắng, dưa leo cà chua.', 'com-ga-nuong.jpg'],
                ['Cơm chiên hải sản', 75000, 'Tôm, mực, hạt điều, trứng; chiên lửa lớn tơi hạt.', 'com-chien-hai-san.jpg'],
                ['Cơm chiên gà xối mỡ', 70000, 'Cơm chiên cà chua, gà xối mỡ da giòn.', 'com-chien-ga.jpg'],
                ['Cơm cá kho tộ', 70000, 'Cá basa kho tộ đậm vị tiêu, kèm canh chua.', null, ['sold_out']],
            ]],
            ['Món nướng & xào', 'Gọi thêm cho bữa tối đông người', true, [
                ['Gà nướng mật ong (nửa con)', 180000, 'Gà ta ướp mật ong, tiêu xanh; nướng than, chấm muối ớt chanh.', 'ga-nuong-mat-ong.jpg'],
                ['Mực nướng sa tế', 145000, 'Mực ống tươi nướng sa tế, chấm muối ớt xanh.', 'muc-nuong-sa-te.jpg'],
                ['Bánh xèo miền Tây', 75000, 'Vỏ giòn nghệ vàng, nhân tôm thịt giá; cuốn cải xanh, rau rừng.', 'banh-xeo-mien-tay.jpg', ['featured']],
                ['Tôm xào thập cẩm', 125000, 'Tôm sú xào bông cải, đậu Hà Lan, nấm đông cô.', 'tom-xao-thap-cam.jpg'],
                ['Rau muống xào tỏi', 45000, 'Rau muống non xào lửa lớn với tỏi phi.', 'rau-muong-xao-toi.jpg'],
                ['Bò lúc lắc khoai tây', 165000, 'Thăn bò cắt hạt lựu, khoai tây chiên, salad dầu giấm.', null, ['hidden']],
                ['Bánh mì thịt nướng', 30000, 'Bánh mì giòn, thịt nướng, đồ chua, rau mùi.', 'banh-mi-thit-nuong.jpg', ['trashed' => 12]],
            ]],
            ['Lẩu', 'Nồi cho 2 – 4 người, kèm bún tươi và rau nhúng', true, [
                ['Lẩu bò nhúng mẻ', 340000, 'Bắp bò, gân, nạm nhúng nước mẻ chua thanh; kèm rau muống, bắp chuối.', 'lau-bo-nhung-me.jpg', ['featured']],
                ['Lẩu cá thập cẩm', 320000, 'Cá lóc, cá basa, đậu hũ, rau nhúng; nồi đồng than hồng.', 'lau-ca-thap-cam.jpg'],
                ['Lẩu thái hải sản', 350000, 'Tôm, mực, nghêu, cá; nước lẩu chua cay sả, lá chanh.'],
                ['Lẩu gà lá é', 320000, 'Gà ta, lá é Đà Lạt, măng chua.', null, ['sold_out']],
            ]],
            ['Đồ uống', 'Pha khi gọi, ít đường theo yêu cầu', true, [
                ['Cà phê sữa đá', 30000, 'Cà phê phin rang mộc, sữa đặc, đá viên.', 'ca-phe-sua-da.jpg'],
                ['Bạc xỉu', 32000, 'Nhiều sữa ít cà phê, vị béo nhẹ.', 'bac-xiu.jpg'],
                ['Trà chanh mật ong', 25000, 'Trà xanh, chanh tươi, mật ong.', 'tra-chanh.jpg'],
                ['Trà cam sả', 35000, 'Trà đen ủ lạnh, cam vàng, sả cây.', 'tra-cam-sa.jpg'],
                ['Rau má đậu xanh', 30000, 'Rau má xay, đậu xanh nấu nhừ, nước cốt dừa.', 'rau-ma-dau-xanh.jpg'],
                ['Trà đá', 5000, 'Châm thêm miễn phí.'],
                ['Bia Sài Gòn', 25000, 'Lon 330 ml, ướp lạnh.'],
                ['Nước dừa tươi', 30000, 'Dừa xiêm Bến Tre.', null, ['sold_out']],
            ]],
            ['Tráng miệng', 'Món ngọt làm trong ngày', true, [
                ['Chè thập cẩm', 30000, 'Đậu đỏ, đậu trắng, thạch, khoai môn, nước cốt dừa.', 'che-thap-cam.jpg', ['featured', 'sold_out']],
                ['Chè trôi nước', 25000, 'Viên nếp nhân đậu xanh, nước đường gừng.', 'che-troi-nuoc.jpg'],
                ['Chè bắp', 25000, 'Bắp nếp, nước cốt dừa, bột báng.', 'che-bap.jpg'],
                ['Bánh flan', 20000, 'Trứng gà ta, caramel đắng nhẹ; thêm cà phê theo ý.', 'banh-flan.jpg'],
            ]],
            // Danh mục đang tắt: món bên trong không hiện trên web dù món vẫn bật
            ['Món theo mùa', 'Chỉ bán theo mùa nguyên liệu, bật lại khi có hàng', false, [
                ['Bánh canh ghẹ', 95000, 'Ghẹ biển nguyên con, bánh canh bột gạo.'],
                ['Gỏi cá trích', 120000, 'Cá trích Phú Quốc, dừa nạo, bánh tráng, rau rừng.'],
            ]],
            // Danh mục chưa có món: không hiện trên web, xóa được trong admin
            ['Combo trưa văn phòng', 'Sắp ra mắt: cơm + canh + nước, giao tận nơi.', true, []],
        ];

        foreach ($menu as $categoryOrder => [$categoryName, $categoryDescription, $categoryActive, $items]) {
            $category = Category::updateOrCreate(['slug' => Str::slug($categoryName)], [
                'name' => $categoryName,
                'description' => $categoryDescription,
                'sort_order' => $categoryOrder,
                'is_active' => $categoryActive,
            ]);

            foreach ($items as $itemOrder => $item) {
                [$name, $price, $description] = $item;
                $image = $item[3] ?? null;
                $flags = $item[4] ?? [];

                $menuItem = MenuItem::withTrashed()->updateOrCreate(['slug' => Str::slug($name)], [
                    'category_id' => $category->id,
                    'name' => $name,
                    'price' => $price,
                    'description' => $description,
                    'image' => $image ? $this->image($image, 'menu') : null,
                    'is_featured' => in_array('featured', $flags, true),
                    'is_available' => ! in_array('sold_out', $flags, true),
                    'is_active' => ! in_array('hidden', $flags, true),
                    'sort_order' => $itemOrder,
                ]);

                $menuItem->forceFill([
                    'deleted_at' => isset($flags['trashed']) ? now()->subDays($flags['trashed']) : null,
                    'created_at' => now()->subMonths(4)->addDays($categoryOrder * 3 + $itemOrder),
                    'updated_at' => now()->subDays(($categoryOrder * 5 + $itemOrder * 2) % 21)->subHours($itemOrder),
                ])->saveQuietly();
            }
        }

        // Các lệnh saveQuietly ở trên không gọi model event: xóa cache thực đơn một lần ở cuối.
        MenuCatalog::flush();
    }

    /** Chép ảnh mẫu vào disk public (ghi đè), trả về đường dẫn lưu trong DB như FileUpload. */
    private function image(string $file, string $directory): string
    {
        $path = "{$directory}/demo-{$file}";

        Storage::disk('public')->put($path, file_get_contents(self::SOURCE_DIRECTORY.'/'.$file));

        return $path;
    }
}
