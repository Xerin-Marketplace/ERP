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
    ) {}

    public function href(): ?string
    {
        return $this->route && \Illuminate\Support\Facades\Route::has($this->route)
            ? route($this->route)
            : $this->url;
    }

    public function isActive(): bool
    {
        return $this->route ? request()->routeIs($this->route) : false;
    }

    public function canSee(): bool
    {
        if (! $this->visible) {
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
