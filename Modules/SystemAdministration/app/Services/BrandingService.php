<?php

namespace Modules\SystemAdministration\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\SystemAdministration\Models\BrandingAsset;

/**
 * Manages uploaded branding assets (logos, favicons) with history + restore.
 * Files live under public/branding/ so they are directly servable;
 * history rows let an admin roll back to any previous version.
 */
class BrandingService
{
    public const ALLOWED_MIMES = ['image/png', 'image/jpeg', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon'];
    public const MAX_SIZE_KB = 2048;

    /** Logo slots => recommended dimensions note. */
    public const SLOTS = [
        'logo_primary' => 'Primary logo (sidebar, ~180px wide, transparent PNG/WebP)',
        'logo_icon' => 'Collapsed sidebar icon (~64×64)',
        'logo_login' => 'Login page logo',
        'favicon' => 'Favicon (PNG/ICO, 32×32 or 48×48)',
    ];

    public function current(string $key): ?BrandingAsset
    {
        return BrandingAsset::where('key', $key)->where('is_current', true)->latest()->first();
    }

    public function currentUrl(string $key): ?string
    {
        return \Illuminate\Support\Facades\Cache::remember("branding:{$key}", 300, function () use ($key) {
            try {
                return $this->current($key)?->url();
            } catch (\Illuminate\Database\QueryException) {
                return null;
            }
        });
    }

    public static function flushCache(): void
    {
        collect(self::SLOTS)->keys()->each(fn ($k) => \Illuminate\Support\Facades\Cache::forget("branding:{$k}"));
    }

    public function history(string $key)
    {
        return BrandingAsset::where('key', $key)->latest()->limit(10)->get();
    }

    public function upload(UploadedFile $file, string $key): BrandingAsset
    {
        if (! isset(self::SLOTS[$key])) {
            throw ValidationException::withMessages(['key' => 'Unknown branding slot.']);
        }
        if (! in_array($file->getMimeType(), self::ALLOWED_MIMES, true)) {
            throw ValidationException::withMessages(['file' => "Type {$file->getMimeType()} not allowed. Use PNG, JPEG, WebP or ICO."]);
        }
        if ($file->getSize() > self::MAX_SIZE_KB * 1024) {
            throw ValidationException::withMessages(['file' => 'File exceeds the '.self::MAX_SIZE_KB.' KB limit.']);
        }

        $dir = public_path('branding');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $key.'-'.now()->format('YmdHis').'.'.$file->guessExtension();
        $file->move($dir, $filename);

        BrandingAsset::where('key', $key)->update(['is_current' => false]);

        $asset = BrandingAsset::create([
            'key' => $key,
            'path' => 'branding/'.$filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'is_current' => true,
            'uploaded_by' => auth()->id(),
        ]);
        self::flushCache();

        return $asset;
    }

    public function restore(BrandingAsset $asset): void
    {
        if (! is_file(public_path($asset->path))) {
            throw ValidationException::withMessages(['asset' => 'The stored file no longer exists.']);
        }

        BrandingAsset::where('key', $asset->key)->update(['is_current' => false]);
        $asset->update(['is_current' => true]);
        self::flushCache();
    }
}
