<?php

namespace App\Services\Menu;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Thực đơn cho trang chủ, có cache. Xóa cache khi admin sửa danh mục / món (model event gọi flush()).
 */
class MenuCatalog
{
    private const CACHE_KEY = 'menu.catalog';

    /**
     * Danh mục đang bật kèm các món đang hiện (gồm món tạm hết, hiện nhãn "Tạm hết").
     *
     * @return Collection<int, Category>
     */
    public function categories(): Collection
    {
        // Cache mảng thuộc tính rồi hydrate lại: Laravel không unserialize object từ cache
        // (cache.serializable_classes = false).
        $rows = Cache::rememberForever(self::CACHE_KEY, fn () => Category::query()
            ->active()
            ->ordered()
            ->with(['menuItems' => fn ($query) => $query->visible()->ordered()])
            ->get()
            ->filter(fn (Category $category) => $category->menuItems->isNotEmpty())
            ->map(fn (Category $category) => [
                'category' => $category->getAttributes(),
                'items' => $category->menuItems->map(fn (MenuItem $item) => $item->getAttributes())->all(),
            ])
            ->values()
            ->all());

        $categories = Category::hydrate(array_column($rows, 'category'));

        foreach ($rows as $index => $row) {
            $categories[$index]->setRelation('menuItems', MenuItem::hydrate($row['items']));
        }

        return $categories;
    }

    /**
     * Món nổi bật đang bán.
     *
     * @return \Illuminate\Support\Collection<int, MenuItem>
     */
    public function featured(): \Illuminate\Support\Collection
    {
        return $this->categories()->flatMap->menuItems->where('is_featured', true)->values();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
