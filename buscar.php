<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__ . '/assets/php/search.php';

$query = sideb_search_query($_GET['q'] ?? '');
$categories = sideb_search_categories();
$category = $_GET['categoria'] ?? '';
if (!is_string($category) || !isset($categories[$category])) $category = '';
$page = $category === '' ? 1 : (filter_input(INPUT_GET, 'pagina', FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => intdiv(PHP_INT_MAX, 24)],
]) ?: 1);
$pageSize = $category === '' ? 12 : 24;
$groups = [];
$error = null;

if ($query !== '') {
    try {
        $selected = $category === '' ? $categories : [$category => $categories[$category]];
        foreach ($selected as $key => $label) {
            $items = sideb_search_results(db(), $query, $key, $pageSize + 1, ($page - 1) * $pageSize);
            if (!$items) continue;
            $groups[$key] = ['items' => array_slice($items, 0, $pageSize), 'more' => count($items) > $pageSize];
        }
    } catch (Throwable $exception) {
        error_log('[SIDE B búsqueda] ' . $exception->getMessage());
        $error = 'No fue posible realizar la búsqueda. Intenta nuevamente.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="description" content="Busca publicaciones, artistas, álbumes y productos en SIDE B.">
    <title>Buscar — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/search.css?v=20261119">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('') ?>
<main id="contenido" class="container search-page">
    <header class="search-heading">
        <h1>Buscar en SIDE B</h1>
        <form class="search-form" method="get" action="buscar.php" role="search" aria-label="Búsqueda completa">
            <label class="sr-only" for="search-query">Buscar en SIDE B</label>
            <input class="field-control" id="search-query" name="q" type="search" maxlength="100" value="<?= e($query) ?>" placeholder="Artistas, álbumes, publicaciones y productos">
            <?php if ($category !== ''): ?><input type="hidden" name="categoria" value="<?= e($category) ?>"><?php endif; ?>
            <button class="button button-primary" type="submit">Buscar</button>
        </form>
    </header>

    <?php if ($query !== ''): ?>
        <nav class="search-filters" aria-label="Filtrar por categoría">
            <?php foreach (['' => 'Todo'] + $categories as $key => $label): ?>
                <a href="<?= e(sideb_search_url($query, $key)) ?>"<?= $category === $key ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <section class="search-results" aria-labelledby="search-results-title">
            <h2 id="search-results-title">Resultados para «<?= e($query) ?>»</h2>
            <?php if ($error): ?>
                <p class="editorial-feedback" role="alert"><?= e($error) ?></p>
            <?php elseif (!$groups): ?>
                <p class="search-empty"><?= $page > 1 ? 'No hay más resultados en esta página.' : 'No encontramos coincidencias.' ?></p>
            <?php endif; ?>
            <?php foreach ($groups as $key => $group): ?>
                <section class="search-section" aria-labelledby="search-<?= e($key) ?>">
                    <div class="search-section-heading">
                        <h3 id="search-<?= e($key) ?>"><?= e($categories[$key]) ?></h3>
                        <?php if ($category === '' && $group['more']): ?>
                            <a class="text-link" href="<?= e(sideb_search_url($query, $key)) ?>">Ver todos <span aria-hidden="true">↗</span></a>
                        <?php endif; ?>
                    </div>
                    <ul class="search-list" role="list">
                        <?php foreach ($group['items'] as $item): ?>
                            <li>
                                <a class="search-card" href="<?= e($item['url']) ?>">
                                    <?php if ($item['image']): ?>
                                        <img class="search-card-image" src="<?= e($item['image']) ?>" alt="" width="96" height="96" loading="lazy">
                                    <?php endif; ?>
                                    <div class="search-card-copy">
                                        <p class="eyebrow"><?= e($item['meta']) ?></p>
                                        <strong><?= e($item['label']) ?></strong>
                                        <?php if ($item['description'] !== ''): ?><p><?= e($item['description']) ?></p><?php endif; ?>
                                    </div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            <?php endforeach; ?>

            <?php if (!$error && $category !== '' && ($page > 1 || !empty($groups[$category]['more']))): ?>
                <nav class="search-pagination" aria-label="Páginas de resultados">
                    <?php if ($page > 1): ?><a class="button button-secondary" href="<?= e(sideb_search_url($query, $category, $page - 1)) ?>">Anterior</a><?php endif; ?>
                    <span>Página <?= $page ?></span>
                    <?php if (!empty($groups[$category]['more'])): ?><a class="button button-secondary" href="<?= e(sideb_search_url($query, $category, $page + 1)) ?>">Siguiente</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
<?= sideb_footer() ?>
</body>
</html>
