<?php
// Publication reactions share the API's catalog and votes. Writes stay in mobile.
$reactionRows = [];
try {
    $reactionQuery = db()->prepare(
        'SELECT t.id,t.nombre,t.icono,t.color,COUNT(r.usr_id) AS total,
                MAX(CASE WHEN r.usr_id=:usr THEN 1 ELSE 0 END) AS selected
         FROM tipo_reaccion t LEFT JOIN post_reactions r
           ON r.reaction_id=t.id AND r.blog_id=:post
         GROUP BY t.id,t.nombre,t.icono,t.color,t.orden ORDER BY t.orden'
    );
    $reactionQuery->execute(['usr' => (int) ($_SESSION['user_id'] ?? 0), 'post' => (int) $post['id_blog']]);
    $reactionRows = $reactionQuery->fetchAll();
} catch (Throwable) {
    error_log('SIDE B: no fue posible consultar las reacciones de la publicación.');
}
$reactionPaths = [
    'heart' => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/>',
    'music-2' => '<path d="M9 18V5l12-2v13M9 9l12-2"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
    'headphones' => '<path d="M3 14v-3a9 9 0 0 1 18 0v3"/><rect x="3" y="12" width="4" height="9" rx="2"/><rect x="17" y="12" width="4" height="9" rx="2"/>',
    'sparkles' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5L12 3ZM5 3v4M3 5h4M19 17v4M17 19h4"/>',
];
?>
<?php if ($reactionRows): ?>
<section class="publication-reactions" aria-label="Reacciones a la publicación">
  <ul role="list">
    <?php foreach ($reactionRows as $reaction): ?>
      <?php $reactionColor = preg_match('/^#[0-9a-f]{6}$/i', $reaction['color']) ? $reaction['color'] : '#858C77'; ?>
      <li style="--reaction-color:<?= e($reactionColor) ?>">
        <svg role="img" aria-label="<?= e($reaction['nombre']) ?>" viewBox="0 0 24 24"><?= $reactionPaths[$reaction['icono']] ?? $reactionPaths['heart'] ?></svg>
        <strong><?= (int) $reaction['total'] ?></strong>
      </li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>
