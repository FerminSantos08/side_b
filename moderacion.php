<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';
require_executive_producer();

$db = db();
$states = ['visible' => 'Visibles', 'oculto' => 'Ocultos', 'eliminado' => 'Eliminados'];
$labels = ['visible' => 'Visible', 'oculto' => 'Oculto', 'eliminado' => 'Eliminado'];
$status = filter_input(INPUT_GET, 'estado') ?? 'todos';
$status = isset($states[$status]) ? $status : 'todos';
$query = trim((string) filter_input(INPUT_GET, 'q'));
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$url = static function (int $page) use ($status, $query): string {
    return 'moderacion.php?' . http_build_query(['estado' => $status, 'q' => $query, 'page' => $page]);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) {
        http_response_code(403);
        $error = 'La sesión del formulario venció. Recarga la página e inténtalo de nuevo.';
    } else {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $target = filter_input(INPUT_POST, 'estado');
        if (!$id || !in_array($target, ['visible', 'oculto', 'eliminado'], true)) {
            http_response_code(400);
            $error = 'La acción de moderación no es válida.';
        } else {
            try {
                $condition = "id_com = :id AND estatus IN ('visible', 'oculto')";
                $values = ['nuevo' => $target, 'id' => $id];
                if ($target !== 'eliminado') {
                    $condition .= ' AND estatus = :anterior';
                    $values['anterior'] = $target === 'visible' ? 'oculto' : 'visible';
                }
                $update = $db->prepare('UPDATE comentarios SET estatus = :nuevo WHERE ' . $condition);
                $update->execute($values);
                flash($update->rowCount() ? match ($target) {
                    'visible' => 'Comentario visible de nuevo.',
                    'oculto' => 'Comentario ocultado.',
                    'eliminado' => 'Comentario eliminado.',
                } : 'El comentario ya cambió de estado.');
                redirect($url($page));
            } catch (PDOException) {
                $error = 'No se pudo actualizar el comentario. Inténtalo de nuevo.';
            }
        }
    }
}

$conditions = [];
$params = [];
if ($status !== 'todos') {
    $conditions[] = 'c.estatus = :estado';
    $params['estado'] = $status;
}
if ($query !== '') {
    $conditions[] = "(c.comentario LIKE :texto OR b.titulo LIKE :titulo OR COALESCE(p.alias, u.nom_usr) LIKE :autor)";
    foreach (['texto', 'titulo', 'autor'] as $key) {
        $params[$key] = '%' . $query . '%';
    }
}
$from = ' FROM comentarios AS c
          JOIN blog AS b ON b.id_blog = c.blog_id
          JOIN usuario AS u ON u.id_usr = c.usr_id
          LEFT JOIN perfil_usuario AS p ON p.usr_id = u.id_usr';
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$count = $db->prepare('SELECT COUNT(*)' . $from . $where);
$count->execute($params);
$total = (int) $count->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$list = $db->prepare('SELECT c.id_com, c.comentario, c.fecha, c.estatus, b.titulo, b.slug,
                             COALESCE(p.alias, u.nom_usr) AS autor'
    . $from . $where . ' ORDER BY c.fecha DESC, c.id_com DESC LIMIT :limit OFFSET :offset');
foreach ($params as $key => $value) {
    $list->bindValue($key, $value);
}
$list->bindValue('limit', $perPage, PDO::PARAM_INT);
$list->bindValue('offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$list->execute();
$comments = $list->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Moderación de comentarios — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/backstage.css?v=20261120-6">
    <link rel="stylesheet" href="assets/css/admin-navigation.css?v=20261120-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_admin_header() ?>
<main id="contenido" class="backstage-page container">
    <header class="backstage-heading">
        <div>
            <p class="eyebrow">Contenido</p>
            <h1>Moderación de comentarios</h1>
        </div>
        <p class="backstage-intro">Revisa los comentarios de las publicaciones y cambia su visibilidad.</p>
    </header>
    <section class="backstage-content moderation-content" aria-label="Comentarios de publicaciones">
        <?php if (isset($error)): ?><p class="editorial-feedback" role="alert"><?= e($error) ?></p><?php endif; ?>
        <?php if ($message = flash()): ?><p class="editorial-feedback is-success" role="status"><?= e($message) ?></p><?php endif; ?>
        <form class="moderation-filter" method="get">
            <label>Buscar <input type="search" name="q" value="<?= e($query) ?>" placeholder="Comentario, autor o publicación"></label>
            <label>Estado <select name="estado">
                <option value="todos">Todos</option>
                <?php foreach ($states as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select></label>
            <button class="button button-primary" type="submit">Filtrar</button>
        </form>
        <p class="moderation-count"><?= $total ?> <?= $total === 1 ? 'comentario' : 'comentarios' ?></p>
        <div class="editorial-list moderation-list">
            <?php if (!$comments): ?><p>No hay comentarios con estos filtros.</p><?php endif; ?>
            <?php foreach ($comments as $comment): ?>
                <article>
                    <div>
                        <p class="moderation-meta">
                            <span class="moderation-status <?= $comment['estatus'] === 'visible' ? 'is-visible' : '' ?>"><?= e($labels[$comment['estatus']]) ?></span>
                            <strong><?= e($comment['autor']) ?></strong>
                            <time datetime="<?= e(str_replace(' ', 'T', $comment['fecha'])) ?>"><?= e(date('d/m/Y H:i', strtotime($comment['fecha']))) ?></time>
                        </p>
                        <h2><a href="publicacion.php?slug=<?= rawurlencode($comment['slug']) ?>#comentarios"><?= e($comment['titulo']) ?></a></h2>
                        <p class="moderation-comment"><?= nl2br(e($comment['comentario'])) ?></p>
                    </div>
                    <?php if ($comment['estatus'] !== 'eliminado'): ?>
                      <div class="moderation-actions">
                        <?php $visible = $comment['estatus'] === 'visible'; ?>
                        <form method="post" action="<?= e($url($page)) ?>">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $comment['id_com'] ?>">
                            <input type="hidden" name="estado" value="<?= $visible ? 'oculto' : 'visible' ?>">
                            <button class="button button-secondary" type="submit">
                                <svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s4-6 10-6 10 6 10 6-4 6-10 6-10-6-10-6z"/><circle cx="12" cy="12" r="2.5"/><?php if ($visible): ?><path d="m3 3 18 18"/><?php endif; ?></svg>
                                <?= $visible ? 'Ocultar' : 'Volver a mostrar' ?>
                            </button>
                        </form>
                        <details class="moderation-delete">
                            <summary class="button button-secondary">
                                <svg class="admin-nav-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 10v6m4-6v6"/></svg>
                                Eliminar
                            </summary>
                            <form method="post" action="<?= e($url($page)) ?>">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="id" value="<?= (int) $comment['id_com'] ?>">
                                <input type="hidden" name="estado" value="eliminado">
                                <button class="button button-primary" type="submit">Confirmar eliminación</button>
                            </form>
                        </details>
                      </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
            <nav class="moderation-pagination" aria-label="Páginas de comentarios">
                <?php if ($page > 1): ?><a class="button button-secondary" href="<?= e($url($page - 1)) ?>">Anterior</a><?php endif; ?>
                <span>Página <?= $page ?> de <?= $pages ?></span>
                <?php if ($page < $pages): ?><a class="button button-secondary" href="<?= e($url($page + 1)) ?>">Siguiente</a><?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</main>
<?= sideb_footer() ?>
</body>
</html>
