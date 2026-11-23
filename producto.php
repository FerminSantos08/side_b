<?php
declare(strict_types=1);require_once __DIR__.'/assets/php/bootstrap.php';$slug=trim((string)($_GET['slug']??''));$legacyId=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;$product=null;if($legacyId&&$slug===''){$s=db()->prepare('SELECT slug FROM producto WHERE id_producto=:id AND activo=TRUE');$s->execute(['id'=>$legacyId]);if($legacySlug=$s->fetchColumn())redirect('producto.php?slug='.rawurlencode($legacySlug));}if($slug!==''&&preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/',$slug)){$s=db()->prepare('SELECT p.*,c.nombre categoria FROM producto p JOIN categoria_producto c ON c.id_categoria=p.categoria_id WHERE p.slug=:slug AND p.activo=TRUE');$s->execute(['slug'=>$slug]);$product=$s->fetch();}$saved=false;if($product&&!empty($_SESSION['user_id'])){$s=db()->prepare('SELECT 1 FROM wishlist_item WHERE usr_id=:u AND producto_id=:p');$s->execute(['u'=>$_SESSION['user_id'],'p'=>$product['id_producto']]);$saved=(bool)$s->fetchColumn();}if(!$product)http_response_code(404);
$editions = [];
if (!empty($product['album_id'])) {
    $editionQuery = db()->prepare(
        "SELECT p.slug, p.nombre, p.stock, a.titulo
         FROM producto p JOIN album a ON a.id_album = p.album_id
         WHERE p.album_id = :album AND p.activo = TRUE AND p.slug IS NOT NULL AND p.slug <> ''
         ORDER BY p.id_producto"
    );
    $editionQuery->execute(['album' => $product['album_id']]);
    $editions = $editionQuery->fetchAll();
}

$relatedProducts = [];
if ($product) {
    $relatedQuery = db()->prepare(
        "SELECT id_producto, slug, nombre, imagen, precio
         FROM producto
         WHERE categoria_id = :category AND id_producto <> :product
           AND activo = TRUE AND stock > 0 AND slug IS NOT NULL AND slug <> ''
           AND COALESCE(album_id = :album, 0) = 0
         ORDER BY fecha_actualizacion DESC, id_producto DESC
         LIMIT 4"
    );
    $relatedQuery->execute([
        'category' => $product['categoria_id'],
        'product' => $product['id_producto'],
        'album' => $product['album_id'],
    ]);
    $relatedProducts = $relatedQuery->fetchAll();
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=e($product['nombre']??'Producto no disponible')?> — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/home.css?v=20261118">
    <link rel="stylesheet" href="assets/css/shop.css?v=20261118">
    <link rel="stylesheet" href="assets/css/shop-purchase.css?v=20261123-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('tienda') ?>
<main id="contenido" class="purchase-page container">
    <a class="purchase-back" href="tienda.php"><span aria-hidden="true">←</span> Volver a la tienda</a>
    <?php if (!$product): ?>
        <section class="purchase-empty">
            <h1 class="purchase-title">Producto no disponible.</h1>
        </section>
    <?php else: ?>
        <?php $stock = (int) $product['stock']; ?>
        <div class="purchase-product-grid">
            <div class="purchase-product-art">
                <?php if ($product['imagen']): ?>
                    <img src="<?= e($product['imagen']) ?>" alt="<?= e($product['nombre']) ?>">
                <?php else: ?>
                    <p>Imagen no disponible</p>
                <?php endif; ?>
            </div>
            <section class="purchase-product-info" aria-labelledby="product-title">
                <p class="card-kicker"><?= e($product['categoria']) ?></p>
                <h1 class="purchase-title" id="product-title"><?= e($product['nombre']) ?></h1>
                <div class="product-price-stock">
                    <strong><?= cart_money((float) $product['precio']) ?></strong>
                    <span class="<?= $stock > 0 ? 'in-stock' : 'out-stock' ?>">
                        <?= $stock === 1 ? '1 unidad disponible' : ($stock > 1 ? $stock . ' unidades disponibles' : 'Agotado') ?>
                    </span>
                </div>
                <?php if (count($editions) > 1): ?>
                    <nav class="product-editions" aria-labelledby="product-editions-title">
                        <h2 id="product-editions-title">Ediciones del álbum</h2>
                        <ul>
                            <?php foreach ($editions as $edition): ?>
                                <?php
                                $titlePrefix = preg_quote($edition['titulo'], '~');
                                $label = preg_match('~^' . $titlePrefix . '\s+\(([^()]+)\)$~iu', $edition['nombre'], $matches)
                                    ? $matches[1] : $edition['nombre'];
                                ?>
                                <li>
                                    <a href="producto.php?slug=<?= rawurlencode($edition['slug']) ?>"<?= $edition['slug'] === $slug ? ' aria-current="page"' : '' ?>>
                                        <?= e($label) ?>
                                        <?php if ((int) $edition['stock'] <= 0): ?><small>Agotado</small><?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
                <?php if ($stock > 0 || !empty($_SESSION['user_id'])): ?>
                    <div class="product-actions">
                        <?php if ($stock > 0): ?>
                            <form method="post" action="carrito.php">
                                <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="product_id" value="<?=(int)$product['id_producto']?>">
                                <button class="button button-primary add-cart-button"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 4h2l2 11h10l2-8H6M9 20h.01M17 20h.01"/></svg>Añadir al carrito</button>
                            </form>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['user_id'])): ?>
                            <form method="post" action="guardados.php">
                                <input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
                                <input type="hidden" name="action" value="toggle_product">
                                <input type="hidden" name="product_id" value="<?=(int)$product['id_producto']?>">
                                <input type="hidden" name="return_url" value="producto.php?slug=<?=rawurlencode($slug)?>">
                                <button class="save-product-button<?=$saved?' is-saved':''?>" aria-label="<?=$saved?'Quitar de guardados':'Guardar producto'?>" aria-pressed="<?=$saved?'true':'false'?>" title="<?=$saved?'Quitar de guardados':'Guardar producto'?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3.75A1.75 1.75 0 0 1 7.75 2h8.5A1.75 1.75 0 0 1 18 3.75V22l-6-3.75L6 22V3.75Z"/></svg></button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ($product['descripcion']): ?>
                    <div class="product-description">
                        <h2>Descripción</h2>
                        <p><?= nl2br(e($product['descripcion'])) ?></p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php if ($relatedProducts): ?>
            <section class="shop-catalog purchase-related" aria-labelledby="related-products-title">
                <div class="section-heading">
                    <h2 id="related-products-title">Productos relacionados</h2>
                    <a href="tienda.php?categoria=<?= (int) $product['categoria_id'] ?>#catalogo">Ver categoría</a>
                </div>
                <div class="catalog-grid">
                    <?php foreach ($relatedProducts as $relatedProduct): ?>
                        <article class="product-card">
                            <a class="shop-catalog-card" href="producto.php?slug=<?= rawurlencode($relatedProduct['slug']) ?>">
                                <?php if ($relatedProduct['imagen']): ?>
                                    <div class="product-image">
                                        <img src="<?= e($relatedProduct['imagen']) ?>" alt="" loading="lazy">
                                    </div>
                                <?php endif; ?>
                                <div class="shop-card-copy">
                                    <h3><?= e($relatedProduct['nombre']) ?></h3>
                                    <p class="shop-price"><?= e(cart_money((float) $relatedProduct['precio'])) ?> MXN</p>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?= sideb_footer() ?>
</body>
</html>
