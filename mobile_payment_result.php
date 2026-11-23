<?php
declare(strict_types=1);
require_once __DIR__.'/assets/php/bootstrap.php';
require_once __DIR__.'/assets/php/mobile_payment.php';
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
$orderId=filter_input(INPUT_GET,'pedido',FILTER_VALIDATE_INT) ?: 0;
if(!$orderId){http_response_code(400);exit('Pedido inválido.');}
$userId=mobile_payment_user_id($orderId);
$q=db()->prepare('SELECT estado,paypal_capture_id FROM pedido WHERE id_pedido=:id AND usr_id=:usr');
$q->execute(['id'=>$orderId,'usr'=>$userId]);$order=$q->fetch();
if(!$order){http_response_code(404);exit('Pedido no encontrado.');}
$paid=in_array($order['estado'],['pagado','preparando','enviado','entregado'],true)&&!empty($order['paypal_capture_id']);
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pago SIDE B</title><link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/purchase-result.css?v=20261118"></head><body><main class="container purchase-result"><section class="purchase-result-card"><p class="eyebrow">SIDE B · Pedido #<?=$orderId?></p><h1><?=$paid?'Pago confirmado.':'Tu pago sigue pendiente.'?></h1><p><?=$paid?'Gracias por tu compra. Consulta el pedido desde la app.':'Puedes volver a intentar el pago desde Mis pedidos sin crear otro pedido.'?></p><a class="button button-primary" href="sideb://payment/result?order_id=<?=$orderId?>">Volver a SIDE B</a></section></main></body></html>
