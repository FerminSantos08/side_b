<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/assets/php/editorial.php';

if (!class_exists('DOMDocument')) {
    fwrite(STDERR, "Falta php-xml para probar la limpieza editorial.\n"); exit(1);
}
$cases = [
    '<div><p onclick="alert(1)">Texto <strong>válido</strong></p></div>',
    '<section><div><a href="javascript:alert(1)">Enlace</a><img src=x onerror="alert(1)"></div></section>',
    '<p><a href="https://example.com" onclick="alert(1)">Enlace válido</a></p>',
];
foreach ($cases as $input) {
    $clean = clean_editorial_html($input);
    if (preg_match('/on(?:click|error)\s*=|javascript:|<\/?(?:div|section|img)\b/i', $clean)) {
        fwrite(STDERR, "La limpieza dejó contenido no permitido.\n"); exit(1);
    }
}
$clean = clean_editorial_html($cases[0]);
if (!str_contains($clean, '<strong>válido</strong>')) {
    fwrite(STDERR, "Se perdió formato editorial permitido.\n"); exit(1);
}
if (!str_contains(clean_editorial_html($cases[2]), 'href="https://example.com"')) {
    fwrite(STDERR, "Se perdió un enlace permitido.\n"); exit(1);
}
if (publication_image_url('../secreto.png') !== null || publication_image_url('archivo.php') !== null
    || publication_image_url('portada.png') !== 'uploads/publicaciones/portada.png') {
    fwrite(STDERR, "La ruta de imagen editorial es incorrecta.\n"); exit(1);
}
echo "Pruebas de contenido editorial: OK\n";
