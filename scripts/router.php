<?php
declare(strict_types=1);

// Router exclusivo del servidor local de PHP.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if (str_contains($path, "\0") || str_contains($path, '\\')) {
    http_response_code(404); exit;
}
$root = realpath(dirname(__DIR__));
$target = realpath($root . $path);
if ($target === false) { http_response_code(404); exit('No encontrado.'); }
if ($target !== $root && !str_starts_with($target, $root . DIRECTORY_SEPARATOR)) {
    http_response_code(404); exit;
}
$relative = substr($target, strlen($root));
if (preg_match('~(?:^|/)\.[^/]*|^/(?:vendor|scripts|tests)(?:/|$)|^/assets/(?:php|sql|docs)(?:/|$)~', $relative)
    || preg_match('~^/uploads/.*\.(?:php\d*|phtml|phar)$~i', $relative)) {
    http_response_code(404); exit;
}
return false;
