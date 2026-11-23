<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';
require_login();
if (!in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true)) {
    flash('No tienes permisos para acceder a Backstage.');
    redirect('index.php');
}

$executive = is_executive_producer();
if ($executive) {
    $database = db();
    $metrics = $database->query("SELECT
        (SELECT COUNT(*) FROM blog_detalle WHERE estado = 'publicado') published,
        (SELECT COUNT(*) FROM blog_detalle WHERE estado IN ('borrador','revision')) drafts,
        (SELECT COUNT(*) FROM comentarios WHERE estatus = 'visible') comments,
        (SELECT COUNT(*) FROM producto WHERE activo = TRUE AND stock <= 5) inventory,
        (SELECT COUNT(*) FROM pedido WHERE estado IN ('pendiente','pagado','preparando')) orders,
        (SELECT COUNT(*) FROM usuario WHERE activo = TRUE) users")->fetch();
    $modules = [
        ['Pedidos abiertos', $metrics['orders'], 'admin.php?module=pedidos'],
        ['Productos con poco stock', $metrics['inventory'], 'admin.php?module=productos'],
        ['Publicaciones publicadas', $metrics['published'], 'editorial.php'],
        ['Borradores y revisiones', $metrics['drafts'], 'editorial.php'],
        ['Comentarios visibles', $metrics['comments'], 'moderacion.php?estado=visible'],
        ['Usuarios activos', $metrics['users'], 'admin.php?module=usuarios'],
    ];
    $orders = $database->query("SELECT p.id_pedido, p.estado, p.total, u.nom_usr
        FROM pedido p JOIN usuario u ON u.id_usr = p.usr_id
        WHERE p.estado IN ('pendiente','pagado','preparando')
        ORDER BY p.fecha DESC, p.id_pedido DESC LIMIT 4")->fetchAll();
    $orderStates = ['pendiente' => 'Pago pendiente', 'pagado' => 'Pagado', 'preparando' => 'En preparación'];
    $inventory = $database->query('SELECT id_producto, nombre, stock
        FROM producto WHERE activo = TRUE AND stock <= 5 ORDER BY stock, nombre LIMIT 4')->fetchAll();
    $drafts = $database->query("SELECT b.id_blog, b.titulo, d.estado
        FROM blog b JOIN blog_detalle d ON d.blog_id = b.id_blog
        WHERE d.estado IN ('borrador','revision')
        ORDER BY d.estado = 'revision' DESC, d.fecha_actualizacion DESC LIMIT 4")->fetchAll();
    $comments = $database->query("SELECT c.comentario, c.fecha, b.titulo, COALESCE(p.alias, u.nom_usr) AS autor
        FROM comentarios c JOIN blog b ON b.id_blog = c.blog_id
        JOIN usuario u ON u.id_usr = c.usr_id LEFT JOIN perfil_usuario p ON p.usr_id = u.id_usr
        WHERE c.estatus = 'visible' ORDER BY c.fecha DESC, c.id_com DESC LIMIT 4")->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Backstage — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/backstage.css?v=20261120-6">
    <link rel="stylesheet" href="assets/css/admin-navigation.css?v=20261120-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_admin_header() ?>
<main id="contenido" class="backstage-page container">
    <header class="backstage-heading">
        <div>
            <p class="eyebrow"><?= $executive ? 'Administración' : 'Equipo editorial' ?></p>
            <h1>Backstage.</h1>
        </div>
        <p class="backstage-intro"><?= $executive ? 'Consulta el estado de tu contenido, la tienda y las cuentas de SIDE B.' : 'Gestiona tus publicaciones editoriales.' ?></p>
    </header>
    <section class="backstage-shell" aria-label="<?= $executive ? 'Resumen operativo' : 'Publicaciones' ?>">
        <div class="backstage-content">
            <?php if ($executive): ?>
                <section aria-labelledby="metrics-title">
                    <div class="dashboard-section-heading">
                        <h2 id="metrics-title">Estado de SIDE B</h2>
                        <a class="button button-primary" href="editorial.php?view=new">Nueva publicación</a>
                    </div>
                    <div class="dashboard-metrics">
                        <?php foreach ($modules as [$label, $count, $href]): ?>
                            <a class="dashboard-metric" href="<?= e($href) ?>">
                                <span><?= e($label) ?></span>
                                <strong><?= number_format((int) $count) ?></strong>
                                <span class="dashboard-arrow" aria-hidden="true">↗</span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <div class="dashboard-panels">
                    <section class="dashboard-panel" aria-labelledby="orders-title">
                        <div class="dashboard-panel-heading">
                            <h2 id="orders-title">Pedidos por atender</h2>
                            <a class="button button-secondary" href="admin.php?module=pedidos">Ver pedidos</a>
                        </div>
                        <?php if (!$orders): ?><p class="dashboard-empty">No hay pedidos abiertos.</p><?php endif; ?>
                        <ul class="dashboard-list">
                            <?php foreach ($orders as $order): ?>
                                <li><a href="admin.php?module=pedidos&amp;id=<?= (int) $order['id_pedido'] ?>">
                                    <div><strong>Pedido #<?= (int) $order['id_pedido'] ?></strong><span><?= e($order['nom_usr']) ?></span></div>
                                    <div><strong><?= e(cart_money((float) $order['total'])) ?></strong><span><?= e($orderStates[$order['estado']]) ?></span></div>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <section class="dashboard-panel" aria-labelledby="stock-title">
                        <div class="dashboard-panel-heading">
                            <h2 id="stock-title">Inventario bajo</h2>
                            <a class="button button-secondary" href="admin.php?module=productos">Ver productos</a>
                        </div>
                        <?php if (!$inventory): ?><p class="dashboard-empty">No hay productos activos con cinco unidades o menos.</p><?php endif; ?>
                        <ul class="dashboard-list">
                            <?php foreach ($inventory as $product): ?>
                                <li><a href="admin.php?module=productos&amp;id=<?= (int) $product['id_producto'] ?>">
                                    <strong><?= e($product['nombre']) ?></strong>
                                    <span class="dashboard-badge"><?= (int) $product['stock'] === 0 ? 'Agotado' : (int) $product['stock'] . ' en stock' ?></span>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <section class="dashboard-panel" aria-labelledby="drafts-title">
                        <div class="dashboard-panel-heading">
                            <h2 id="drafts-title">Cola editorial</h2>
                            <a class="button button-secondary" href="editorial.php">Ver editorial</a>
                        </div>
                        <?php if (!$drafts): ?><p class="dashboard-empty">No hay publicaciones en borrador o revisión.</p><?php endif; ?>
                        <ul class="dashboard-list">
                            <?php foreach ($drafts as $draft): ?>
                                <li><a href="editorial.php?view=edit&amp;id=<?= (int) $draft['id_blog'] ?>">
                                    <strong><?= e($draft['titulo']) ?></strong>
                                    <span class="dashboard-badge"><?= $draft['estado'] === 'revision' ? 'En revisión' : 'Borrador' ?></span>
                                </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                    <section class="dashboard-panel" aria-labelledby="comments-title">
                        <div class="dashboard-panel-heading">
                            <h2 id="comments-title">Comentarios recientes</h2>
                            <a class="button button-secondary" href="moderacion.php">Moderar</a>
                        </div>
                        <?php if (!$comments): ?><p class="dashboard-empty">No hay comentarios visibles.</p><?php endif; ?>
                        <ul class="dashboard-list dashboard-comments">
                            <?php foreach ($comments as $comment): ?>
                                <li>
                                    <strong><?= e($comment['autor']) ?></strong>
                                    <span><?= e($comment['titulo']) ?> · <?= e(date('d/m/Y', strtotime($comment['fecha']))) ?></span>
                                    <p><?= e($comment['comentario']) ?></p>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                </div>
            <?php else: ?>
                <p class="eyebrow">Contenido</p>
                <h2>Publicaciones<br><em>editoriales.</em></h2>
                <p class="backstage-note">Crea y edita tus publicaciones.</p>
                <a class="button button-primary" href="editorial.php">Gestionar publicaciones</a>
            <?php endif; ?>
        </div>
    </section>
</main>
<?= sideb_footer() ?>
</body>
</html>
