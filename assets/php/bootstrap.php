<?php
declare(strict_types=1);
require_once __DIR__ . '/footer.php';

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
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Usa una imagen JPG, PNG o WebP válida.');
    $folder = trim($folder, '/');
    if (!preg_match('#^[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*$#', $folder)) throw new RuntimeException('Destino de imagen inválido.');
    $directory = dirname(__DIR__, 2) . '/uploads/' . $folder;
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('No fue posible preparar el directorio de imágenes.');
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) throw new RuntimeException('No fue posible guardar la imagen.');
    return 'uploads/' . $folder . '/' . $filename;
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
function logout_control(): string
{
    if (empty($_SESSION['user_id'])) return '';
    $adminPage = in_array(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')), ['backstage.php', 'admin.php', 'editorial.php'], true);
    $class = $adminPage ? 'logout-form logout-form--admin' : 'logout-form';
    $label = $adminPage
        ? '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3M21 3v18"/></svg>Cerrar sesión'
        : '<svg class="logout-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/></svg><span class="logout-icon-label">Cerrar sesión</span>';
    $buttonAttributes = $adminPage ? '' : ' class="logout-icon-button" aria-label="Cerrar sesión" data-tooltip="Cerrar sesión" title="Cerrar sesión"';
    $form = '<form class="' . $class . '" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '"><button type="submit"' . $buttonAttributes . '>' . $label . '</button></form>';
    if (!$adminPage) return $form;

    $isExecutive = is_executive_producer();
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $module = $script === 'admin.php' ? (string) ($_GET['module'] ?? 'productos') : ($script === 'editorial.php' ? 'editorial' : ($script === 'roles.php' ? 'roles' : 'resumen'));
    $items = [['resumen', 'Resumen', 'backstage.php'], ['editorial', 'Editorial', 'editorial.php']];
    if ($isExecutive) {
        $items = array_merge($items, [
            ['productos', 'Productos', 'admin.php?module=productos'], ['categorias', 'Categorías', 'admin.php?module=categorias'],
            ['artistas', 'Artistas', 'admin.php?module=artistas'], ['albumes', 'Álbumes', 'admin.php?module=albumes'],
            ['pedidos', 'Pedidos', 'admin.php?module=pedidos'], ['usuarios', 'Usuarios', 'admin.php?module=usuarios'], ['roles', 'Roles', 'roles.php'],
        ]);
    }
    $icons = [
        'resumen' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/></svg>',
        'editorial' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M4 5h16v14H4zM8 9h8M8 13h5"/></svg>',
        'productos' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M21 8l-9 5-9-5 9-5 9 5zM3 8v8l9 5 9-5V8"/></svg>',
        'categorias' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/></svg>',
        'artistas' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2"/></svg>',
        'albumes' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="2"/><path d="M12 3v9"/></svg>',
        'roles' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3l3 6 6 .9-4.3 4.2 1 5.9-5.7-3-5.7 3 1-5.9L3 9.9 9 9z"/></svg>',
        'pedidos' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M6 7h12l1 13H5L6 7zM9 7a3 3 0 0 1 6 0"/></svg>',
        'usuarios' => '<svg class="admin-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 20v-1a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
    ];
    $links = '';
    foreach ($items as [$key, $text, $href]) $links .= '<a class="nav-link' . ($module === $key ? ' is-active' : '') . '" href="' . e($href) . '">' . ($icons[$key] ?? '') . '<span>' . e($text) . '</span></a>';
    $account = '<div class="admin-account"><strong>' . e((string) ($_SESSION['user_name'] ?? '')) . '</strong><small>' . e((string) ($_SESSION['role_name'] ?? '')) . '</small></div>';
    $javascript = 'document.addEventListener("DOMContentLoaded",function(){var nav=document.querySelector(".site-header .primary-nav"),form=document.querySelector(".site-header .logout-form--admin");if(!nav||!form)return;nav.classList.add("admin-nav");nav.innerHTML=' . json_encode($links, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';var account=document.createElement("div");account.innerHTML=' . json_encode($account, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) . ';account=account.firstChild;form.parentNode.insertBefore(account,form);});';
    $styles = '<style>
      .site-header .admin-nav{align-items:center!important;display:flex!important;gap:1.35rem!important;margin-left:auto!important}
      .site-header .admin-nav .nav-link{align-items:center!important;color:var(--color-muted)!important;display:inline-flex!important;font-size:13px!important;font-weight:500!important;gap:.45rem!important;padding:.85rem 0!important;position:relative!important;white-space:nowrap!important}
      .site-header .admin-nav .nav-link::after{background:var(--color-terracotta)!important;bottom:-.1rem!important;content:""!important;height:2px!important;left:0!important;opacity:0;position:absolute!important;transform:scaleX(.55)!important;transform-origin:left!important;transition:opacity .2s ease,transform .2s ease!important;width:100%!important}
      .site-header .admin-nav .nav-link:hover,.site-header .admin-nav .nav-link.is-active{color:var(--color-ink)!important}.site-header .admin-nav .nav-link:hover::after,.site-header .admin-nav .nav-link.is-active::after{opacity:1!important;transform:scaleX(1)!important}
      .site-header .admin-nav .admin-nav-icon,.site-header .logout-form--admin .admin-nav-icon{fill:none!important;height:16px!important;max-height:16px!important;max-width:16px!important;stroke:currentColor!important;stroke-linecap:round!important;stroke-linejoin:round!important;stroke-width:1.7!important;width:16px!important}
      .site-header .logout-form--admin{display:block!important;margin-left:-.85rem!important}.site-header .logout-form--admin button{align-items:center!important;background:transparent!important;border:0!important;color:var(--color-muted)!important;display:inline-flex!important;font-size:13px!important;font-weight:500!important;gap:.45rem!important;padding:.85rem .2rem!important}.site-header .logout-form--admin button:hover{color:var(--color-terracotta)!important}
      .site-header .admin-account{border-left:1px solid var(--color-border)!important;display:grid!important;gap:.1rem!important;margin-left:.3rem!important;padding-left:.9rem!important}.site-header .admin-account strong{font-size:11px!important}.site-header .admin-account small{color:var(--color-muted)!important;font-size:10px!important}
      .backstage-shell{display:block!important;max-width:980px!important;padding-top:3.5rem!important}.backstage-sidebar{display:none!important}.backstage-content{margin:0 auto!important;max-width:980px!important}
      @media(max-width:1100px){.site-header .admin-nav{gap:.9rem!important;max-width:calc(100vw - 320px)!important;overflow-x:auto!important}}@media(max-width:760px){.site-header .admin-nav{margin-left:0!important;max-width:calc(100vw - 155px)!important;order:3;width:100%}.site-header .admin-account{display:none!important}.header-inner:has(.admin-nav){flex-wrap:wrap!important;gap:.6rem!important;padding:.7rem 0!important}}
    </style>';
    return $form . $styles . '<script>' . $javascript . 'document.addEventListener("DOMContentLoaded",function(){var brand=document.querySelector(".site-header .brand");if(brand)brand.href="backstage.php";});</script><script src="assets/js/admin-product-album.js" defer></script>';
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
function paypal_request(string $method, string $path, ?array $payload = null): array
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
    // PayPal exige este encabezado también en POST sin cuerpo, como la captura de una orden.
    if ($payload !== null || in_array($method, ['POST', 'PUT', 'PATCH'], true)) $headers[] = 'Content-Type: application/json';
    curl_setopt_array($request, [CURLOPT_CUSTOMREQUEST => $method, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 25, CURLOPT_HTTPHEADER => $headers]);
    if ($payload !== null) curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR));
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
    $links = ['inicio' => ['Inicio', 'index.php'], 'artistas' => ['Artistas', 'artistas.php'], 'tienda' => ['Tienda', 'tienda.php']];
    $navIcons = [
        'inicio' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 11.5 12 4l9 7.5V20H3zM9 20v-5h6v5"/></svg>',
        'artistas' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c.5-4 2.8-6 7-6s6.5 2 7 6"/></svg>',
        'tienda' => '<svg class="public-nav-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M5 8h14l1 12H4zM9 8a3 3 0 0 1 6 0"/></svg>',
    ];
    $nav = '';
    foreach ($links as $key => [$label, $href]) $nav .= '<a class="nav-link' . ($active === $key ? ' is-active' : '') . '" href="' . $href . '">' . $navIcons[$key] . '<span>' . $label . '</span></a>';
    $cartCount = cart_count_for_current_user();
    return '<header class="site-header"><div class="container header-inner"><a class="brand" href="index.php" aria-label="SIDE B, inicio">SIDE B<span class="brand-dot">.</span></a><nav class="primary-nav" aria-label="Navegación principal">' . $nav . '</nav><div class="header-actions"><a class="icon-button" href="publicaciones.php" aria-label="Buscar publicaciones"><svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="11" cy="11" r="6"/><path d="m16 16 5 5"/></svg></a><a class="icon-button" href="perfil.php" aria-label="Mi perfil"><svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="3"/><path d="M5 20c.5-4 2.8-6 7-6s6.5 2 7 6"/></svg></a><a class="icon-button cart-button" href="carrito.php" aria-label="Carrito"><svg class="public-action-icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M3 4h2l2 11h10l2-8H6M9 20h.01M17 20h.01"/></svg><span class="cart-count">' . $cartCount . '</span></a></div><button class="menu-button" type="button" aria-label="Abrir menú"><span></span><span></span></button>' . logout_control() . '</div></header>';
}

$sidebPublicPages = ['index.php' => 'inicio', 'artistas.php' => 'artistas', 'artista.php' => 'artistas', 'tienda.php' => 'tienda', 'producto.php' => 'tienda', 'carrito.php' => 'tienda', 'publicaciones.php' => '', 'publicacion.php' => '', 'perfil.php' => '', 'guardados.php' => ''];
$sidebScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (isset($sidebPublicPages[$sidebScript])) {
    $sidebActive = $sidebPublicPages[$sidebScript];
    ob_start(static fn(string $html): string => preg_replace('#<header class="site-header">.*?</header>#s', sideb_public_header($sidebActive), $html, 1) ?? $html);
}

function sideb_favicon_markup(): string
{
    return '<link rel="icon" href="assets/favicons/sideb_sf.png" type="image/png"><link rel="apple-touch-icon" href="assets/favicons/sideb_sf.png"><link rel="manifest" href="assets/favicons/site.webmanifest">';
}

ob_start(static function (string $html): string {
    $html = preg_replace('#<link\\s+rel="(?:icon|apple-touch-icon|manifest)"[^>]*>\\s*#i', '', $html) ?? $html;
    return str_ireplace('</head>', sideb_favicon_markup() . '</head>', $html);
});
