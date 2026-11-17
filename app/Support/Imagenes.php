<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Convierte fotos subidas a webp (foto grande + miniatura) con GD. Si GD no tiene webp, guarda el original. */
class Imagenes
{
    /**
     * @return array{archivo:string, url:string, miniatura_url:string}
     */
    public static function guardarWebp(UploadedFile $archivo, string $carpeta, int $lado = 1600, int $ladoMini = 480): array
    {
        $base = $carpeta . '/' . Str::random(20);
        $origen = self::abrir($archivo->getRealPath());

        if (! $origen || ! function_exists('imagewebp')) {
            $ruta = $archivo->store($carpeta, 'public');
            $url = Storage::disk('public')->url($ruta);

            return ['archivo' => $ruta, 'url' => $url, 'miniatura_url' => $url];
        }

        $grande = self::redimensionar($origen, $lado);
        $mini = self::redimensionar($origen, $ladoMini);

        $rutaGrande = $base . '.webp';
        $rutaMini = $base . '-mini.webp';
        Storage::disk('public')->put($rutaGrande, self::webp($grande, 82));
        Storage::disk('public')->put($rutaMini, self::webp($mini, 75));

        imagedestroy($origen);

        return [
            'archivo' => $rutaGrande,
            'url' => Storage::disk('public')->url($rutaGrande),
            'miniatura_url' => Storage::disk('public')->url($rutaMini),
        ];
    }

    public static function borrar(?string $archivo): void
    {
        if (! $archivo) {
            return;
        }
        Storage::disk('public')->delete([$archivo, preg_replace('/\.webp$/', '-mini.webp', $archivo)]);
    }

    private static function abrir(string $ruta)
    {
        $info = @getimagesize($ruta);
        if (! $info) {
            return null;
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($ruta),
            IMAGETYPE_PNG => @imagecreatefrompng($ruta),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($ruta) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($ruta),
            default => null,
        };
        if (! $img) {
            return null;
        }

        // Fotos de celular: respeta la orientación EXIF
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($ruta);
            $giro = ['3' => 180, '6' => -90, '8' => 90][(string) ($exif['Orientation'] ?? '')] ?? 0;
            if ($giro) {
                $img = imagerotate($img, $giro, 0);
            }
        }

        return $img;
    }

    private static function redimensionar($img, int $lado)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        $escala = min(1, $lado / max($w, $h));
        $nw = max(1, (int) round($w * $escala));
        $nh = max(1, (int) round($h * $escala));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }

    private static function webp($img, int $calidad): string
    {
        ob_start();
        imagewebp($img, null, $calidad);
        imagedestroy($img);

        return (string) ob_get_clean();
    }
}
