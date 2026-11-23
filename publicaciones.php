<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$posts = [];
$loadError = null;
try {
    $statement = db()->prepare(
        "SELECT b.id_blog, b.titulo, b.`desc`, b.img, b.tags, b.slug,
                d.fecha_publicacion, u.nom_usr, COALESCE(p.alias, u.nom_usr) AS autor, r.nombre AS rol_autor,
                (SELECT GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ' / ')
                 FROM blog_artista ba
                 INNER JOIN artista a ON a.id_artista = ba.artista_id
                 WHERE ba.blog_id = b.id_blog AND a.activo = TRUE) AS artista_nombre
         FROM blog AS b
         INNER JOIN blog_detalle AS d ON d.blog_id = b.id_blog
         INNER JOIN usuario AS u ON u.id_usr = d.autor_id
         INNER JOIN rol AS r ON r.id_rol = u.tip_usu
         LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr
         WHERE d.estado = 'publicado' AND d.fecha_publicacion IS NOT NULL AND d.fecha_publicacion <= NOW()
         ORDER BY d.fecha_publicacion DESC"
    );
    $statement->execute();
    $posts = $statement->fetchAll();
} catch (Throwable) {
    $loadError = 'No fue posible cargar las publicaciones. Intenta de nuevo más tarde.';
}

$leadPost = array_shift($posts);
$coverUrl = static function (?string $filename): ?string {
    $url = publication_image_url($filename);
    return $url !== null && is_file(__DIR__ . '/uploads/publicaciones/' . $filename) ? $url : null;
};
$leadImage = $leadPost ? $coverUrl($leadPost['img']) : null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Publicaciones y artículos de cultura musical en SIDE B.">
  <title>Publicaciones — SIDE B</title>
  <link rel="icon" href="assets/favicons/sideb_sf.png" type="image/png">
  <link rel="apple-touch-icon" href="assets/favicons/sideb_sf.png">
  <link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
  <link rel="stylesheet" href="assets/css/global.css?v=20261118">
  <link rel="stylesheet" href="assets/css/components.css?v=20261118">
  <link rel="stylesheet" href="assets/css/home.css?v=20261119">
  <link rel="stylesheet" href="assets/css/posts.css?v=20261119">
  <link rel="stylesheet" href="assets/css/posts-dynamic.css?v=20261118">
</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <?php echo sideb_public_header('publicaciones'); ?>
  <main id="contenido" class="publication-index">
    <section class="home-lead container" aria-labelledby="publications-title">
      <div class="home-lead-copy">
        <p class="eyebrow">Historias para escuchar distinto</p>
        <h1 id="publications-title">Publicaciones<br><em>y hallazgos.</em></h1>
        <p class="hero-description">Entrevistas, contexto y selecciones para mirar la música desde otro lugar.</p>
        <?php if ($posts): ?>
          <div class="button-group"><a class="button button-primary" href="#archivo">Explorar publicaciones <span aria-hidden="true">↓</span></a></div>
        <?php endif; ?>
      </div>
      <?php if ($leadPost): ?>
        <a class="home-feature" href="publicacion.php?slug=<?= rawurlencode($leadPost['slug']) ?>">
          <?php if ($leadImage): ?><img class="home-feature-image" src="<?= e($leadImage) ?>" alt="" fetchpriority="high"><?php endif; ?>
          <div class="home-feature-caption">
            <div>
              <p class="eyebrow">Última publicación</p>
              <h2><?= e($leadPost['titulo']) ?></h2>
              <?php if (!empty($leadPost['desc'])): ?><p class="home-feature-meta"><?= e($leadPost['desc']) ?></p><?php endif; ?>
              <div class="card-meta">
                <span>Por <?= e($leadPost['autor']) ?> · <?= e($leadPost['rol_autor']) ?></span>
                <time datetime="<?= e($leadPost['fecha_publicacion']) ?>"><?= e(date('d/m/Y', strtotime($leadPost['fecha_publicacion']))) ?></time>
              </div>
            </div>
            <span class="home-feature-arrow" aria-hidden="true">↗</span>
          </div>
        </a>
      <?php endif; ?>
    </section>

    <?php if ($loadError || !$leadPost): ?>
      <section class="container publication-index-empty">
        <p class="profile-note"><?= e($loadError ?? 'Aún no hay publicaciones disponibles.') ?></p>
      </section>
    <?php elseif ($posts): ?>
      <section class="container publication-archive" id="archivo" aria-labelledby="archive-title">
        <div class="section-heading"><h2 id="archive-title">Archivo editorial</h2></div>
        <div class="posts-grid">
          <?php foreach ($posts as $post): ?>
            <?php
              $editorialKicker = publication_editorial_kicker($post['artista_nombre'] ?? null);
              $date = date('d/m/Y', strtotime($post['fecha_publicacion']));
              $excerpt = (string) ($post['desc'] ?? '');
              $image = $coverUrl($post['img']);
              $url = 'publicacion.php?slug=' . rawurlencode($post['slug']);
            ?>
            <article class="story-card">
              <?php if ($image): ?>
                <a class="story-image visual-surface" href="<?= e($url) ?>" aria-label="Leer <?= e($post['titulo']) ?>">
                  <img src="<?= e($image) ?>" alt="" loading="lazy">
                </a>
              <?php endif; ?>
              <div class="story-content">
                <p class="card-kicker"><?= e($editorialKicker) ?></p>
                <h3><a href="<?= e($url) ?>"><?= e($post['titulo']) ?></a></h3>
                <?php if ($excerpt !== ''): ?><p class="card-excerpt"><?= e($excerpt) ?></p><?php endif; ?>
                <div class="card-meta"><span>Por <?= e($post['autor']) ?> · <?= e($post['rol_autor']) ?></span><time datetime="<?= e($post['fecha_publicacion']) ?>"><?= e($date) ?></time></div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>
  </main>
  <?= sideb_footer() ?>
</body>
</html>
