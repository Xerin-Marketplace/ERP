<?php

namespace Modules\SystemAdministration\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class SystemIntegration extends Model
{
    protected $table = 'system_integrations';

    protected $fillable = ['key', 'name', 'driver', 'is_enabled', 'config', 'last_tested_at', 'last_status', 'last_error', 'updated_by'];

    protected $casts = ['is_enabled' => 'boolean', 'last_tested_at' => 'datetime'];

    /** Decrypted config array; never expose raw in responses. */
    public function configArray(): array
    {
        if (! $this->config) {
            return [];
        }
        try {
            return json_decode(Crypt::decryptString($this->config), true) ?: [];
        } catch (\Throwable) {
            return [];
        }
    }

    public function setConfig(array $config): void
    {
        $this->config = Crypt::encryptString(json_encode($config));
    }

    /** Config with secret-looking values masked for display. */
    public function maskedConfig(): array
    {
        return collect($this->configArray())->map(fn ($v, $k) =>
            preg_match('/(secret|password|token|key|credential)/i', $k) && $v !== ''
                ? '••••••••' : $v
        )->all();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
