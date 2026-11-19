<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Genera un slug único (por organización cuando el modelo tiene club_id).
 * El modelo define slugSource(): string.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            if (empty($model->slug) || $model->isDirty($model->slugSourceAttributes())) {
                $model->slug = $model->uniqueSlug(Str::slug($model->slugSource()) ?: 'item');
            }
        });
    }

    /** @return list<string> */
    protected function slugSourceAttributes(): array
    {
        return ['name'];
    }

    protected function slugSource(): string
    {
        return (string) $this->name;
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = Str::limit($base, 150, '');
        $i = 2;
        while ($this->slugExists($slug)) {
            $slug = Str::limit($base, 145, '').'-'.$i++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $q = static::query()->where('slug', $slug);
        if (in_array(SoftDeletes::class, class_uses_recursive($this), true)) {
            $q->withTrashed();
        }
        if ($this->getAttribute('club_id') !== null && $this->getTable() !== 'clubs') {
            $q->where('club_id', $this->club_id);
        }
        if ($this->exists) {
            $q->whereKeyNot($this->getKey());
        }

        return $q->exists();
    }
}
