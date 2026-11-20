<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Resolve request locale: user preference → system.locale setting → app default.
 * Foundation for EN+SW bilingual ERP; strings live in lang/{locale}/.
 */
class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $available = ['en', 'sw'];

        try {
            $catalog = app(\Modules\SystemAdministration\Services\SettingCatalog::class);
            $configured = $catalog->get('system.available_locales');
            if (is_array($configured) && $configured !== []) {
                $available = $configured;
            }
            $systemDefault = $catalog->get('system.locale') ?: config('app.locale');
        } catch (\Throwable) {
            $systemDefault = config('app.locale');
        }

        $locale = $request->user()?->profile?->preferred_language
            ?? $request->session()->get('locale')
            ?? $systemDefault;

        App::setLocale(in_array($locale, $available, true) ? $locale : 'en');

        return $next($request);
    }
}
