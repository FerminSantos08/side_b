<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
$userId=require_login(); $database=db(); $error=null;
$profile=$database->prepare('SELECT dir_usr FROM usuario WHERE id_usr=:id'); $profile->execute(['id'=>$userId]); $defaultAddress=(string)($profile->fetchColumn()?:'');
if($_SERVER['REQUEST_METHOD']==='POST'){
    try {
        if(!valid_csrf()) throw new RuntimeException('La sesión del formulario venció.');
        $address=trim((string)($_POST['address']??''));
        if($address===''||strlen($address)>255) throw new RuntimeException('Escribe una dirección de envío válida.');
        $database->beginTransaction(); $items=cart_items_for_user($database,$userId,true);
        if(!$items) throw new RuntimeException('Tu carrito está vacío.');
        $subtotal=0.0;
        foreach($items as $item){ if(!$item['activo']||(int)$item['stock']<(int)$item['cantidad']) throw new RuntimeException('Uno de los productos ya no tiene existencias suficientes.'); $subtotal+=(float)$item['precio']*(int)$item['cantidad']; }
        $shipping=99.0; $discount=0.0; $tax=0.0;
        $total=$subtotal+$shipping-$discount+$tax;
        $database->prepare('INSERT INTO pedido (usr_id,estado,total,subtotal,envio,descuento,impuestos,direccion_envio) VALUES (:user,\'pendiente\',:total,:subtotal,:shipping,:discount,:tax,:address)')->execute(['user'=>$userId,'total'=>$total,'subtotal'=>$subtotal,'shipping'=>$shipping,'discount'=>$discount,'tax'=>$tax,'address'=>$address]);
        $orderId=(int)$database->lastInsertId(); $detail=$database->prepare('INSERT INTO pedido_detalle (pedido_id,producto_id,nombre_producto,cantidad,precio_unitario) VALUES (:order,:product,:name,:quantity,:price)');
        foreach($items as $item) $detail->execute(['order'=>$orderId,'product'=>$item['producto_id'],'name'=>$item['nombre'],'quantity'=>$item['cantidad'],'price'=>$item['precio']]);
        $database->prepare('DELETE FROM carrito_item WHERE usr_id=:user')->execute(['user'=>$userId]); $database->commit(); redirect('pago.php?pedido='.$orderId);
    }catch(Throwable $exception){if($database->inTransaction())$database->rollBack();$error=$exception->getMessage();}
}
$items=cart_items_for_user($database,$userId); $subtotal=array_reduce($items,static fn(float $sum,array $item):float=>$sum+(float)$item['precio']*(int)$item['cantidad'],0.0);
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Checkout — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css"><link rel="stylesheet" href="assets/css/global.css"><link rel="stylesheet" href="assets/css/components.css"><link rel="stylesheet" href="assets/css/home.css"><link rel="stylesheet" href="assets/css/cart.css"></head><body><?=sideb_public_header('tienda')?><main class="cart-page container"><h1>Finalizar compra.</h1><?php if($error):?><p class="editorial-feedback" role="alert"><?=e($error)?></p><?php endif;?><?php if(!$items):?><p>Tu carrito está vacío. <a href="tienda.php">Ir a la tienda</a></p><?php else:?><div class="cart-layout"><form class="checkout-form" method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><h2>Envío</h2><div class="form-field"><label for="address">Dirección completa</label><textarea id="address" name="address" maxlength="255" required><?=e($defaultAddress)?></textarea></div><p>El pedido se creará como pendiente. El pago se confirma de forma segura con PayPal en el siguiente paso.</p><button class="button button-primary" type="submit">Continuar con PayPal →</button></form><aside class="cart-summary"><h2>Resumen</h2><?php foreach($items as $item):?><div class="summary-line"><span><?=e($item['nombre'])?> × <?= (int)$item['cantidad'] ?></span><span><?=cart_money((float)$item['precio']*(int)$item['cantidad'])?></span></div><?php endforeach;?><div class="summary-line"><span>Envío</span><span>$99.00</span></div><div class="summary-line summary-total"><span>Total</span><span><?=cart_money($subtotal+99)?></span></div></aside></div><?php endif;?></main><?=sideb_footer()?></body></html>
