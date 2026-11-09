<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
$userId = require_login();
$database = db(); $error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!valid_csrf()) throw new RuntimeException('La sesión del formulario venció.');
        $action = (string) ($_POST['action'] ?? '');
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if (!$productId) throw new RuntimeException('El producto seleccionado no es válido.');
        if ($action === 'add') {
            $database->beginTransaction();
            $statement = $database->prepare('SELECT id_producto, stock, activo FROM producto WHERE id_producto = :id FOR UPDATE');
            $statement->execute(['id' => $productId]); $product = $statement->fetch();
            if (!$product || !$product['activo'] || (int) $product['stock'] < 1) throw new RuntimeException('Este producto ya no está disponible.');
            $database->prepare('INSERT INTO carrito_item (usr_id, producto_id, cantidad) VALUES (:user, :product, 1) ON DUPLICATE KEY UPDATE cantidad = LEAST(cantidad + 1, :stock)')->execute(['user' => $userId, 'product' => $productId, 'stock' => (int) $product['stock']]);
            $database->commit(); flash('Producto añadido al carrito.'); redirect('carrito.php');
        }
        if ($action === 'update') {
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            if (!$quantity || $quantity < 1) throw new RuntimeException('La cantidad no es válida.');
            $statement = $database->prepare('UPDATE carrito_item ci INNER JOIN producto p ON p.id_producto = ci.producto_id SET ci.cantidad = LEAST(:quantity, p.stock) WHERE ci.usr_id = :user AND ci.producto_id = :product AND p.activo = TRUE AND p.stock > 0');
            $statement->execute(['quantity' => $quantity, 'user' => $userId, 'product' => $productId]);
            redirect('carrito.php');
        }
        if ($action === 'remove') { $database->prepare('DELETE FROM carrito_item WHERE usr_id=:user AND producto_id=:product')->execute(['user'=>$userId,'product'=>$productId]); flash('Producto eliminado del carrito.'); redirect('carrito.php'); }
        throw new RuntimeException('Acción no válida.');
    } catch (Throwable $exception) { if ($database->inTransaction()) $database->rollBack(); $error = $exception->getMessage(); }
}
$items=cart_items_for_user($database,$userId); $subtotal=array_reduce($items,static fn(float $sum,array $item):float=>$sum+(float)$item['precio']*(int)$item['cantidad'],0.0); $shipping=$items?99.0:0.0;
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Carrito — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css"><link rel="stylesheet" href="assets/css/global.css"><link rel="stylesheet" href="assets/css/components.css"><link rel="stylesheet" href="assets/css/home.css"><link rel="stylesheet" href="assets/css/shop.css"><link rel="stylesheet" href="assets/css/cart.css"></head><body><?=sideb_public_header('tienda')?><main class="cart-page container"><h1>Tu carrito.</h1><?php if($error):?><p class="editorial-feedback" role="alert"><?=e($error)?></p><?php endif;?><?php if($message=flash()):?><p class="editorial-feedback is-success"><?=e($message)?></p><?php endif;?><div class="cart-layout"><section class="cart-items" aria-label="Productos en el carrito"><?php foreach($items as $item):?><article class="cart-item"><a class="cart-thumb visual-surface visual-surface--record" href="producto.php?slug=<?=rawurlencode($item['slug'])?>"><?php if($item['imagen']):?><img src="<?=e($item['imagen'])?>" alt=""><?php endif;?></a><div><h2><?=e($item['nombre'])?></h2><p><?=e($item['categoria'])?></p><div class="cart-controls"><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?=(int)$item['producto_id']?>"><input type="hidden" name="quantity" value="<?=max(1,(int)$item['cantidad']-1)?>"><button type="submit" aria-label="Reducir cantidad">−</button></form><span><?= (int)$item['cantidad'] ?></span><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="update"><input type="hidden" name="product_id" value="<?=(int)$item['producto_id']?>"><input type="hidden" name="quantity" value="<?=(int)$item['cantidad']+1?>"><button type="submit" aria-label="Aumentar cantidad">+</button></form><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="remove"><input type="hidden" name="product_id" value="<?=(int)$item['producto_id']?>"><button class="cart-remove" type="submit" aria-label="Eliminar <?=e($item['nombre'])?>">×</button></form></div></div><strong class="cart-price"><?=cart_money((float)$item['precio']*(int)$item['cantidad'])?></strong></article><?php endforeach;?><?php if(!$items):?><p class="cart-empty">Tu carrito está vacío. <a href="tienda.php">Explora la tienda.</a></p><?php endif;?></section><aside class="cart-summary"><h2>Resumen</h2><div class="summary-line"><span>Subtotal</span><span><?=cart_money($subtotal)?></span></div><div class="summary-line"><span>Envío</span><span><?=cart_money($shipping)?></span></div><div class="summary-line summary-total"><span>Total</span><span><?=cart_money($subtotal+$shipping)?></span></div><?php if($items):?><a class="button button-primary summary-button" href="checkout.php">Continuar al pago →</a><?php endif;?></aside></div></main><?=sideb_footer()?></body></html>
