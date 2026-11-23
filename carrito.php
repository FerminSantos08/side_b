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
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Carrito — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/home.css?v=20261118">
    <link rel="stylesheet" href="assets/css/shop.css?v=20261118">
    <link rel="stylesheet" href="assets/css/cart.css?v=20261118">
    <link rel="stylesheet" href="assets/css/shop-purchase.css?v=20261119">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('tienda') ?>
<main id="contenido" class="cart-page purchase-page container">
    <a class="purchase-back" href="tienda.php"><span aria-hidden="true">←</span> Volver a la tienda</a>
    <h1 class="purchase-title">Tu <em>carrito.</em></h1>
    <?php if ($error): ?>
        <p class="editorial-feedback" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <?php if ($message = flash()): ?>
        <p class="editorial-feedback is-success" role="status"><?= e($message) ?></p>
    <?php endif; ?>

    <?php if ($items): ?>
        <div class="cart-layout">
            <section class="cart-items" aria-label="Productos en el carrito">
                <?php foreach ($items as $item): ?>
                    <?php
                    $quantity = (int) $item['cantidad'];
                    $stock = (int) $item['stock'];
                    $available = $item['activo'] && $stock > 0;
                    ?>
                    <article class="cart-item">
                        <div class="cart-thumb">
                            <?php if ($item['imagen']): ?>
                                <img src="<?= e($item['imagen']) ?>" alt="">
                            <?php endif; ?>
                        </div>
                        <div class="cart-item-info">
                            <p class="card-kicker"><?= e($item['categoria']) ?></p>
                            <h2><a href="producto.php?slug=<?= rawurlencode($item['slug']) ?>"><?= e($item['nombre']) ?></a></h2>
                            <?php if (!$available): ?>
                                <p class="cart-availability">Producto no disponible</p>
                            <?php elseif ($quantity > $stock): ?>
                                <p class="cart-availability">Disponibles: <?= $stock ?></p>
                            <?php endif; ?>
                            <div class="purchase-cart-actions">
                                <form method="post" class="purchase-quantity" aria-label="Cantidad de <?= e($item['nombre']) ?>">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['producto_id'] ?>">
                                    <button type="submit" name="quantity" value="<?= $quantity - 1 ?>" aria-label="Reducir cantidad" <?= !$available || $quantity <= 1 ? 'disabled' : '' ?>>−</button>
                                    <span aria-label="Cantidad actual"><?= $quantity ?></span>
                                    <button type="submit" name="quantity" value="<?= $quantity + 1 ?>" aria-label="Aumentar cantidad" <?= !$available || $quantity >= $stock ? 'disabled' : '' ?>>+</button>
                                </form>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="product_id" value="<?= (int) $item['producto_id'] ?>">
                                    <button class="purchase-remove" type="submit" aria-label="Quitar <?= e($item['nombre']) ?> del carrito">Quitar</button>
                                </form>
                            </div>
                        </div>
                        <strong class="cart-price"><?= cart_money((float) $item['precio'] * $quantity) ?></strong>
                    </article>
                <?php endforeach; ?>
            </section>
            <aside class="cart-summary" aria-labelledby="cart-summary-title">
                <h2 id="cart-summary-title">Resumen del pedido</h2>
                <div class="summary-line"><span>Subtotal</span><span><?= cart_money($subtotal) ?></span></div>
                <div class="summary-line"><span>Envío</span><span><?= cart_money($shipping) ?></span></div>
                <div class="summary-line summary-total"><span>Total</span><span><?= cart_money($subtotal + $shipping) ?></span></div>
                <a class="button summary-button" href="checkout.php">Continuar al pago <span aria-hidden="true">→</span></a>
            </aside>
        </div>
    <?php else: ?>
        <section class="purchase-empty">
            <h2>Tu carrito está vacío.</h2>
            <a class="button button-primary" href="tienda.php">Explorar la tienda <span aria-hidden="true">→</span></a>
        </section>
    <?php endif; ?>
</main>
<?= sideb_footer() ?>
</body>
</html>
