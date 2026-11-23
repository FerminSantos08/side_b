<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';

$userId = require_login();
if (in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true)) redirect('backstage.php');

$profileQuery = db()->prepare('SELECT u.nom_usr,u.fecha_creacion,COALESCE(p.alias,u.nom_usr) alias,p.avatar_url FROM usuario u LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr WHERE u.id_usr=:id');
$profileQuery->execute(['id' => $userId]);
$profile = $profileQuery->fetch();
if (!$profile) { session_destroy(); redirect('login.php'); }
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($profile['alias'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($profile['alias'], 0, 1));

$ordersQuery = db()->prepare('SELECT id_pedido,fecha,fecha_pago,estado,total,direccion_envio,paypal_capture_id FROM pedido WHERE usr_id=:user ORDER BY fecha DESC,id_pedido DESC');
$ordersQuery->execute(['user' => $userId]);
$orders = $ordersQuery->fetchAll();

$details = [];
if ($orders) {
    $detailsQuery = db()->prepare('SELECT d.pedido_id,d.nombre_producto,d.cantidad,d.precio_unitario,p.imagen FROM pedido_detalle d LEFT JOIN producto p ON p.id_producto=d.producto_id INNER JOIN pedido o ON o.id_pedido=d.pedido_id WHERE o.usr_id=:user ORDER BY d.pedido_id,d.id_detalle');
    $detailsQuery->execute(['user' => $userId]);
    foreach ($detailsQuery->fetchAll() as $detail) $details[(int) $detail['pedido_id']][] = $detail;
}

$statusLabels = ['pendiente' => 'Pago pendiente', 'pagado' => 'Pago confirmado', 'preparando' => 'Preparando pedido', 'enviado' => 'En camino', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
$paidStates = ['pagado', 'preparando', 'enviado', 'entregado'];
require_once __DIR__ . '/assets/php/account_layout.php';
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mis pedidos — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/profile.css?v=20261118"><link rel="stylesheet" href="assets/css/orders.css?v=20261119"><link rel="stylesheet" href="assets/css/profile-menu-icons.css?v=20261118"><link rel="stylesheet" href="assets/css/profile-edit.css?v=20261119-2"></head>
<body><a class="skip-link" href="#contenido">Saltar al contenido</a><?=sideb_public_header('')?><main id="contenido" class="profile-page account-page container">
<header class="account-heading"><p class="eyebrow">Compras SIDE B</p><h1 id="orders-title">Mis pedidos<em>.</em></h1></header>
<div class="profile-shell account-layout">
<?php sideb_account_identity($profile, $initial, 'pedidos'); ?>
<section class="account-list account-orders" aria-labelledby="orders-title">
<?php if(!$orders):?><div class="account-card"><h2>Aún no tienes pedidos</h2><p class="account-empty">Tus compras aparecerán aquí.</p><a class="button button-primary" href="tienda.php">Explorar tienda</a></div><?php else:?><div class="order-list"><?php foreach($orders as $order):$orderId=(int)$order['id_pedido'];$orderItems=$details[$orderId]??[];$itemCount=array_sum(array_map(static fn(array $item):int=>(int)$item['cantidad'],$orderItems));?><article class="order-card account-card"><header class="order-card-header"><div><p class="order-kicker">Pedido #<?= $orderId ?></p><h3>Realizado el <?=e(date('d \d\e F, Y',strtotime($order['fecha'])))?></h3></div><span class="order-status order-status--<?=e($order['estado'])?>"><?=e($statusLabels[$order['estado']] ?? $order['estado'])?></span></header><div class="order-products"><?php foreach($orderItems as $item):?><div class="order-product"><div class="order-product-image"><?php if(!empty($item['imagen'])):?><img src="<?=e($item['imagen'])?>" alt=""><?php else:?><span aria-hidden="true">SB</span><?php endif;?></div><div class="order-product-info"><strong><?=e($item['nombre_producto'])?></strong><span><?= (int)$item['cantidad'] ?> × <?=cart_money((float)$item['precio_unitario'])?> MXN</span></div><strong class="order-product-total"><?=cart_money((float)$item['precio_unitario']*(int)$item['cantidad'])?></strong></div><?php endforeach;?></div><footer class="order-card-footer"><div><span><?= $itemCount ?> <?= $itemCount===1?'artículo':'artículos' ?></span><?php if($order['direccion_envio']):?><span class="order-address">Envío: <?=e($order['direccion_envio'])?></span><?php endif;?></div><div class="order-total"><span>Total</span><strong><?=cart_money((float)$order['total'])?> MXN</strong></div></footer><div class="order-actions"><?php if($order['estado']==='pendiente'):?><a class="button button-primary" href="pago.php?pedido=<?=$orderId?>">Completar pago</a><?php elseif($order['estado']==='pagado'):?><a class="button button-secondary" href="pago_exitoso.php?pedido=<?=$orderId?>">Ver confirmación</a><a class="order-text-link" href="comprobante.php?pedido=<?=$orderId?>">Ver comprobante</a><?php elseif(in_array($order['estado'],$paidStates,true)):?><span class="order-paid-note">Pago confirmado<?= $order['fecha_pago'] ? ' · '.e(date('d/m/Y',strtotime($order['fecha_pago']))):'' ?></span><?php else:?><span class="order-paid-note">No hay acciones disponibles para este pedido.</span><?php endif;?><?php if(in_array($order['estado'],$paidStates,true) && !empty($order['paypal_capture_id'])):?><a class="order-text-link" href="factura.php?pedido=<?=$orderId?>">Factura de ejemplo</a><?php endif;?></div></article><?php endforeach;?></div><?php endif;?></section></div></main><?=sideb_footer()?></body></html>
