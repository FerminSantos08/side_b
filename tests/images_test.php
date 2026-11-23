<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/assets/php/editorial.php';

function check_image(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$directory = sys_get_temp_dir() . '/side-b-images-' . bin2hex(random_bytes(8));
mkdir($directory);
try {
    foreach (['jpeg', 'png', 'webp'] as $format) {
        $image = imagecreatetruecolor(2400, 1200);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 80, 120, 160, $format === 'jpeg' ? 0 : 127));
        $source = "$directory/input.$format";
        $destination = "$directory/output-$format.webp";
        ('image' . $format)($image, $source);
        convert_image_to_webp($source, $destination);
        $info = getimagesize($destination);
        check_image($info['mime'] === 'image/webp' && $info[0] === 1920 && $info[1] === 960, 'Conversión o dimensiones incorrectas.');
        if ($format !== 'jpeg') {
            $decoded = imagecreatefromwebp($destination);
            check_image(imagecolorsforindex($decoded, imagecolorat($decoded, 0, 0))['alpha'] === 127, 'Se perdió la transparencia.');
        }
        convert_image_to_webp($source, "$directory/avatar-$format.webp", 512);
        check_image(getimagesize("$directory/avatar-$format.webp")[0] === 512, 'Avatar demasiado grande.');
    }
    $small = imagecreatetruecolor(30, 20);
    imagepng($small, "$directory/small.png");
    convert_image_to_webp("$directory/small.png", "$directory/small.webp");
    check_image(getimagesize("$directory/small.webp")[0] === 30, 'Se amplió una imagen pequeña.');
    file_put_contents("$directory/fake.jpg", '<?php echo "invalid";');
    try {
        convert_image_to_webp("$directory/fake.jpg", "$directory/fake.webp");
        throw new LogicException('Se aceptó un archivo falso.');
    } catch (RuntimeException) {
        check_image(!file_exists("$directory/fake.webp"), 'Se guardó un archivo inválido.');
    }
    check_image(upload_backstage_image([], 'artistas', 'old.jpg') === 'old.jpg', 'Se perdió la imagen anterior.');
    check_image(upload_publication_image([], 'old.jpg') === 'old.jpg', 'Se perdió la portada anterior.');
    try {
        upload_backstage_image(['error' => UPLOAD_ERR_PARTIAL], 'artistas');
        throw new LogicException('Se aceptó una subida incompleta.');
    } catch (RuntimeException) {}
    echo "Pruebas de imágenes: OK\n";
} finally {
    foreach (glob($directory . '/*') as $file) unlink($file);
    rmdir($directory);
}
