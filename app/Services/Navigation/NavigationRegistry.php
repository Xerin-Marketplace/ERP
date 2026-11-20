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

    /** All registered items, unfiltered (admin tooling). */
    public function all(): Collection
    {
        return $this->items;
    }

    /**
     * Items visible to the current user, grouped + ordered.
     * Admin navigation overrides (hide/rename/reorder) are applied here.
     *
     * @return array<string, Collection<int, NavItem>>
     */
    public function visible(): array
    {
        $items = $this->items->filter->canSee();

        try {
            $overrides = \Modules\SystemAdministration\Models\NavigationOverride::cached();
            if ($overrides->isNotEmpty()) {
                $items = $items
                    ->map(function (NavItem $item) use ($overrides) {
                        $o = $overrides->get($item->module.':'.$item->label);
                        if (! $o) {
                            return $item;
                        }
                        $clone = clone $item;
                        if ($o->is_hidden) {
                            $clone->visible = false;
                        }
                        if ($o->label_override) {
                            $clone->label = $o->label_override;
                        }
                        if ($o->order_override !== null) {
                            $clone->order = $o->order_override;
                        }
                        return $clone;
                    })
                    ->filter->canSee();
            }
        } catch (\Throwable) {
            // Override table may not exist yet (migrations) — show defaults.
        }

        return $items->sortBy('order')->groupBy('group')->all();
    }
}
