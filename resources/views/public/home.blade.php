@use('App\Support\Money')

<x-layouts.app>
    @push('head')
        <script type="application/ld+json">{!! json_encode($site->structuredData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush

    {{-- ===== Đầu trang ===== --}}
    <header class="relative overflow-hidden bg-stone-900 text-white">
        @if ($hero = $site->heroImageUrl())
            <img src="{{ $hero }}" alt="" class="absolute inset-0 size-full object-cover opacity-45">
            <div class="absolute inset-0 bg-gradient-to-t from-stone-950/85 via-stone-900/40 to-stone-900/20"></div>
        @else
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,var(--color-amber-600),transparent_60%)] opacity-50"></div>
        @endif

        <nav class="relative mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2 font-bold">
                @if ($logo = $site->logoUrl())
                    {{-- Nền trắng: logo chữ tối vẫn đọc được trên header tối --}}
                    <span class="rounded-xl bg-white/95 px-2.5 py-1.5 shadow-sm"><img src="{{ $logo }}" alt="{{ $site->name() }}" class="h-8 w-auto"></span>
                @else
                    <span class="truncate text-lg">{{ $site->name() }}</span>
                @endif
            </a>
            <div class="flex shrink-0 items-center gap-4 text-sm font-medium">
                <a href="#thuc-don" class="hidden hover:text-amber-300 sm:inline">Thực đơn</a>
                <a href="#lien-he" class="hidden hover:text-amber-300 sm:inline">Liên hệ</a>
                @if ($phoneUrl = $site->phoneUrl())
                    <a href="{{ $phoneUrl }}" class="rounded-full bg-white/15 px-4 py-2 backdrop-blur hover:bg-white/25">{{ $site->get('restaurant.phone') }}</a>
                @endif
            </div>
        </nav>

        <div class="relative mx-auto max-w-6xl px-4 pb-16 pt-10 sm:pb-24 sm:pt-16">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">{{ $site->get('home.hero_eyebrow', 'Chào mừng đến với') }}</p>
            <h1 class="mt-2 max-w-3xl text-4xl font-extrabold sm:text-6xl">{{ $site->get('home.hero_title', $site->name()) }}</h1>
            @if ($subtitle = $site->get('home.hero_subtitle', $site->slogan()))
                <p class="mt-4 max-w-xl text-lg text-stone-200">{{ $subtitle }}</p>
            @endif
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="#thuc-don" class="rounded-full bg-amber-600 px-6 py-3 font-bold text-white shadow-lg hover:bg-amber-700">Xem thực đơn</a>
                @if (($ctaText = $site->get('home.cta_text')) && ($ctaUrl = $site->get('home.cta_url')))
                    <a href="{{ $ctaUrl }}" target="_blank" rel="noopener" class="rounded-full border border-white/30 px-6 py-3 font-semibold hover:bg-white/10">{{ $ctaText }}</a>
                @endif
                @if ($phoneUrl)
                    <a href="{{ $phoneUrl }}" class="rounded-full border border-white/30 px-6 py-3 font-semibold hover:bg-white/10">Gọi {{ $site->get('restaurant.phone') }}</a>
                @endif
            </div>
        </div>
    </header>

    {{-- ===== Món nổi bật ===== --}}
    @if ($site->get('home.show_featured', true) && $featured->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 pt-14">
            <h2 class="text-3xl font-bold">Món nổi bật</h2>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $item)
                    <article class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-stone-200">
                        @if ($item->image_url)
                            <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                        @else
                            <div class="flex aspect-[4/3] items-center justify-center bg-gradient-to-br from-amber-100 to-amber-200 text-6xl">🍽️</div>
                        @endif
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="text-lg font-bold">{{ $item->name }}</h3>
                                <span class="shrink-0 font-bold text-amber-700">{{ Money::format($item->price) }}</span>
                            </div>
                            @if ($item->description)
                                <p class="mt-1 text-sm text-stone-600">{{ $item->description }}</p>
                            @endif
                            @unless ($item->is_available)
                                <span class="mt-2 inline-block rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-600">Tạm hết</span>
                            @endunless
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ===== Thực đơn ===== --}}
    <section id="thuc-don" class="mx-auto max-w-6xl scroll-mt-16 px-4 py-14">
        <h2 class="text-3xl font-bold">Thực đơn</h2>

        @if ($categories->count() > 1)
            <div class="no-scrollbar sticky top-0 z-10 -mx-4 mt-4 flex gap-2 overflow-x-auto bg-stone-50/95 px-4 py-3 backdrop-blur">
                @foreach ($categories as $category)
                    <a href="#danh-muc-{{ $category->slug }}" class="shrink-0 rounded-full border border-stone-200 bg-white px-4 py-1.5 text-sm font-medium text-stone-700 hover:border-amber-400 hover:text-amber-700">{{ $category->name }}</a>
                @endforeach
            </div>
        @endif

        <div class="mt-4 space-y-12">
            @forelse ($categories as $category)
                <div id="danh-muc-{{ $category->slug }}" class="scroll-mt-20">
                    <div class="flex items-end gap-4 border-b-2 border-amber-500 pb-2">
                        <h3 class="text-2xl font-bold">{{ $category->name }}</h3>
                        @if ($category->description)
                            <p class="pb-0.5 text-sm text-stone-500">{{ $category->description }}</p>
                        @endif
                    </div>
                    <div class="mt-4 grid gap-x-10 gap-y-4 md:grid-cols-2">
                        @foreach ($category->menuItems as $item)
                            <article @class(['flex gap-4', 'opacity-60' => ! $item->is_available])>
                                @if ($item->image_url)
                                    <img src="{{ $item->image_url }}" alt="{{ $item->name }}" loading="lazy" class="size-20 shrink-0 rounded-xl object-cover sm:size-24">
                                @endif
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-baseline gap-2">
                                        <h4 class="font-semibold">{{ $item->name }}</h4>
                                        <span class="flex-1 border-b border-dotted border-stone-300"></span>
                                        <span class="shrink-0 font-bold text-amber-700">{{ Money::format($item->price) }}</span>
                                    </div>
                                    @if ($item->description)
                                        <p class="mt-0.5 text-sm text-stone-500">{{ $item->description }}</p>
                                    @endif
                                    @unless ($item->is_available)
                                        <span class="mt-1 inline-block rounded-full bg-stone-200 px-2 py-0.5 text-xs font-semibold text-stone-600">Tạm hết</span>
                                    @endunless
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="py-10 text-center text-stone-500">Thực đơn đang được cập nhật.</p>
            @endforelse
        </div>

        @if ($note = $site->get('home.menu_note'))
            <p class="mt-10 text-center text-sm text-stone-500">{{ $note }}</p>
        @endif
    </section>

    {{-- ===== Giới thiệu ===== --}}
    @if ($about = $site->get('home.about_text'))
        <section class="bg-white">
            <div class="mx-auto max-w-3xl px-4 py-14 text-center">
                <h2 class="text-3xl font-bold">{{ $site->get('home.about_title', 'Về chúng tôi') }}</h2>
                <div class="mt-4 space-y-3 text-lg leading-relaxed text-stone-600">
                    @foreach (preg_split('/\R+/', trim($about)) as $paragraph)
                        <p>{{ $paragraph }}</p>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ===== Liên hệ ===== --}}
    <footer id="lien-he" class="bg-stone-900 text-stone-200">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:grid-cols-3">
            <div>
                <h2 class="text-xl font-bold text-white">{{ $site->name() }}</h2>
                @if ($site->slogan())
                    <p class="mt-1 text-stone-400">{{ $site->slogan() }}</p>
                @endif
            </div>
            <dl class="space-y-3 text-sm">
                @foreach (['Địa chỉ' => 'restaurant.address', 'Giờ mở cửa' => 'restaurant.opening_hours', 'Điện thoại' => 'restaurant.phone', 'Email' => 'restaurant.email'] as $label => $key)
                    @if ($value = $site->get($key))
                        <div><dt class="text-stone-400">{{ $label }}</dt><dd class="font-semibold text-white">{{ $value }}</dd></div>
                    @endif
                @endforeach
            </dl>
            @if ($links = $site->socialLinks())
                <div class="flex flex-wrap content-start gap-2">
                    @foreach ($links as $label => $url)
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="rounded-full border border-white/20 px-4 py-2 text-sm font-medium hover:bg-white/10">{{ $label }}</a>
                    @endforeach
                </div>
            @endif
        </div>
        <div class="border-t border-white/10 py-5 text-center text-xs text-stone-500">© {{ now()->year }} {{ $site->name() }}</div>
    </footer>
</x-layouts.app>
