<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Procesa imágenes subidas: las decodifica y vuelve a codificar con GD
 * (elimina metadatos y cualquier contenido no-imagen), limita el tamaño
 * y conserva la transparencia. Guarda en el disco público como WebP.
 */
class ImageService
{
    public function store(UploadedFile $file, string $directory, int $maxSize = 800, ?string $previous = null): string
    {
        $raw = @file_get_contents($file->getRealPath());
        $info = $raw !== false ? @getimagesizefromstring($raw) : false;
        if (! $info || ! in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_WEBP], true)) {
            throw new RuntimeException('El archivo no es una imagen válida.');
        }

        $src = @imagecreatefromstring($raw);
        if (! $src) {
            throw new RuntimeException('No se pudo procesar la imagen.');
        }

        [$w, $h] = [imagesx($src), imagesy($src)];
        $scale = min(1, $maxSize / max($w, $h));
        [$nw, $nh] = [max(1, (int) round($w * $scale)), max(1, (int) round($h * $scale))];

        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

        ob_start();
        imagewebp($dst, null, 88);
        $binary = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $binary);

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
}
