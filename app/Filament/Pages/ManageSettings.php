<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\Site;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use UnitEnum;

/**
 * Cấu hình giao diện / nội dung / SEO lưu bảng settings, key "group.key" (form dùng state lồng nhau group → key).
 * Giá trị mặc định khi để trống nằm ở App\Support\Site.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = 'Hệ thống';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Cài đặt';

    protected static ?string $title = 'Cài đặt';

    protected static ?string $slug = 'settings';

    /** Ảnh upload vào storage public. */
    private const IMAGE_DIRECTORY = 'branding';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $values = [];

        foreach (Setting::all() as $setting) {
            Arr::set($values, "{$setting->group}.{$setting->key}", $setting->value);
        }

        $this->form->fill($values);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('settings')
                    ->persistTabInQueryString()
                    ->columnSpanFull()
                    ->tabs([
                        $this->brandTab(),
                        $this->homeTab(),
                        $this->contactTab(),
                        $this->seoTab(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Lưu cài đặt')->submit('save'),
                        Action::make('preview')
                            ->label('Xem trang chủ')
                            ->color('gray')
                            ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                            ->url(url('/'), shouldOpenInNewTab: true),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        // Cài đặt luôn 2 cấp group.key; không dùng Arr::dot vì sẽ làm phẳng giá trị mảng (từ khóa SEO).
        foreach ($this->form->getState() as $group => $fields) {
            foreach ((array) $fields as $key => $value) {
                $name = "{$group}.{$key}";

                Setting::set($name, is_string($value) && trim($value) === '' ? null : (is_string($value) ? trim($value) : $value));
            }
        }

        Notification::make()->title('Đã lưu cài đặt')->success()->send();
    }

    private function brandTab(): Tab
    {
        return Tab::make('Thương hiệu')
            ->icon(Heroicon::OutlinedSparkles)
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('restaurant.name')->label('Tên quán')->required()->maxLength(100),
                    TextInput::make('restaurant.slogan')->label('Slogan')->placeholder('Món Việt đậm vị')->maxLength(150),
                ]),
                Grid::make(3)->schema([
                    FileUpload::make('brand.logo')
                        ->label('Logo')
                        ->helperText('PNG / SVG nền trong suốt, cao tối thiểu 120px. Hiện ở đầu trang web và trang admin.')
                        ->image()
                        ->disk('public')
                        ->directory(self::IMAGE_DIRECTORY)
                        ->maxSize(2048),
                    FileUpload::make('brand.favicon')
                        ->label('Favicon (biểu tượng tab trình duyệt)')
                        ->helperText('Ảnh vuông, 512×512. Để trống sẽ dùng logo.')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->disk('public')
                        ->directory(self::IMAGE_DIRECTORY)
                        ->maxSize(1024),
                    ColorPicker::make('brand.color')
                        ->label('Màu chủ đạo')
                        ->helperText('Nút, giá tiền, điểm nhấn trên web và trang admin. Mặc định '.Site::DEFAULT_COLOR.'.')
                        ->placeholder(Site::DEFAULT_COLOR)
                        ->regex('/^#[0-9a-fA-F]{6}$/'),
                ]),
            ]);
    }

    private function homeTab(): Tab
    {
        return Tab::make('Trang chủ')
            ->icon(Heroicon::OutlinedHome)
            ->schema([
                Section::make('Phần đầu trang')
                    ->columns(2)
                    ->schema([
                        TextInput::make('home.hero_eyebrow')->label('Dòng chữ nhỏ phía trên')->placeholder('Chào mừng đến với')->maxLength(80),
                        TextInput::make('home.hero_title')->label('Tiêu đề lớn')->placeholder('Để trống = tên quán')->maxLength(120),
                        Textarea::make('home.hero_subtitle')->label('Mô tả ngắn')->placeholder('Để trống = slogan')->rows(2)->maxLength(300)->columnSpanFull(),
                        FileUpload::make('home.hero_image')
                            ->label('Ảnh nền')
                            ->helperText('Ảnh ngang, tối thiểu 1600px. Được phủ lớp tối để chữ dễ đọc.')
                            ->image()
                            ->disk('public')
                            ->directory(self::IMAGE_DIRECTORY)
                            ->maxSize(5120),
                        Grid::make(1)->schema([
                            TextInput::make('home.cta_text')
                                ->label('Nút thêm (không bắt buộc)')
                                ->placeholder('Đặt bàn qua Zalo')
                                ->maxLength(40),
                            TextInput::make('home.cta_url')
                                ->label('Link của nút')
                                ->placeholder('https://zalo.me/0900000000')
                                ->url()
                                ->required(fn (Get $get) => filled($get('home.cta_text'))),
                        ]),
                    ]),
                Section::make('Thực đơn')
                    ->columns(2)
                    ->schema([
                        Toggle::make('home.show_featured')
                            ->label('Hiện mục "Món nổi bật"')
                            ->helperText('Các món bật "Món nổi bật" trong Món ăn.')
                            ->default(true),
                        TextInput::make('home.menu_note')
                            ->label('Ghi chú dưới thực đơn')
                            ->placeholder('Giá đã bao gồm VAT')
                            ->maxLength(200),
                    ]),
                Section::make('Giới thiệu')
                    ->schema([
                        TextInput::make('home.about_title')->label('Tiêu đề')->placeholder('Về chúng tôi')->maxLength(100),
                        Textarea::make('home.about_text')->label('Nội dung')->helperText('Để trống thì ẩn phần giới thiệu. Xuống dòng để tách đoạn.')->rows(5)->maxLength(3000),
                    ]),
            ]);
    }

    private function contactTab(): Tab
    {
        return Tab::make('Liên hệ')
            ->icon(Heroicon::OutlinedPhone)
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('restaurant.phone')->label('Điện thoại')->tel()->maxLength(30),
                    TextInput::make('restaurant.email')->label('Email')->email()->maxLength(150),
                    TextInput::make('restaurant.address')->label('Địa chỉ')->maxLength(255)->columnSpanFull(),
                    TextInput::make('restaurant.opening_hours')->label('Giờ mở cửa')->placeholder('10:00 - 22:00')->maxLength(100),
                    TextInput::make('restaurant.map_url')->label('Link Google Maps')->url()->maxLength(500),
                ]),
                Section::make('Mạng xã hội')
                    ->description('Để trống thì không hiện.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('social.facebook')->label('Facebook')->url()->placeholder('https://facebook.com/...')->maxLength(300),
                        TextInput::make('social.zalo')->label('Zalo')->placeholder('Số điện thoại hoặc link zalo.me')->maxLength(300),
                        TextInput::make('social.instagram')->label('Instagram')->url()->maxLength(300),
                        TextInput::make('social.tiktok')->label('TikTok')->url()->maxLength(300),
                    ]),
            ]);
    }

    private function seoTab(): Tab
    {
        return Tab::make('SEO')
            ->icon(Heroicon::OutlinedMagnifyingGlass)
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('seo.title')
                        ->label('Tiêu đề trang chủ (thẻ title)')
                        ->helperText('Hiện trên kết quả Google và tab trình duyệt, nên dưới 60 ký tự. Để trống = tên quán.')
                        ->maxLength(70),
                    TextInput::make('seo.cuisine')->label('Loại ẩm thực')->placeholder('Việt Nam')->maxLength(100),
                    Textarea::make('seo.description')
                        ->label('Mô tả (meta description)')
                        ->helperText('Đoạn mô tả dưới tiêu đề trên Google, nên 120-160 ký tự.')
                        ->rows(3)
                        ->maxLength(300)
                        ->columnSpanFull(),
                    TagsInput::make('seo.keywords')->label('Từ khóa')->placeholder('Nhập từ khóa rồi Enter')->columnSpanFull(),
                    FileUpload::make('seo.og_image')
                        ->label('Ảnh khi chia sẻ link (Facebook, Zalo...)')
                        ->helperText('1200×630. Để trống sẽ dùng ảnh nền trang chủ hoặc logo.')
                        ->image()
                        ->disk('public')
                        ->directory(self::IMAGE_DIRECTORY)
                        ->maxSize(2048),
                    Grid::make(1)->schema([
                        Toggle::make('seo.allow_indexing')
                            ->label('Cho phép Google tìm thấy website')
                            ->helperText('Tắt khi đang chạy thử website.')
                            ->default(true),
                        TextInput::make('seo.google_analytics_id')
                            ->label('Google Analytics (Measurement ID)')
                            ->placeholder('G-XXXXXXXXXX')
                            ->regex('/^G-[A-Za-z0-9]{4,20}$/'),
                        TextInput::make('seo.google_site_verification')
                            ->label('Mã xác minh Google Search Console')
                            ->helperText('Phần content của thẻ meta google-site-verification.')
                            ->maxLength(100),
                    ]),
                ]),
            ]);
    }
}
