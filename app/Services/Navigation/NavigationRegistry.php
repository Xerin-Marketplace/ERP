<?php

namespace App\Services\Navigation;

use Illuminate\Support\Collection;

/**
 * Central navigation registry.
 *
 * Modules register items in their service provider:
 *
 *   Navigation::register(new NavItem(
 *       module: 'Inventory',
 *       label: 'Products',
 *       icon: 'box',
 *       route: 'inventory.products.index',
 *       permission: 'inventory.products.view',
 *       group: 'Inventory',
 *       order: 10,
 *   ));
 */
class NavigationRegistry
{
    /** @var Collection<int, NavItem> */
    private Collection $items;

    public function __construct()
    {
        $this->items = collect();
    }

    public function register(NavItem $item): void
    {
        $this->items->push($item);
    }

    /**
     * Items visible to the current user, grouped + ordered.
     *
     * @return array<string, Collection<int, NavItem>>
     */
    public function visible(): array
    {
        return $this->items
            ->filter->canSee()
            ->sortBy('order')
            ->groupBy('group')
            ->all();
    }
}
