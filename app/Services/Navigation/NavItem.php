<?php

namespace App\Services\Navigation;

class NavItem
{
    public function __construct(
        public string $module,
        public string $label,
        public string $icon,
        public ?string $route = null,
        public ?string $url = null,
        public string|array|null $permission = null,
        public string $group = 'Menu',
        public int $order = 100,
        public bool $visible = true,
        /** @var NavItem[] */
        public array $children = [],
    ) {}

    public function href(): ?string
    {
        return $this->route && \Illuminate\Support\Facades\Route::has($this->route)
            ? route($this->route)
            : $this->url;
    }

    public function isActive(): bool
    {
        if ($this->route && request()->routeIs($this->route)) {
            return true;
        }

        return collect($this->children)->contains->isActive();
    }

    /** @return NavItem[] */
    public function visibleChildren(): array
    {
        return collect($this->children)->filter->canSee()->sortBy('order')->values()->all();
    }

    public function canSee(): bool
    {
        if (! $this->visible) {
            return false;
        }

        // A parent item is only useful when at least one child is reachable.
        if ($this->children !== [] && $this->visibleChildren() === []) {
            return false;
        }

        if (! $this->permission) {
            return true;
        }

        $user = auth()->user();

        return is_array($this->permission)
            ? (bool) $user?->canAny($this->permission)
            : (bool) $user?->can($this->permission);
    }
}
