<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/** Manifiesto web (base para la PWA de la Fase 4). */
class ManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $icon = fn (string $file, int $size, string $purpose = 'any') => [
            'src' => asset("assets/branding/icons/{$file}"), 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => $purpose,
        ];

        return response()->json([
            'name' => 'HR Media Score',
            'short_name' => 'HR Score',
            'description' => 'Torneos, resultados y estadísticas de baloncesto.',
            'lang' => 'es',
            'start_url' => '/',
            'scope' => '/',
            'display' => 'standalone',
            'background_color' => '#0B101A',
            'theme_color' => '#0B101A',
            'icons' => [
                $icon('icon-192.png', 192), $icon('icon-512.png', 512),
                $icon('icon-maskable-192.png', 192, 'maskable'), $icon('icon-maskable-512.png', 512, 'maskable'),
            ],
        ], 200, ['Content-Type' => 'application/manifest+json']);
    }
}
