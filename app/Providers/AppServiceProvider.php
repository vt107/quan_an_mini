<?php

namespace App\Providers;

use App\Models\User;
use App\Support\Site;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Site::class);
    }

    public function boot(): void
    {
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // SSL kết thúc ở Cloudflare / proxy: vẫn sinh link https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Cấu hình giao diện / SEO cho view của app (không gắn vào view của Filament / vendor).
        View::composer(
            ['components.layouts.*', 'partials.*', 'public.*', 'errors.*'],
            fn ($view) => $view->with('site', $this->app->make(Site::class)),
        );

        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
