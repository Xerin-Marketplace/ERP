<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Write an audit record.
     *
     * Usage: Audit::log('user.login'); Audit::log('product.updated', $product, old: [...], new: [...]);
     */
    public function log(
        string $action,
        ?Model $resource = null,
        ?array $old = null,
        ?array $new = null,
        ?array $metadata = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'resource_type' => $resource ? $resource::class : null,
            'resource_id' => $resource?->getKey(),
            'old_values' => $old,
            'new_values' => $new,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 255),
        ]);
    }

    /**
     * Convenience: audit a model change with only dirty attributes.
     */
    public function modelUpdated(Model $model, string $action): AuditLog
    {
        $dirty = $model->getChanges();
        $old = array_intersect_key($model->getOriginal(), $dirty);

        return $this->log($action, $model, $old, $dirty);
    }
}
