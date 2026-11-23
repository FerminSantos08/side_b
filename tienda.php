<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$products = [];
$categories = [];
$error = null;
try {
    $rows = db()->query('SELECT p.id_producto,p.slug,p.nombre,p.precio,p.imagen,p.descripcion,p.categoria_id,p.album_id,c.nombre AS categoria
        FROM producto p JOIN categoria_producto c ON c.id_categoria=p.categoria_id
        WHERE p.activo=TRUE AND p.stock>0 ORDER BY p.fecha_actualizacion DESC,p.id_producto DESC')->fetchAll();
    $categories = db()->query('SELECT id_categoria,nombre FROM categoria_producto ORDER BY nombre')->fetchAll();
    $seenAlbums = [];
    $categoryTotals = [];
    foreach ($rows as $product) {
        $albumId = $product['album_id'];
        if ($albumId !== null) {
            if (isset($seenAlbums[$albumId])) continue;
            $seenAlbums[$albumId] = true;
        }
        $products[] = $product;
        $categoryId = $product['categoria_id'];
        $categoryTotals[$categoryId] = ($categoryTotals[$categoryId] ?? 0) + 1;
    }
} catch (Throwable) {
    $error = 'No fue posible cargar el catálogo.';
}

$selected = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;
$categoryNames = array_column($categories, 'nombre', 'id_categoria');
if (!isset($categoryNames[$selected])) $selected = 0;
$visibleProducts = $selected
    ? array_filter($products, static fn (array $product): bool => (int) $product['categoria_id'] === $selected)
    : $products;
$featured = null;
foreach ($visibleProducts as $product) {
    if (!$product['imagen']) continue;
    if ($product['slug'] === 'am-arctic-monkeys') {
        $featured = $product;
        break;
    }
    $featured ??= $product;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Tienda — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/home.css?v=20261119">
    <link rel="stylesheet" href="assets/css/shop.css?v=20261122-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('tienda') ?>
<main id="contenido" class="shop-index">
    <section class="home-lead container" aria-labelledby="shop-title">
        <div class="home-lead-copy">
            <p class="eyebrow">Objetos para escuchar y llevar</p>
            <h1 id="shop-title">La tienda<br><em>del otro lado.</em></h1>
            <p class="hero-description">Ediciones especiales, ropa y objetos elegidos para acompañar tu forma de escuchar.</p>
            <?php if ($products): ?>
                <div class="button-group"><a class="button button-primary" href="#catalogo">Explorar catálogo <span aria-hidden="true">↓</span></a></div>
            <?php endif; ?>
        </div>
        <?php if ($featured): ?>
            <a class="home-feature shop-feature" href="producto.php?id=<?= (int) $featured['id_producto'] ?>">
                <img class="home-feature-image" src="<?= e($featured['imagen']) ?>" alt="" fetchpriority="high">
                <div class="home-feature-caption">
                    <div>
                        <p class="eyebrow">Producto destacado · <?= e($featured['categoria']) ?></p>
                        <h2><?= e($featured['nombre']) ?></h2>
                        <?php if ($featured['descripcion']): ?>
                            <p class="shop-feature-description"><?= nl2br(e($featured['descripcion'])) ?></p>
                        <?php endif; ?>
                        <p class="shop-price"><?= e(cart_money((float) $featured['precio'])) ?> MXN</p>
                    </div>
                    <span class="home-feature-arrow" aria-hidden="true">↗</span>
                </div>
            </a>
        <?php endif; ?>
    </section>

    <section class="container shop-catalog" id="catalogo" aria-labelledby="catalog-title">
        <div class="section-heading"><h2 id="catalog-title"><?= e($categoryNames[$selected] ?? 'Catálogo') ?></h2></div>
        <?php if ($categories): ?>
            <nav class="shop-category-nav" aria-label="Categorías de tienda">
                <a href="tienda.php#catalogo"<?= $selected === 0 ? ' aria-current="page"' : '' ?>>Todos <small><?= count($products) ?></small></a>
                <?php foreach ($categories as $category): ?>
                    <?php $id = (int) $category['id_categoria']; ?>
                    <a href="tienda.php?categoria=<?= $id ?>#catalogo"<?= $selected === $id ? ' aria-current="page"' : '' ?>>
                        <?= e($category['nombre']) ?> <small><?= $categoryTotals[$id] ?? 0 ?></small>
                    </a>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
        <?php if ($error): ?>
            <p role="alert"><?= e($error) ?></p>
        <?php elseif (!$visibleProducts): ?>
            <p>No hay productos disponibles por ahora.</p>
        <?php else: ?>
            <div class="catalog-grid">
                <?php foreach ($visibleProducts as $product): ?>
                    <article class="product-card">
                        <a class="shop-catalog-card" href="producto.php?id=<?= (int) $product['id_producto'] ?>">
                            <?php if ($product['imagen']): ?>
                                <div class="product-image"><img src="<?= e($product['imagen']) ?>" alt="" loading="lazy"></div>
                            <?php endif; ?>
                            <div class="shop-card-copy">
                                <p class="card-kicker"><?= e($product['categoria']) ?></p>
                                <h3><?= e($product['nombre']) ?></h3>
                                <?php if ($product['descripcion']): ?>
                                    <p class="shop-card-description"><?= e($product['descripcion']) ?></p>
                                <?php endif; ?>
                                <p class="shop-price"><?= e(cart_money((float) $product['precio'])) ?> MXN</p>
                            </div>
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?= sideb_footer() ?>
</body>
</html>
