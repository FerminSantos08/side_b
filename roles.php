<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
require_executive_producer();
$roles = db()->query('SELECT r.id_rol,r.nombre,r.descripcion,COUNT(u.id_usr) AS usuarios FROM rol r LEFT JOIN usuario u ON u.tip_usu=r.id_rol GROUP BY r.id_rol,r.nombre,r.descripcion ORDER BY r.id_rol')->fetchAll();
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Roles y permisos — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/backstage.css?v=20261120-6"><link rel="stylesheet" href="assets/css/admin-navigation.css?v=20261120-2"></head><body>
<?= sideb_admin_header() ?>
<main class="backstage-page container"><div class="backstage-heading"><div><p class="eyebrow">Administración</p><h1>Roles y<br><em>permisos.</em></h1></div><p class="backstage-intro">Los roles son reglas fijas del servidor. Desde Usuarios puedes asignarlos de forma segura.</p></div><section class="backstage-content"><div class="editorial-list" aria-label="Roles del sistema"><?php foreach($roles as $role): ?><article><div><p class="card-kicker">ROL <?= (int)$role['id_rol'] ?></p><h2><?= e($role['nombre']) ?></h2><p><?= e((string)$role['descripcion']) ?> · <?= (int)$role['usuarios'] ?> usuarios</p></div><a class="text-link" href="admin.php?module=usuarios">Gestionar usuarios →</a></article><?php endforeach; ?></div></section></main><?= sideb_footer() ?></body></html>
