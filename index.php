<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$featuredPosts = [];
try {
    $statement = db()->prepare(
        "SELECT b.titulo, b.`desc`, b.img, b.tags, b.slug, d.fecha_publicacion,
                (SELECT GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ' / ')
                 FROM blog_artista ba
                 INNER JOIN artista a ON a.id_artista = ba.artista_id
                 WHERE ba.blog_id = b.id_blog AND a.activo = TRUE) AS artista_nombre,
                COALESCE(p.alias, u.nom_usr) AS autor, r.nombre AS rol_autor
         FROM blog AS b
         INNER JOIN blog_detalle AS d ON d.blog_id = b.id_blog
         INNER JOIN usuario AS u ON u.id_usr = d.autor_id
         INNER JOIN rol AS r ON r.id_rol = u.tip_usu
         LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr
         WHERE d.estado = 'publicado' AND d.fecha_publicacion IS NOT NULL
           AND d.fecha_publicacion <= NOW()
         ORDER BY d.fecha_publicacion DESC
         LIMIT 3"
    );
    $statement->execute();
    $featuredPosts = $statement->fetchAll();
} catch (Throwable) {
    // El inicio sigue disponible aunque no se pueda cargar esta sección.
}
$shopProducts = [];
$shopCategories = [];
$featuredArtist = null;
try {
    $shopProducts = db()->query('SELECT p.nombre,p.slug,p.precio,p.imagen,c.nombre categoria FROM producto p INNER JOIN categoria_producto c ON c.id_categoria=p.categoria_id WHERE p.activo=TRUE AND p.stock>0 AND p.slug IS NOT NULL ORDER BY p.fecha_actualizacion DESC LIMIT 3')->fetchAll();
    $shopCategories = db()->query('SELECT c.nombre,COUNT(p.id_producto) total FROM categoria_producto c INNER JOIN producto p ON p.categoria_id=c.id_categoria WHERE p.activo=TRUE AND p.stock>0 GROUP BY c.id_categoria,c.nombre ORDER BY c.nombre')->fetchAll();
    $featuredArtist = db()->query('SELECT id_artista,nombre,biografia,imagen,genero FROM artista WHERE activo=TRUE ORDER BY RAND() LIMIT 1')->fetch();
} catch (Throwable) {
    // La portada continúa disponible si el catálogo falla.
}
$heroSelections = [];
foreach ($featuredPosts as $item) $heroSelections[] = ['label' => 'Lectura recomendada', 'title' => $item['titulo'], 'caption' => 'SIDE B / Editorial', 'image' => publication_image_url($item['img']), 'url' => 'publicacion.php?slug=' . rawurlencode($item['slug'])];
if ($featuredArtist) $heroSelections[] = ['label' => 'Artista destacado', 'title' => $featuredArtist['nombre'], 'caption' => $featuredArtist['genero'] ?: 'SIDE B recomienda', 'image' => $featuredArtist['imagen'], 'url' => 'artista.php?artista=' . rawurlencode(artist_slug($featuredArtist['nombre']))];
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="SIDE B: historias, lanzamientos, merch y una comunidad real alrededor de la cultura musical.">
  <title>SIDE B — Más que música</title>
<link rel="icon" href="assets/favicons/sideb_sf.png" type="image/png">
<link rel="apple-touch-icon" href="assets/favicons/sideb_sf.png">
<link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/posts-dynamic.css">
  <style>
    .shop-section .product-grid{grid-template-columns:repeat(auto-fill,minmax(210px,280px));justify-content:start}
    .shop-section .product-image{background:var(--color-paper)!important}
    .shop-section .product-image::before,.shop-section .product-image::after{display:none!important}
    .shop-section .product-image img{display:block;height:100%;object-fit:contain;position:relative;width:100%;z-index:1}
    @media(max-width:560px){.shop-section .product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  </style>

</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>

  <?= sideb_public_header('inicio') ?>

  <main id="contenido">
    <section class="hero-section container" aria-labelledby="hero-title">
      <div class="hero-copy">
        <p class="eyebrow">Cultura musical independiente</p>
        <h1 id="hero-title">Más que<br><em>música.</em></h1>
        <p class="hero-description">Historias, artistas y objetos para seguir escuchando distinto.</p>
        <div class="button-group">
          <a class="button button-primary" href="#destacados">Explorar contenido <span aria-hidden="true">↗</span></a>
          <a class="button button-secondary" href="artistas.php">Descubrir artistas</a>
          <?php if (is_editorial_user()): ?><a class="button button-secondary" href="<?= in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true) ? 'backstage.php' : 'editorial.php' ?>"><?= (int) ($_SESSION['role_id'] ?? 0) === 2 ? 'Mis publicaciones' : 'Backstage' ?></a><?php endif; ?>
        </div>
        <div class="hero-note"><span class="note-line"></span><span>Lo que no sabías que querías escuchar</span></div>
      </div>
      <div class="hero-art visual-surface visual-surface--hero" role="img" aria-label="Composición abstracta editorial en terracota y oliva">
        <span class="hero-art-label">SIDE B / 001</span>
        <span class="hero-art-title">The<br>other<br>side</span>
        <span class="hero-art-circle"></span>
        <span class="hero-art-caption">Vol. 01 — Nuevas coordenadas</span>
      </div>
    </section>

    <section class="section container" id="destacados" aria-labelledby="featured-title">
      <div class="section-heading">
        <div><p class="eyebrow">Para empezar por aquí</p><h2 id="featured-title">Destacados</h2></div>
        <a class="text-link" href="publicaciones.php">Ver todos <span aria-hidden="true">↗</span></a>
      </div>
      <?php if ($featuredPosts): ?>
        <div class="editorial-grid">
          <?php $surfaces = ['visual-surface--portrait', 'visual-surface--warm', 'visual-surface--olive']; ?>
          <?php foreach ($featuredPosts as $index => $featured): ?>
            <?php
              $editorialLabel = publication_editorial_label($featured['artista_nombre'] ?? null);
              $editorialKicker = publication_editorial_kicker($featured['artista_nombre'] ?? null);
              $image = publication_image_url($featured['img']);
              $date = date('d/m/Y', strtotime($featured['fecha_publicacion']));
            ?>
            <article class="story-card <?= $index === 0 ? 'story-card--large' : '' ?>">
              <a class="story-image visual-surface <?= e($surfaces[$index]) ?>" href="publicacion.php?slug=<?= rawurlencode($featured['slug']) ?>" aria-label="Leer: <?= e($featured['titulo']) ?>">
                <?php if ($image): ?><img src="<?= e($image) ?>" alt=""><?php endif; ?>
                <?php if ($index === 0): ?><span class="image-stamp"><?= e($editorialLabel) ?></span><?php endif; ?>
                <span class="image-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
              </a>
              <div class="story-content">
                <p class="card-kicker"><?= e($editorialKicker) ?></p>
                <h3><a href="publicacion.php?slug=<?= rawurlencode($featured['slug']) ?>"><?= e($featured['titulo']) ?></a></h3>
                <?php if ($index === 0 && !empty($featured['desc'])): ?><p class="card-excerpt"><?= e($featured['desc']) ?></p><?php endif; ?>
                <div class="card-meta"><span>Por <?= e($featured['autor']) ?> · <?= e($featured['rol_autor']) ?></span><time datetime="<?= e($featured['fecha_publicacion']) ?>"><?= e($date) ?></time></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <p class="card-excerpt">Aún no hay publicaciones destacadas.</p>
      <?php endif; ?>
    </section>

    <section class="discovery-section" id="artistas" aria-labelledby="discovery-title">
      <div class="container discovery-inner">
        <div class="discovery-intro"><p class="eyebrow">Nuevas coordenadas</p><h2 id="discovery-title">Descubre<br><em>algo distinto.</em></h2><p>Artistas que están haciendo ruido en los márgenes. Sin algoritmos, con criterio.</p><a class="button button-light" href="artistas.php">Explorar artistas <span aria-hidden="true">↗</span></a></div>
        <?php if($featuredArtist):?><div class="artist-feature visual-surface visual-surface--artist"><?php if($featuredArtist['imagen']):?><img src="<?=e($featuredArtist['imagen'])?>" alt=""><?php endif;?><div class="artist-feature-top"><span>ARTISTA DESTACADO</span><span>01 / 01</span></div><div class="artist-feature-info"><p><?=e($featuredArtist['genero']?:'SIDE B recomienda')?></p><h3><?=e($featuredArtist['nombre'])?></h3><a href="artista.php?artista=<?=rawurlencode(artist_slug($featuredArtist['nombre']))?>" class="circle-arrow" aria-label="Ver perfil de <?=e($featuredArtist['nombre'])?>">↗</a></div></div><?php else:?><div class="artist-feature visual-surface visual-surface--artist"><div class="artist-feature-top"><span>ARTISTA DESTACADO</span></div><div class="artist-feature-info"><p>Próximamente</p><h3>Nuevas voces</h3></div></div><?php endif;?>
      </div>
    </section>

    <section class="section shop-section container" id="tienda" aria-labelledby="shop-title">
      <div class="section-heading"><div><p class="eyebrow">Objetos para escuchar y llevar</p><h2 id="shop-title">Tienda destacada</h2></div><a class="text-link" href="tienda.php">Ver tienda <span aria-hidden="true">↗</span></a></div>
      <div class="shop-layout">
        <aside class="shop-categories" aria-label="Categorías de tienda"><p class="filter-label">Explora por categoría</p><?php foreach($shopCategories as $index=>$category):?><a class="category-link <?= $index===0?'is-selected':'' ?>" href="tienda.php"><?=e($category['nombre'])?> <span><?= (int)$category['total'] ?></span></a><?php endforeach;?></aside>
        <div class="product-grid">
          <?php if(!$shopProducts):?><p class="card-excerpt">Aún no hay productos disponibles.</p><?php endif;?><?php foreach($shopProducts as $product):?><article class="product-card"><a class="product-image visual-surface visual-surface--record" href="producto.php?slug=<?=rawurlencode($product['slug'])?>"><?php if($product['imagen']):?><img src="<?=e($product['imagen'])?>" alt="<?=e($product['nombre'])?>"><?php endif;?></a><div class="product-info"><div><p class="card-kicker"><?=e($product['categoria'])?></p><h3><a href="producto.php?slug=<?=rawurlencode($product['slug'])?>"><?=e($product['nombre'])?></a></h3></div><strong>$<?=number_format((float)$product['precio'],2)?></strong></div></article><?php endforeach;?>
        </div>
      </div>
    </section>

  </main>

  <?= sideb_footer() ?>
  <script>
    const heroSelections = <?= json_encode($heroSelections, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const heroArt = document.querySelector('.hero-art');
    if (heroArt && heroSelections.length) {
      const selection = heroSelections[Math.floor(Math.random() * heroSelections.length)];
      const label = heroArt.querySelector('.hero-art-label');
      const title = heroArt.querySelector('.hero-art-title');
      const caption = heroArt.querySelector('.hero-art-caption');
      if (label) label.textContent = selection.label;
      if (title) title.textContent = selection.title.length > 34 ? selection.title.slice(0, 34).trimEnd() + '…' : selection.title;
      if (caption) caption.textContent = selection.caption;
      if (selection.image) heroArt.style.background = `center / cover no-repeat url("${selection.image}")`;
      heroArt.setAttribute('aria-label', selection.label + ': ' + selection.title);
      heroArt.addEventListener('click', () => { window.location.href = selection.url; });
      heroArt.tabIndex = 0;
      heroArt.setAttribute('role', 'link');
      heroArt.addEventListener('keydown', event => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = selection.url; } });
    }
  </script>
</body>
</html>




