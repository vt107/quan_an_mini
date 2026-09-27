<?php

use App\Http\Controllers\Site\SeoController;
use App\Services\Menu\MenuCatalog;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (MenuCatalog $catalog) => view('public.home', [
    'categories' => $catalog->categories(),
    'featured' => $catalog->featured(),
]))->name('home');

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
