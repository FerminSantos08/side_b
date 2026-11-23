<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';

$userId = require_login();
if (in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true)) redirect('backstage.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $returnUrl = (string) ($_POST['return_url'] ?? 'guardados.php');
    if (!preg_match('#^(guardados\.php|producto\.php\?slug=[a-z0-9-]+|publicacion\.php\?slug=[a-z0-9-]+)$#', $returnUrl)) $returnUrl = 'guardados.php';
    if (!valid_csrf()) { flash('La sesión del formulario venció. Intenta de nuevo.'); redirect($returnUrl); }

    if ($action === 'toggle_product') {
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
        $exists = db()->prepare('SELECT 1 FROM wishlist_item WHERE usr_id=:user AND producto_id=:product');
        $exists->execute(['user' => $userId, 'product' => $productId]);
        if ($exists->fetchColumn()) {
            db()->prepare('DELETE FROM wishlist_item WHERE usr_id=:user AND producto_id=:product')->execute(['user' => $userId, 'product' => $productId]);
            flash('Producto quitado de guardados.');
        } else {
            db()->prepare('INSERT INTO wishlist_item (usr_id, producto_id) SELECT :user, id_producto FROM producto WHERE id_producto=:product AND activo=TRUE')->execute(['user' => $userId, 'product' => $productId]);
            flash('Producto guardado.');
        }
    } elseif ($action === 'toggle_post') {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT) ?: 0;
        $exists = db()->prepare('SELECT 1 FROM guardado_publicacion WHERE usr_id=:user AND blog_id=:post');
        $exists->execute(['user' => $userId, 'post' => $postId]);
        if ($exists->fetchColumn()) {
            db()->prepare('DELETE FROM guardado_publicacion WHERE usr_id=:user AND blog_id=:post')->execute(['user' => $userId, 'post' => $postId]);
            flash('Publicación quitada de guardados.');
        } else {
            db()->prepare("INSERT INTO guardado_publicacion (usr_id, blog_id) SELECT :user, b.id_blog FROM blog b JOIN blog_detalle d ON d.blog_id=b.id_blog WHERE b.id_blog=:post AND d.estado='publicado'")->execute(['user' => $userId, 'post' => $postId]);
            flash('Publicación guardada.');
        }
    }
    redirect($returnUrl);
}

$query = db()->prepare('SELECT u.nom_usr,u.fecha_creacion,COALESCE(p.alias,u.nom_usr) alias,p.avatar_url FROM usuario u LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr WHERE u.id_usr=:id');
$query->execute(['id' => $userId]);
$profile = $query->fetch();
if (!$profile) { session_destroy(); redirect('login.php'); }
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($profile['alias'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($profile['alias'], 0, 1));
$products = db()->prepare('SELECT p.nombre,p.slug,p.imagen,p.precio,w.fecha_guardado FROM wishlist_item w JOIN producto p ON p.id_producto=w.producto_id WHERE w.usr_id=:user AND p.activo=TRUE ORDER BY w.fecha_guardado DESC');
$products->execute(['user' => $userId]);
$products = $products->fetchAll();
$posts = db()->prepare("SELECT b.titulo,b.slug,b.img,g.fecha_guardado FROM guardado_publicacion g JOIN blog b ON b.id_blog=g.blog_id JOIN blog_detalle d ON d.blog_id=b.id_blog WHERE g.usr_id=:user AND d.estado='publicado' ORDER BY g.fecha_guardado DESC");
$posts->execute(['user' => $userId]);
$posts = $posts->fetchAll();
foreach ($posts as &$post) $post['img'] = publication_image_url($post['img']);
unset($post);
$message = flash();
require_once __DIR__ . '/assets/php/account_layout.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Guardados — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile-menu-icons.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile-edit.css?v=20261119-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('') ?>
<main id="contenido" class="profile-page account-page container">
    <header class="account-heading">
        <p class="eyebrow">Tu colección personal</p>
        <h1>Guardados<em>.</em></h1>
    </header>
    <?php if ($message): ?><p class="editorial-feedback" role="status"><?= e($message) ?></p><?php endif; ?>
    <div class="profile-shell account-layout">
        <?php sideb_account_identity($profile, $initial, 'guardados'); ?>
        <div class="account-list">
            <?php if (!$products && !$posts): ?>
                <section class="account-card">
                    <h2>Tu colección está vacía</h2>
                    <p class="account-empty">Aquí aparecerán las publicaciones y los productos que guardes.</p>
                    <div class="account-actions">
                        <a class="button button-primary" href="publicaciones.php">Explorar publicaciones</a>
                        <a class="button button-secondary" href="tienda.php">Ver tienda</a>
                    </div>
                </section>
            <?php endif; ?>
            <?php foreach (['publicaciones' => $posts, 'productos' => $products] as $category => $items): ?>
                <?php if (!$items) continue; ?>
                <?php $isPublication = $category === 'publicaciones'; ?>
                <section class="account-card" aria-labelledby="saved-<?= e($category) ?>">
                    <h2 id="saved-<?= e($category) ?>"><?= $isPublication ? 'Publicaciones' : 'Productos' ?></h2>
                    <ul class="account-saved-grid" role="list">
                        <?php foreach ($items as $item): ?>
                            <?php
                            $image = $isPublication ? $item['img'] : $item['imagen'];
                            $title = $isPublication ? $item['titulo'] : $item['nombre'];
                            $url = ($isPublication ? 'publicacion.php?slug=' : 'producto.php?slug=') . rawurlencode($item['slug']);
                            ?>
                            <li>
                                <a class="account-saved-item" href="<?= e($url) ?>">
                                    <?php if ($image): ?><img src="<?= e($image) ?>" alt="" width="72" height="72" loading="lazy"><?php endif; ?>
                                    <div>
                                        <h3><?= e($title) ?></h3>
                                        <time datetime="<?= e($item['fecha_guardado']) ?>">Guardado el <?= e(date('d/m/Y', strtotime($item['fecha_guardado']))) ?></time>
                                        <?php if (!$isPublication): ?><strong><?= e(cart_money((float) $item['precio'])) ?> MXN</strong><?php endif; ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?= sideb_footer() ?>
</body>
</html>
