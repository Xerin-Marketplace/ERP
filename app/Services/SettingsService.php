<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class SettingsService
{
    private const CACHE_PREFIX = 'settings:';

    /**
     * Get a setting value, decrypted if flagged, with caching.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever(self::CACHE_PREFIX.$key, function () use ($key, $default) {
            $setting = SystemSetting::where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            $value = $setting->is_encrypted && $setting->value
                ? Crypt::decryptString($setting->value)
                : $setting->value;

            return $this->cast($value, $setting->type);
        });
    }

    /**
     * Store a setting. Pass encrypt: true for sensitive values.
     * Never store real secrets (API keys, passwords) unencrypted.
     */
    public function set(string $key, mixed $value, string $group = 'general', string $type = 'string', bool $encrypt = false): void
    {
        SystemSetting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $encrypt ? Crypt::encryptString((string) $value) : (string) $value,
                'group' => $group,
                'type' => $type,
                'is_encrypted' => $encrypt,
            ]
        );

        Cache::forget(self::CACHE_PREFIX.$key);
    }

    public function forget(string $key): void
    {
        SystemSetting::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX.$key);
    }

    /**
     * All settings of a group as key => value.
     */
    public function group(string $group): array
    {
        return SystemSetting::where('group', $group)->get()
            ->mapWithKeys(fn ($s) => [$s->key => $this->cast($s->value, $s->type)])
            ->all();
    }

    private function cast(?string $value, string $type): mixed
    {
        return match ($type) {
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int', 'integer' => (int) $value,
            'float' => (float) $value,
            'json', 'array' => json_decode($value ?? 'null', true),
            default => $value,
        };
    }
}
