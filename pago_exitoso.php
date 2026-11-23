<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';

$userId = require_login();
$orderId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
if (!$orderId) { http_response_code(400); exit('Pedido inválido.'); }

$statement = db()->prepare('SELECT id_pedido, estado, total, paypal_capture_id FROM pedido WHERE id_pedido=:id AND usr_id=:user LIMIT 1');
$statement->execute(['id' => $orderId, 'user' => $userId]);
$order = $statement->fetch();
if (!$order) { http_response_code(404); exit('Pedido no encontrado.'); }

$itemsStatement = db()->prepare('SELECT nombre_producto, cantidad, precio_unitario FROM pedido_detalle WHERE pedido_id=:order ORDER BY id_detalle');
$itemsStatement->execute(['order' => $orderId]);
$items = $itemsStatement->fetchAll();
$paid = in_array($order['estado'], ['pagado','preparando','enviado','entregado'], true) && !empty($order['paypal_capture_id']);
?>
<!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $paid ? 'Compra confirmada' : 'Pago pendiente' ?> — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/home.css?v=20261118"><link rel="stylesheet" href="assets/css/cart.css?v=20261118"><link rel="stylesheet" href="assets/css/purchase-result.css?v=20261118"></head>
<body><a class="skip-link" href="#contenido">Saltar al contenido</a><?=sideb_public_header('tienda')?><main id="contenido" class="purchase-result container"><section class="purchase-result-card">
<?php if ($paid): ?>
    <div class="purchase-status-icon success" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12 4.2 4.2L19 6.5"/></svg></div>
    <p class="eyebrow">Pago confirmado</p><h1>Tu compra está lista.</h1><p class="purchase-intro">Gracias por apoyar a SIDE B. Recibimos tu pago y prepararemos tu pedido.</p>
    <div class="purchase-summary"><div><span>Pedido</span><strong>#<?= (int) $orderId ?></strong></div><div><span>Total pagado</span><strong><?=cart_money((float) $order['total'])?> MXN</strong></div></div>
    <section class="purchase-items" aria-label="Resumen de compra"><h2>Resumen</h2><?php foreach ($items as $item): ?><div><span><?=e($item['nombre_producto'])?> × <?= (int) $item['cantidad'] ?></span><span><?=cart_money((float) $item['precio_unitario'] * (int) $item['cantidad'])?></span></div><?php endforeach; ?></section>
    <div class="purchase-actions"><a class="button button-primary" href="comprobante.php?pedido=<?=$orderId?>&amp;formato=pdf">Descargar comprobante</a><a class="button button-secondary" href="factura.php?pedido=<?=$orderId?>">Factura de ejemplo</a><a class="button button-secondary" href="pedidos.php">Ver mis pedidos</a></div>
    <p class="purchase-note">Podrás consultar el estado de cada compra desde Mis pedidos.</p>
<?php else: ?>
    <div class="purchase-status-icon failure" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 7l10 10M17 7 7 17"/></svg></div>
    <p class="eyebrow">Pago no confirmado</p><h1>No pudimos confirmar tu compra.</h1><p class="purchase-intro">Tu pedido sigue pendiente. Puedes volver a intentar el pago sin crear un pedido nuevo.</p>
    <div class="purchase-actions"><a class="button button-primary" href="pago.php?pedido=<?=$orderId?>">Intentar pago otra vez</a><a class="button button-secondary" href="carrito.php">Volver al carrito</a></div>
<?php endif; ?>
</section></main><?=sideb_footer()?>
<?php if ($paid && (string) ($_GET['descargar'] ?? '') === '1'): ?>
<script>
(() => {
  const key = 'sideb-receipt-downloaded-<?= (int) $orderId ?>';
  if (sessionStorage.getItem(key)) return;
  sessionStorage.setItem(key, '1');
  const frame = document.createElement('iframe');
  frame.hidden = true;
  frame.src = 'comprobante.php?pedido=<?= (int) $orderId ?>&formato=pdf';
  document.body.appendChild(frame);
  history.replaceState(null, '', 'pago_exitoso.php?pedido=<?= (int) $orderId ?>');
})();
</script>
<?php endif; ?></body></html>
