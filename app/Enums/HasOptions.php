<?php

namespace App\Enums;

/**
 * Utilidades comunes para enums con etiqueta en español.
 */
trait HasOptions
{
    /** @return array<string, string> valor => etiqueta */
    public static function options(): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label();
        }

        return $out;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
