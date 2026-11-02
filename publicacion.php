<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$post = null;
$related = [];
$comments = [];
$loadError = null;
$commentError = null;
$commentMessage = null;

if ($slug === '' || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
    http_response_code(404);
    $loadError = 'La publicación que buscas no está disponible.';
} else {
    try {
        $statement = db()->prepare(
            "SELECT b.id_blog, b.titulo, b.`desc`, b.img, b.tags, b.slug, d.contenido, d.fecha_publicacion,
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
             WHERE b.slug = :slug AND d.estado = 'publicado'
               AND d.fecha_publicacion IS NOT NULL AND d.fecha_publicacion <= NOW()
             LIMIT 1"
        );
        $statement->execute(['slug' => $slug]);
        $post = $statement->fetch();

        if (!$post) {
            http_response_code(404);
            $loadError = 'La publicación que buscas no está disponible.';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'comment') {
                $comment = trim((string) ($_POST['comment'] ?? ''));
                $commentLength = function_exists('mb_strlen') ? mb_strlen($comment) : strlen($comment);

                if (!valid_csrf()) {
                    $commentError = 'La sesión del formulario venció. Intenta de nuevo.';
                } elseif (empty($_SESSION['user_id'])) {
                    $commentError = 'Inicia sesión para publicar un comentario.';
                } elseif ($commentLength < 1 || $commentLength > 500) {
                    $commentError = 'El comentario debe tener entre 1 y 500 caracteres.';
                } else {
                    $commentStatement = db()->prepare(
                        'INSERT INTO comentarios (comentario, usr_id, blog_id) VALUES (:comment, :user, :blog)'
                    );
                    $commentStatement->execute([
                        'comment' => $comment,
                        'user' => (int) $_SESSION['user_id'],
                        'blog' => (int) $post['id_blog'],
                    ]);
                    flash('Tu comentario fue publicado.');
                    redirect('publicacion.php?slug=' . rawurlencode($slug) . '#comentarios');
                }
            }

            $commentMessage = flash();
            $relatedStatement = db()->prepare(
                "SELECT b.titulo, b.img, b.tags, b.slug,
                        (SELECT GROUP_CONCAT(a.nombre ORDER BY a.nombre SEPARATOR ' / ')
                         FROM blog_artista ba
                         INNER JOIN artista a ON a.id_artista = ba.artista_id
                         WHERE ba.blog_id = b.id_blog AND a.activo = TRUE) AS artista_nombre
                 FROM blog AS b
                 INNER JOIN blog_detalle AS d ON d.blog_id = b.id_blog
                 WHERE d.estado = 'publicado' AND d.fecha_publicacion IS NOT NULL
                   AND d.fecha_publicacion <= NOW() AND b.id_blog <> :id
                 ORDER BY d.fecha_publicacion DESC
                 LIMIT 2"
            );
            $relatedStatement->execute(['id' => $post['id_blog']]);
            $related = $relatedStatement->fetchAll();

            $commentsStatement = db()->prepare(
                "SELECT c.id_com, c.comentario, c.fecha, COALESCE(p.alias, u.nom_usr) AS autor
                 FROM comentarios AS c
                 INNER JOIN usuario AS u ON u.id_usr = c.usr_id
                 LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr
                 WHERE c.blog_id = :blog AND c.estatus = 'visible'
                 ORDER BY c.fecha ASC"
            );
            $commentsStatement->execute(['blog' => $post['id_blog']]);
            $comments = $commentsStatement->fetchAll();
        }
    } catch (Throwable) {
        $loadError = 'No fue posible cargar la publicación. Intenta de nuevo más tarde.';
    }
}

$editorialLabel = $post ? publication_editorial_label($post['artista_nombre'] ?? null) : publication_editorial_label(null);
$editorialKicker = $post ? publication_editorial_kicker($post['artista_nombre'] ?? null) : publication_editorial_kicker(null);
$date = $post ? date('d/m/Y', strtotime($post['fecha_publicacion'])) : '';
$wordCount = $post ? str_word_count(strip_tags((string) $post['contenido'])) : 0;
$readMinutes = max(1, (int) ceil($wordCount / 220));
$image = $post ? publication_image_url($post['img']) : null;
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= $post ? e((string) ($post['desc'] ?? $post['titulo'])) : 'Publicación no disponible en SIDE B.' ?>">
  <title><?= $post ? e($post['titulo']) . ' — SIDE B' : 'Publicación no disponible — SIDE B' ?></title>
  <link rel="icon" href="assets/favicons/favicon.ico" sizes="any">
  <link rel="icon" href="assets/favicons/favicon.svg" type="image/svg+xml">
  <link rel="apple-touch-icon" href="assets/favicons/apple-touch-icon.png">
  <link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/variables.css">
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/home.css">
  <link rel="stylesheet" href="assets/css/posts.css">
  <link rel="stylesheet" href="assets/css/posts-dynamic.css">
  <link rel="stylesheet" href="assets/css/post-detail.css?v=2">
</head>
<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <header class="site-header"><div class="container header-inner"><a class="brand" href="index.php">SIDE B<span class="brand-dot">.</span></a><nav class="primary-nav" aria-label="Navegación principal"><a class="nav-link" href="index.php">Inicio</a><a class="nav-link" href="artistas.php">Artistas</a><a class="nav-link" href="#comunidad">Comunidad</a><a class="nav-link" href="tienda.php">Tienda</a></nav><div class="header-actions"><a class="icon-button" href="perfil.php" aria-label="Mi perfil"><span class="icon icon-user"></span></a><a class="icon-button cart-button" href="carrito.php" aria-label="Carrito"><span class="icon icon-bag"></span><span class="cart-count">0</span></a></div><button class="menu-button" type="button" aria-label="Abrir menú"><span></span><span></span></button><?= logout_control() ?></div></header>
  <main id="contenido" class="post-detail">
    <?php if ($loadError): ?>
      <section class="post-detail-header container"><p class="eyebrow">SIDE B</p><h1>Publicación<br><em>no disponible.</em></h1><p><?= e($loadError) ?></p><p><a class="text-link" href="publicaciones.php">Volver a publicaciones <span aria-hidden="true">↗</span></a></p></section>
    <?php else: ?>
      <section class="post-article container">
        <div class="detail-cover visual-surface visual-surface--portrait"><?php if ($image): ?><img src="<?= e($image) ?>" alt=""><span class="image-stamp"><?= e($editorialLabel) ?></span><?php endif; ?></div>
        <header class="post-detail-header"><h1><?= e($post['titulo']) ?></h1><div class="post-detail-meta"><span class="post-category"><?= e($editorialKicker) ?></span><span>Por <?= e($post['autor']) ?> · <?= e($post['rol_autor']) ?></span><time datetime="<?= e($post['fecha_publicacion']) ?>"><?= e($date) ?></time><span><?= $readMinutes ?> min de lectura</span><?= logout_control() ?></div></header>
        <div class="article-reading-layout">
          <!-- El HTML fue filtrado por clean_editorial_html() antes de persistirse. -->
          <article class="detail-copy"><?= $post['contenido'] ?></article>
          <aside class="interaction-panel" aria-label="Interacción de la publicación">
            <section class="interaction-section">
              <p class="eyebrow">Interacción</p>
              <h2>Participa</h2>
              <p class="interaction-pending">Las reacciones estarán disponibles próximamente.</p>
              <p class="interaction-pending">Guardar publicaciones requiere soporte de datos adicional.</p>
              <a class="text-link" href="publicaciones.php">Volver a publicaciones <span aria-hidden="true">↗</span></a>
            </section>
            <section class="comments-section" id="comentarios" aria-labelledby="comments-title">
              <div class="comments-heading"><h2 id="comments-title">Comentarios</h2><span><?= count($comments) ?></span></div>
              <?php if ($commentMessage): ?><p class="comment-feedback is-success" aria-live="polite"><?= e($commentMessage) ?></p><?php endif; ?>
              <?php if ($commentError): ?><p class="comment-feedback" aria-live="polite"><?= e($commentError) ?></p><?php endif; ?>
              <?php if (!empty($_SESSION['user_id'])): ?>
                <form class="comment-form" method="post" action="publicacion.php?slug=<?= rawurlencode($slug) ?>#comentarios">
                  <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="comment">
                  <label for="comment">Comparte tu idea</label>
                  <textarea id="comment" name="comment" maxlength="500" required placeholder="Escribe un comentario…"></textarea>
                  <button class="button button-primary" type="submit">Publicar comentario</button>
                </form>
              <?php else: ?>
                <p class="comment-login">Inicia sesión para participar. <a href="login.php">Entrar</a></p>
              <?php endif; ?>
              <div class="comments-list">
                <?php if (!$comments): ?><p class="comments-empty">Todavía no hay comentarios. Sé la primera persona en participar.</p><?php endif; ?>
                <?php foreach ($comments as $comment): ?>
                  <article class="comment-item"><header><strong><?= e($comment['autor']) ?></strong><time datetime="<?= e($comment['fecha']) ?>"><?= e(date('d/m/Y H:i', strtotime($comment['fecha']))) ?></time></header><p><?= nl2br(e($comment['comentario'])) ?></p></article>
                <?php endforeach; ?>
              </div>
            </section>
          </aside>
        </div>
      </section>
      <?php if ($related): ?><section class="related-posts container"><p class="eyebrow">Sigue explorando</p><h2>También podría interesarte</h2><div class="editorial-grid"><?php foreach ($related as $index => $item): ?><?php $relatedImage = publication_image_url($item['img']); ?><article class="story-card"><a class="story-image visual-surface <?= $index === 0 ? 'visual-surface--warm' : 'visual-surface--olive' ?>" href="publicacion.php?slug=<?= rawurlencode($item['slug']) ?>"><?php if ($relatedImage): ?><img src="<?= e($relatedImage) ?>" alt=""><?php endif; ?><span class="image-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span></a><div class="story-content"><p class="card-kicker"><?= e(publication_editorial_kicker($item['artista_nombre'] ?? null)) ?></p><h3><a href="publicacion.php?slug=<?= rawurlencode($item['slug']) ?>"><?= e($item['titulo']) ?></a></h3></div></article><?php endforeach; ?></div></section><?php endif; ?>
    <?php endif; ?>
  </main>
  <?= sideb_footer() ?>
</body>
</html>
