<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\MenuItem;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = null;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $visible = MenuItem::query()->visible()->count();

        return [
            Stat::make('Món đang hiện trên web', $visible)
                ->description(MenuItem::query()->where('is_active', false)->count().' món đang ẩn')
                ->icon(Heroicon::OutlinedCake),
            Stat::make('Món tạm hết', MenuItem::query()->visible()->where('is_available', false)->count())
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('warning'),
            Stat::make('Món nổi bật', MenuItem::query()->visible()->where('is_featured', true)->count())
                ->icon(Heroicon::OutlinedStar),
            Stat::make('Danh mục', Category::query()->where('is_active', true)->count())
                ->icon(Heroicon::OutlinedSquares2x2),
            Stat::make('Món chưa có ảnh', MenuItem::query()->visible()->whereNull('image')->count())
                ->description('Thêm ảnh để thực đơn hấp dẫn hơn')
                ->icon(Heroicon::OutlinedPhoto),
        ];
    }
}
