<?php
declare(strict_types=1);
require_once __DIR__ . '/footer.php';
require_once __DIR__ . '/images.php';

/* Las credenciales se leen del entorno: DB_HOST, DB_PORT, DB_NAME, DB_USER y DB_PASSWORD. */
/* En desarrollo local también se leen desde el archivo .env en la raíz del proyecto. */
function load_local_env(): void
{
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = dirname(__DIR__, 2) . '/.env';
    if (!is_readable($envFile)) return;

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) || getenv($key) !== false) continue;
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

load_local_env();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) return $connection;

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: '3306';
    $name = getenv('DB_NAME') ?: 'side_b';
    $user = getenv('DB_USER') ?: 'scored';
    $password = getenv('DB_PASSWORD');
    if ($password === false || $password === '') throw new RuntimeException('Falta configurar DB_PASSWORD en el entorno del servidor.');
    $connection = new PDO(
        "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
        $user,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
    return $connection;
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function artist_slug(string $name): string
{
    $value = trim($name);
    $value = function_exists('iconv') ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    return trim($value, '-');
}

function album_url(string $artist, string $title): string
{
    return 'album.php?' . http_build_query(['artista' => $artist, 'album' => $title], '', '&', PHP_QUERY_RFC3986);
}

function publication_image_url(?string $filename): ?string
{
    $filename = trim((string) $filename);
    if ($filename === '' || basename($filename) !== $filename || str_contains($filename, '..')) return null;

    $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) return null;

    return 'uploads/publicaciones/' . rawurlencode($filename);
}

/* blog.tags es texto libre; la etiqueta visible usa blog_artista -> artista. */
function editorial_uppercase(string $value): string
{
    return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
}

function publication_editorial_label(?string $artistName): string
{
    $artistName = trim((string) $artistName);
    return 'SIDE B / ' . editorial_uppercase($artistName !== '' ? $artistName : 'Editorial');
}

function publication_editorial_kicker(?string $artistName): string
{
    $artistName = trim((string) $artistName);
    return editorial_uppercase($artistName !== '' ? $artistName : 'Editorial');
}

/* Images stored by the web back office are always generated server-side names. */
function upload_backstage_image(array $file, string $folder, ?string $previous = null): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return $previous;
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
        throw new RuntimeException('La imagen debe pesar como máximo 5 MB.');
    }
    $source = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($source)) throw new RuntimeException('La subida de imagen no es válida.');
    $folder = trim($folder, '/');
    if (!preg_match('#^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$#', $folder)) throw new RuntimeException('Destino de imagen inválido.');
    $directory = dirname(__DIR__, 2) . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) throw new RuntimeException('No fue posible preparar el directorio de imágenes.');
    $filename = bin2hex(random_bytes(16)) . '.webp';
    convert_image_to_webp($source, $directory . '/' . $filename, $folder === 'perfiles' ? 512 : 1920);
    return 'uploads/' . $folder . '/' . $filename;
}

function upload_profile_avatar(array $file, ?string $previous = null): ?string
{
    return upload_backstage_image($file, 'perfiles', $previous);
}

function redirect(string $path): never { header('Location: ' . $path); exit; }
function csrf_token(): string { return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32)); }
function valid_csrf(): bool { return isset($_POST['csrf_token']) && hash_equals($_SESSION['csrf_token'] ?? '', (string) $_POST['csrf_token']); }
function flash(?string $message = null): ?string { if ($message !== null) { $_SESSION['flash_message'] = $message; return null; } $saved = $_SESSION['flash_message'] ?? null; unset($_SESSION['flash_message']); return $saved; }
function require_login(): int
{
    if (empty($_SESSION['user_id'])) { flash('Inicia sesión para acceder a tu perfil.'); redirect('login.php'); }
    return (int) $_SESSION['user_id'];
}
function logout_control(bool $adminPage = false): string
{
    if (empty($_SESSION['user_id'])) return '';
    $class = $adminPage ? 'logout-form logout-form--admin' : 'logout-form';
    $label = $adminPage
        ? '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M21 3v18"/></svg>Cerrar sesión'
        : '<svg class="logout-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg><span class="logout-icon-label">Cerrar sesión</span>';
    $buttonAttributes = $adminPage ? '' : ' class="logout-icon-button" aria-label="Cerrar sesión" data-tooltip="Cerrar sesión" title="Cerrar sesión"';
    return '<form class="' . $class . '" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><button type="submit"' . $buttonAttributes . '>' . $label . '</button></form>';
}

function sideb_admin_header(): string
{
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $module = match ($script) {
        'admin.php' => (string) ($_GET['module'] ?? 'productos'),
        'editorial.php' => 'editorial',
        'moderacion.php' => 'comentarios',
        'roles.php' => 'roles',
        default => 'resumen',
    };
    $hasOverview = in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true);
    $home = $hasOverview ? 'backstage.php' : 'editorial.php';
    $icons = [
        'resumen' => '<path d="M4 4h6v6H4zm10 0h6v6h-6zM4 14h6v6H4zm10 0h6v6h-6z"/>',
        'contenido' => '<path d="M4 5h16v14H4zM8 9h8m-8 4h6"/>',
        'tienda' => '<path d="M5 8h14l1 12H4zM9 8a3 3 0 0 1 6 0"/>',
        'acceso' => '<path d="M12 22s7-3 7-11V5l-7-3-7 3v6c0 8 7 11 7 11zM9 12l2 2 4-4"/>',
        'cuenta' => '<circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    ];
    $icon = static fn (string $key): string => isset($icons[$key])
        ? '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24">' . $icons[$key] . '</svg>'
        : '';
    $link = static function (string $key, string $label, string $href) use ($module, $icon): string {
        $active = $module === $key;
        return '<a class="admin-link' . ($active ? ' is-active' : '') . '"'
            . ($active ? ' aria-current="page"' : '') . ' href="' . e($href) . '">' . $icon($key) . e($label) . '</a>';
    };
    $group = static function (string $label, array $items) use ($link, $module, $icon): string {
        $active = in_array($module, array_column($items, 0), true);
        $links = '';
        foreach ($items as [$key, $text, $href]) {
            $links .= $link($key, $text, $href);
        }
        return '<details class="admin-menu"><summary class="admin-link' . ($active ? ' is-active' : '')
            . '">' . $icon(strtolower($label)) . e($label) . '</summary><div class="admin-menu-panel">' . $links . '</div></details>';
    };
    $navigation = $hasOverview ? $link('resumen', 'Resumen', 'backstage.php') : '';
    if (is_executive_producer()) {
        $navigation .= $group('Contenido', [
            ['editorial', 'Publicaciones', 'editorial.php'],
            ['comentarios', 'Comentarios', 'moderacion.php'],
            ['artistas', 'Artistas', 'admin.php?module=artistas'],
            ['albumes', 'Álbumes', 'admin.php?module=albumes'],
        ]);
        $navigation .= $group('Tienda', [
            ['productos', 'Productos', 'admin.php?module=productos'],
            ['categorias', 'Categorías', 'admin.php?module=categorias'],
            ['pedidos', 'Pedidos', 'admin.php?module=pedidos'],
        ]);
        $navigation .= $group('Acceso', [
            ['usuarios', 'Usuarios', 'admin.php?module=usuarios'],
            ['roles', 'Roles y permisos', 'roles.php'],
        ]);
    } else {
        $navigation .= $link('editorial', 'Publicaciones', 'editorial.php');
    }
    $account = '<details class="admin-menu admin-account"><summary class="admin-link">' . $icon('cuenta') . 'Cuenta</summary>'
        . '<div class="admin-menu-panel"><div class="admin-account-name"><strong>'
        . e((string) ($_SESSION['user_name'] ?? '')) . '</strong><small>'
        . e((string) ($_SESSION['role_name'] ?? '')) . '</small></div>'
        . '<a class="admin-link" href="index.php">Ver sitio <span aria-hidden="true">↗</span></a>'
        . logout_control(true) . '</div></details>';

    return '<header class="site-header admin-header"><div class="container header-inner"><a class="brand" href="' . $home
        . '" aria-label="SIDE B, Backstage">SIDE B<span class="brand-dot">.</span></a>'
        . '<nav class="admin-nav" aria-label="Administración">' . $navigation . '</nav>'
        . $account . '</div></header><script src="assets/js/admin-navigation.js?v=20261120" defer></script>'
        . ($script === 'admin.php' ? '<script src="assets/js/admin-product-album.js" defer></script>' : '');
}

function sideb_profile_menu(string $active): string
{
    $items = [
        'perfil' => ['perfil.php', 'Datos personales', '<svg class="profile-menu-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="5"/><path d="M20 21a8 8 0 0 0-16 0"/></svg>'],
        'guardados' => ['guardados.php', 'Guardados', '<svg class="profile-menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>'],
        'pedidos' => ['pedidos.php', 'Mis pedidos', '<svg class="profile-menu-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5M12 22V12"/></svg>'],
    ];
    $menu = '<nav class="profile-menu" aria-label="Menú de perfil">';
    foreach ($items as $key => [$href, $label, $icon]) {
        $isActive = $active === $key;
        $menu .= '<a' . ($isActive ? ' class="is-active" aria-current="page"' : '') . ' href="' . $href . '">' . $icon . '<span>' . $label . '</span></a>';
    }
    return $menu . '</nav>';
}
/** @return list<array<string,mixed>> */
function cart_items_for_user(PDO $database, int $userId, bool $lock = false): array
{
    $statement = $database->prepare(
        'SELECT ci.id_item, ci.producto_id, ci.cantidad, p.nombre, p.precio, p.stock, p.activo,
                p.imagen, p.slug, c.nombre AS categoria
         FROM carrito_item AS ci
         INNER JOIN producto AS p ON p.id_producto = ci.producto_id
         INNER JOIN categoria_producto AS c ON c.id_categoria = p.categoria_id
         WHERE ci.usr_id = :user
         ORDER BY ci.fecha_agregado DESC' . ($lock ? ' FOR UPDATE' : '')
    );
    $statement->execute(['user' => $userId]);
    return $statement->fetchAll();
}

function cart_count_for_current_user(): int
{
    if (empty($_SESSION['user_id'])) return 0;
    try {
        $statement = db()->prepare('SELECT COALESCE(SUM(cantidad), 0) FROM carrito_item WHERE usr_id = :user');
        $statement->execute(['user' => (int) $_SESSION['user_id']]);
        return (int) $statement->fetchColumn();
    } catch (Throwable) {
        return 0;
    }
}

function cart_money(float $amount): string
{
    return '$' . number_format($amount, 2);
}

function paypal_config(): array
{
    $clientId = (string) (getenv('PAYPAL_CLIENT_ID') ?: '');
    $secret = (string) (getenv('PAYPAL_CLIENT_SECRET') ?: '');
    $mode = strtolower((string) (getenv('PAYPAL_MODE') ?: 'sandbox'));
    if ($clientId === '' || $secret === '') throw new RuntimeException('PayPal no está configurado en el servidor.');
    return ['client_id' => $clientId, 'secret' => $secret, 'base_url' => $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com'];
}

/** @return array<string,mixed> */
function paypal_request(string $method, string $path, ?array $payload = null, ?string $requestId = null): array
{
    if (!function_exists('curl_init')) throw new RuntimeException('El servidor no tiene habilitada la extensión cURL. Reinicia Apache después de configurar PHP.');
    $config = paypal_config();
    $tokenRequest = curl_init($config['base_url'] . '/v1/oauth2/token');
    curl_setopt_array($tokenRequest, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => 'grant_type=client_credentials', CURLOPT_HTTPAUTH => CURLAUTH_BASIC, CURLOPT_USERPWD => $config['client_id'] . ':' . $config['secret'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: es_MX']]);
    $tokenBody = curl_exec($tokenRequest); $tokenCode = (int) curl_getinfo($tokenRequest, CURLINFO_RESPONSE_CODE);
    $tokenError = curl_error($tokenRequest);
    curl_close($tokenRequest);
    $token = json_decode((string) $tokenBody, true);
    if ($tokenCode < 200 || $tokenCode >= 300 || empty($token['access_token'])) {
        error_log('PayPal OAuth failed: HTTP ' . $tokenCode . ($tokenError !== '' ? '; cURL: ' . $tokenError : ''));
        throw new RuntimeException('No fue posible autenticar la conexión con PayPal.');
    }
    $request = curl_init($config['base_url'] . $path);
    $headers = ['Authorization: Bearer ' . $token['access_token'], 'Accept: application/json'];
    if ($requestId !== null) $headers[] = 'PayPal-Request-Id: ' . $requestId;
    // PayPal exige este encabezado también en POST sin cuerpo, como la captura de una orden.
    if ($payload !== null || in_array($method, ['POST', 'PUT', 'PATCH'], true)) $headers[] = 'Content-Type: application/json';
    curl_setopt_array($request, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => $headers]);
    if ($payload !== null) curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
    elseif ($method === 'POST') curl_setopt($request, CURLOPT_POSTFIELDS, '{}');
    $body = curl_exec($request); $code = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    $requestError = curl_error($request);
    curl_close($request);
    $response = json_decode((string) $body, true);
    if ($code < 200 || $code >= 300 || !is_array($response)) {
        $paypalName = is_array($response) ? (string) ($response['name'] ?? 'UNKNOWN_ERROR') : 'INVALID_RESPONSE';
        $debugId = is_array($response) ? (string) ($response['debug_id'] ?? '') : '';
        $issue = is_array($response) ? (string) ($response['details'][0]['issue'] ?? '') : '';
        error_log('PayPal API failed: HTTP ' . $code . '; name=' . $paypalName . '; issue=' . $issue . '; debug_id=' . $debugId . ($requestError !== '' ? '; cURL: ' . $requestError : ''));

        if ($issue === 'INSTRUMENT_DECLINED') throw new RuntimeException('PAYPAL_INSTRUMENT_DECLINED');
        throw new RuntimeException('PayPal rechazó la operación (' . $paypalName . ($issue !== '' ? ': ' . $issue : '') . ').' . ($debugId !== '' ? ' Referencia: ' . $debugId . '.' : ''));
    }
    return $response;
}

function is_executive_producer(): bool { return (int) ($_SESSION['role_id'] ?? 0) === 3; }
function is_editorial_user(): bool { return in_array((int) ($_SESSION['role_id'] ?? 0), [2, 3, 4], true); }
function is_editor(): bool { return (int) ($_SESSION['role_id'] ?? 0) === 4; }

function require_executive_producer(): void
{
    if (empty($_SESSION['user_id'])) { flash('Inicia sesión para acceder a Backstage.'); redirect('login.php'); }
    if (!is_executive_producer()) { flash('No tienes permisos para acceder a Backstage.'); redirect('index.php'); }
}

function require_editorial_user(): void
{
    if (empty($_SESSION['user_id'])) { flash('Inicia sesión para administrar publicaciones.'); redirect('login.php'); }
    if (!is_editorial_user()) { flash('No tienes permisos para administrar publicaciones.'); redirect('index.php'); }
}

function sideb_public_header(string $active): string
{
    $page = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $currentSearch = $page === 'buscar.php' ? ' aria-current="page"' : '';
    $currentAccount = in_array($page, ['perfil.php', 'guardados.php', 'pedidos.php', 'login.php', 'registro.php'], true)
        ? ' aria-current="page"' : '';
    $currentCart = in_array($page, ['carrito.php', 'checkout.php', 'pago.php', 'pago_exitoso.php'], true)
        ? ' aria-current="page"' : '';
    $links = [
        'inicio' => ['Inicio', 'index.php'],
        'artistas' => ['Artistas', 'artistas.php'],
        'publicaciones' => ['Publicaciones', 'publicaciones.php'],
        'tienda' => ['Tienda', 'tienda.php'],
    ];
    $navIcons = [
        'publicaciones' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h8M8 16h4"/></svg>',
        'inicio' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5V20H3zM9 20v-5h6v5"/></svg>',
        'artistas' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c.5-4 2.8-6 7-6s6.5 2 7 6"/></svg>',
        'tienda' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 8h14l1 12H4zM9 8a3 3 0 0 1 6 0"/></svg>',
    ];
    $nav = '';
    foreach ($links as $key => [$label, $href]) {
        $current = $active === $key;
        $nav .= '<a class="nav-link' . ($current ? ' is-active' : '') . '" href="' . $href . '"'
            . ($current ? ' aria-current="' . ($page === $href ? 'page' : 'location') . '"' : '')
            . '>' . $navIcons[$key] . '<span>' . $label . '</span></a>';
    }
    $cartCount = cart_count_for_current_user();
    return '<header class="site-header public-header"><div class="container header-inner">'
        . '<a class="brand" href="index.php" aria-label="SIDE B, inicio">SIDE B<span class="brand-dot">.</span></a>'
        . '<nav id="sideb-navigation" class="primary-nav" aria-label="Navegación principal">' . $nav . '</nav>'
        . '<div class="header-actions"><a class="icon-button" href="buscar.php" data-search-open aria-label="Buscar en SIDE B"' . $currentSearch . '>'
        . '<svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="m16 16 5 5"/></svg></a>'
        . '<a class="icon-button" href="perfil.php" aria-label="Mi perfil"' . $currentAccount . '>'
        . '<svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c.5-4 2.8-6 7-6s6.5 2 7 6"/></svg></a>'
        . '<a class="icon-button cart-button" href="carrito.php" aria-label="Carrito"' . $currentCart . '>'
        . '<svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 4h2l2 11h10l2-8H6M9 20h.01M17 20h.01"/></svg>'
        . '<span class="cart-count">' . $cartCount . '</span></a>' . logout_control() . '</div>'
        . '<button class="menu-button" type="button" aria-label="Menú principal" aria-controls="sideb-navigation" aria-expanded="false"><span></span><span></span></button>'
        . '</div>' . sideb_search_dropdown() . '</header>'
        . '<script src="assets/js/navigation.js?v=20261118" defer></script>'
        . '<script src="assets/js/search-overlay.js?v=20261119-2" defer></script>';
}

function sideb_search_dropdown(): string
{
    return <<<'HTML'
<link rel="stylesheet" href="assets/css/search-overlay.css?v=20261120-1">
<div class="search-dropdown" id="sideb-search" hidden>
    <form class="container search-dropdown-form" role="search" aria-label="Búsqueda rápida" action="buscar.php" method="get">
        <div class="search-dropdown-field">
            <input class="field-control" id="sideb-search-query" name="q" type="search" placeholder="Artistas, álbumes, publicaciones o productos" aria-label="Buscar en SIDE B" maxlength="100" autocomplete="off">
            <button class="search-dropdown-submit" type="submit" aria-label="Buscar">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 5 5"></path></svg>
            </button>
            <button class="search-dropdown-close" type="button" aria-label="Cerrar búsqueda">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"></path></svg>
            </button>
        </div>
        <div class="search-dropdown-panel">
            <p class="search-dropdown-status" role="status">Escribe al menos dos caracteres.</p>
            <div class="search-dropdown-results" aria-label="Resultados rápidos"></div>
            <button class="search-dropdown-all" type="submit">Ver todos los resultados <span aria-hidden="true">↗</span></button>
        </div>
    </form>
</div>
HTML;
}

function sideb_favicon_markup(): string
{
    return '<link rel="icon" href="assets/favicons/sideb_sf.png" type="image/png"><link rel="apple-touch-icon" href="assets/favicons/sideb_sf.png"><link rel="manifest" href="assets/favicons/site.webmanifest">';
}

ob_start(static function (string $html): string {
    $html = preg_replace('#<link\\s+rel="(?:icon|apple-touch-icon|manifest)"[^>]*>\\s*#i', '', $html) ?? $html;
    return str_ireplace('</head>', sideb_favicon_markup() . '</head>', $html);
});
