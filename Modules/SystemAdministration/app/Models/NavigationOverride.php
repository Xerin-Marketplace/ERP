<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationOverride extends Model
{
    protected $table = 'system_navigation_overrides';

    protected $fillable = ['item_key', 'label_override', 'order_override', 'is_hidden', 'updated_by'];

    protected $casts = ['is_hidden' => 'boolean'];

    /** All overrides keyed by item_key, cached for nav rendering (plain objects). */
    public static function cached(): \Illuminate\Support\Collection
    {
        return collect(\Illuminate\Support\Facades\Cache::remember('nav:overrides', 300,
            fn () => static::all()->map(fn ($m) => $m->only(['item_key', 'label_override', 'order_override', 'is_hidden']))->keyBy('item_key')->all()
        ))->map(fn ($o) => (object) $o);
    }

    public static function flushCache(): void
    {
        \Illuminate\Support\Facades\Cache::forget('nav:overrides');
    }
}
