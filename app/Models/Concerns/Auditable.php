<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

/**
 * Registra en audit_logs las altas, cambios y bajas del modelo.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn ($m) => AuditLogger::model('created', $m, [], $m->auditableAttributes($m->getAttributes())));

        static::updated(function ($m) {
            $changes = $m->auditableAttributes($m->getChanges());
            unset($changes['updated_at']);
            if ($changes === []) {
                return;
            }
            $old = array_intersect_key($m->auditableAttributes($m->getOriginal()), $changes);
            AuditLogger::model('updated', $m, $old, $changes);
        });

        static::deleted(fn ($m) => AuditLogger::model('deleted', $m, $m->auditableAttributes($m->getOriginal()), []));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn ($m) => AuditLogger::model('restored', $m, [], []));
        }
    }

    /** Excluye campos sensibles del registro de auditoría. */
    public function auditableAttributes(array $attributes): array
    {
        $hidden = array_merge($this->getHidden(), ['password', 'remember_token', 'created_at']);

        return array_diff_key($attributes, array_flip($hidden));
    }
}
