<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Procesa imágenes subidas: las decodifica y vuelve a codificar con GD
 * (elimina metadatos y cualquier contenido no-imagen), limita el tamaño
 * y conserva la transparencia. Guarda en el disco público como WebP.
 *
 * Nunca debe provocar un error 500: cualquier problema se devuelve como
 * error de validación del campo, con un mensaje comprensible.
 */
class ImageService
{
    /** Megapíxeles máximos que se aceptan decodificar (≈ 7000×7000). */
    private const MAX_MEGAPIXELS = 50;

    public function store(UploadedFile $file, string $directory, int $maxSize = 800, ?string $previous = null, string $field = 'logo'): string
    {
        $fail = fn (string $msg) => throw ValidationException::withMessages([$field => $msg]);

        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            $fail('El archivo no es una imagen PNG, JPG o WebP válida.');
        }
        [$w, $h] = $info;
        if ($w * $h > self::MAX_MEGAPIXELS * 1_000_000) {
            $fail('La imagen es demasiado grande ('.$w.'×'.$h.' px). Redúzcala a menos de 5000 px de lado.');
        }

        // GD necesita ~5 bytes por píxel para decodificar + el lienzo de salida.
        $needed = (int) ($w * $h * 5 + $maxSize * $maxSize * 5 + 64 * 1024 * 1024);
        $this->ensureMemory($needed);

        try {
            $src = match ($info[2]) {
                IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
                IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
                IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
            };
            if (! $src) {
                $fail('No se pudo leer la imagen. Pruebe exportarla de nuevo como PNG o JPG.');
            }
            if (! imageistruecolor($src)) {
                imagepalettetotruecolor($src);
            }

            $scale = min(1, $maxSize / max($w, $h));
            [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);

            ob_start();
            $ok = imagewebp($dst, null, 88);
            $binary = ob_get_clean();
            imagedestroy($dst);
            if (! $ok || ! $binary) {
                $fail('No se pudo convertir la imagen. Pruebe con otro archivo.');
            }

            $path = trim($directory, '/').'/'.Str::uuid().'.webp';
            if (! Storage::disk('public')->put($path, $binary)) {
                Log::error('ImageService: no se pudo escribir en el disco público', ['path' => $path]);
                $fail('No se pudo guardar la imagen en el servidor (permisos de almacenamiento).');
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            $fail('No se pudo procesar la imagen. Pruebe con un archivo más liviano (PNG o JPG).');
        }

        if ($previous) {
            $this->delete($previous);
        }

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /** Sube temporalmente el límite de memoria de PHP si la imagen lo requiere. */
    private function ensureMemory(int $bytes): void
    {
        $limit = $this->toBytes((string) ini_get('memory_limit'));
        if ($limit !== -1 && $limit < $bytes) {
            @ini_set('memory_limit', (string) min(max($bytes, 256 * 1024 * 1024), 1024 * 1024 * 1024));
        }
    }

    private function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '-1') {
            return -1;
        }
        $n = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
