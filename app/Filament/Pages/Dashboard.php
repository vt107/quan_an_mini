<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MenuOverview;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Tổng quan';

    public static function getNavigationLabel(): string
    {
        return 'Tổng quan';
    }

    public function getWidgets(): array
    {
        return [
            MenuOverview::class,
        ];
    }
}
