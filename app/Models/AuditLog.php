<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function eventLabel(): string
    {
        return match ($this->event) {
            'created' => 'Creación',
            'updated' => 'Modificación',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'login_failed' => 'Intento fallido',
            'password_reset' => 'Restablecimiento de contraseña',
            default => ucfirst($this->event),
        };
    }

    public function subjectLabel(): string
    {
        return $this->auditable_type ? __('models.'.class_basename($this->auditable_type)) : '—';
    }
}
