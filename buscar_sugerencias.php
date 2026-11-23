<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__ . '/assets/php/search.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();

$query = sideb_search_query($_GET['q'] ?? '');
$groups = [];
if (preg_match('/^.{2}/us', $query)) {
    try {
        foreach (sideb_search_categories() as $key => $label) {
            if ($key === 'autores') continue;
            $items = sideb_search_results(db(), $query, $key, 4);
            if ($items) $groups[] = ['key' => $key, 'label' => $label, 'items' => $items];
        }
    } catch (Throwable $exception) {
        error_log('[SIDE B búsqueda] ' . $exception->getMessage());
        http_response_code(500);
        echo '{"groups":[],"error":"No fue posible obtener sugerencias."}';
        exit;
    }
}
echo json_encode(['groups' => $groups], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
