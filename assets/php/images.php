<?php
declare(strict_types=1);

/** Reencode raster uploads; never trust the client filename or MIME type. */
function convert_image_to_webp(string $source, string $destination, int $maxDimension = 1920): void
{
    if ($maxDimension < 1) throw new InvalidArgumentException('Dimensión de imagen inválida.');
    if (!extension_loaded('gd') || !function_exists('imagewebp') || !(imagetypes() & IMG_WEBP)) {
        throw new RuntimeException('El servidor necesita PHP GD con soporte WebP.');
    }
    $size = @filesize($source);
    if ($size === false || $size === 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen debe pesar como máximo 5 MB.');
    }
    $info = @getimagesize($source);
    $decoders = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($source);
    if (!$info || !isset($decoders[$mime]) || $info['mime'] !== $mime) {
        throw new RuntimeException('Usa una imagen JPG, PNG o WebP válida.');
    }
    // Bound decoded memory as well as compressed file size.
    if ($info[0] > 12000 || $info[1] > 12000 || $info[0] * $info[1] > 16000000) {
        throw new RuntimeException('La imagen supera el límite de 16 megapíxeles o 12000 píxeles por lado.');
    }
    $image = @$decoders[$mime]($source);
    if (!$image) throw new RuntimeException('No fue posible leer la imagen.');
    $output = null;
    try {
        // Apply phone camera orientation before stripping JPEG metadata.
        $orientation = 1;
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($source);
            $orientation = (int) ($exif['Orientation'] ?? 1);
        }
        if (in_array($orientation, [2, 4, 5, 7], true)) imageflip($image, IMG_FLIP_HORIZONTAL);
        $angle = match ($orientation) { 3, 4 => 180, 6, 7 => -90, 5, 8 => 90, default => 0 };
        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if (!$rotated) throw new RuntimeException('No fue posible orientar la imagen.');
            imagedestroy($image);
            $image = $rotated;
        }
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxDimension / max($width, $height));
        $targetWidth = max(1, (int) round($width * $scale));
        $targetHeight = max(1, (int) round($height * $scale));
        $output = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$output) throw new RuntimeException('No fue posible preparar la imagen.');
        imagealphablending($output, false);
        imagesavealpha($output, true);
        imagefill($output, 0, 0, imagecolorallocatealpha($output, 0, 0, 0, 127));
        if (!imagecopyresampled($output, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height)) {
            throw new RuntimeException('No fue posible ajustar la imagen.');
        }
        if (!imagewebp($output, $destination, 82) || !is_file($destination) || filesize($destination) === 0) {
            throw new RuntimeException('No fue posible guardar la imagen WebP.');
        }
    } catch (Throwable $error) {
        if (is_file($destination)) unlink($destination);
        throw $error;
    } finally {
        if ($output !== null) imagedestroy($output);
        imagedestroy($image);
    }
}
