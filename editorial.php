<?php
require_once __DIR__ . '/assets/php/editorial.php';
require_editorial_user();

// Editorial conserva su contenido propio, pero usa la misma cabecera administrativa.
$adminHeader = '<header class="site-header"><div class="container header-inner"><a class="brand" href="index.php">SIDE B<span class="brand-dot">.</span></a><nav class="primary-nav" aria-label="Módulos administrativos"></nav>' . logout_control() . '</div></header>';
ob_start(static function (string $html) use ($adminHeader): string {
    return preg_replace('#<header class="site-header">.*?</header>#s', $adminHeader, $html, 1) ?? $html;
});

$database = db();
$isExecutive = is_executive_producer();
$mode = in_array($_GET['view'] ?? '', ['new', 'edit'], true) ? $_GET['view'] : 'list';
$postId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$error = null;
$post = ['titulo'=>'', 'desc'=>'', 'tags'=>'', 'contenido'=>'', 'estado'=>'borrador', 'img'=>null, 'artistas'=>[], 'albumes'=>[]];

function editorial_post(PDO $database, int $id): ?array {
    $s = $database->prepare('SELECT b.*, d.contenido, d.autor_id, d.estado, d.fecha_creacion, d.fecha_actualizacion, d.fecha_publicacion, COALESCE(p.alias, u.nom_usr) AS autor FROM blog b INNER JOIN blog_detalle d ON d.blog_id=b.id_blog INNER JOIN usuario u ON u.id_usr=d.autor_id LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr WHERE b.id_blog=:id LIMIT 1');
    $s->execute(['id'=>$id]); return $s->fetch() ?: null;
}
function ids_from_post(string $key): array {
    $submitted = $_POST[$key] ?? [];
    if (!is_array($submitted)) throw new InvalidArgumentException('El formato de la selección no es válido.');
    $ids = [];
    foreach ($submitted as $value) {
        if (!is_string($value) || !preg_match('/^[1-9][0-9]*$/', $value)) throw new InvalidArgumentException('La selección contiene un identificador no válido.');
        $id = (int) $value;
        if (isset($ids[$id])) throw new InvalidArgumentException('No puedes seleccionar el mismo elemento más de una vez.');
        $ids[$id] = $id;
    }
    return array_values($ids);
}

function existing_ids(PDO $database, string $table, string $column, array $ids): array {
    if (!$ids) return [];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $statement = $database->prepare("SELECT $column FROM $table WHERE $column IN ($placeholders)");
    $statement->execute($ids);
    return array_map('intval', array_column($statement->fetchAll(), $column));
}

function validate_post_relations(PDO $database, array $artistIds, array $albumIds): void {
    if (count(existing_ids($database, 'artista', 'id_artista', $artistIds)) !== count($artistIds)) throw new InvalidArgumentException('Uno o más artistas seleccionados ya no existen. Actualiza la selección.');
    if (!$albumIds) return;
    if (!$artistIds) throw new InvalidArgumentException('Selecciona primero un artista antes de asociar álbumes.');
    $placeholders = implode(',', array_fill(0, count($albumIds), '?'));
    $statement = $database->prepare("SELECT id_album, artista_id FROM album WHERE id_album IN ($placeholders)");
    $statement->execute($albumIds);
    $albums = $statement->fetchAll();
    if (count($albums) !== count($albumIds)) throw new InvalidArgumentException('Uno o más álbumes seleccionados ya no existen. Actualiza la selección.');
    $artistIds = array_flip($artistIds);
    foreach ($albums as $album) if (!isset($artistIds[(int) $album['artista_id']])) throw new InvalidArgumentException('Cada álbum seleccionado debe pertenecer a uno de los artistas relacionados.');
}

if ($mode === 'edit') {
    $post = editorial_post($database, $postId);
    if (!$post || !can_manage_post($post)) { http_response_code(403); flash('No tienes permiso para administrar esta publicación.'); redirect('editorial.php'); }
    $s=$database->prepare('SELECT artista_id FROM blog_artista WHERE blog_id=:id'); $s->execute(['id'=>$postId]); $post['artistas']=array_column($s->fetchAll(),'artista_id');
    $s=$database->prepare('SELECT album_id FROM blog_album WHERE blog_id=:id'); $s->execute(['id'=>$postId]); $post['albumes']=array_column($s->fetchAll(),'album_id');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) $error = 'La sesión del formulario venció. Intenta de nuevo.';
    elseif (($_POST['action'] ?? '') === 'delete') {
        $target = editorial_post($database, (int) ($_POST['id'] ?? 0));
        if (!$target || !can_manage_post($target)) { http_response_code(403); $error='No tienes permiso para eliminar esta publicación.'; }
        else { $s=$database->prepare('DELETE FROM blog WHERE id_blog=:id'); $s->execute(['id'=>$target['id_blog']]); flash('Publicación eliminada.'); redirect('editorial.php'); }
    } else {
        $title=trim((string)($_POST['titulo']??'')); $description=trim((string)($_POST['descripcion']??'')); $tags=trim((string)($_POST['tags']??''));
        $content=clean_editorial_html((string)($_POST['contenido']??'')); $state=in_array($_POST['estado']??'', ['borrador','revision','publicado','archivado'], true) ? $_POST['estado'] : 'borrador';
        try { $artistIds=ids_from_post('artistas'); $albumIds=ids_from_post('albumes'); }
        catch (InvalidArgumentException $exception) { $artistIds=[]; $albumIds=[]; $error=$exception->getMessage(); }
        $post['titulo']=$title; $post['desc']=$description; $post['tags']=$tags; $post['contenido']=$content; $post['estado']=$state; $post['artistas']=$artistIds; $post['albumes']=$albumIds;
        $length=function_exists('mb_strlen') ? mb_strlen($title) : strlen($title);
        if ($error) { /* Conserva los datos enviados para que el usuario pueda corregirlos. */ }
        elseif ($length < 3 || $length > 200) $error='El título debe tener entre 3 y 200 caracteres.';
        elseif (strlen($description)>500) $error='La descripción breve no puede superar 500 caracteres.';
        elseif ($content==='') $error='Escribe el contenido de la publicación.';
        else try {
            $database->beginTransaction();
            validate_post_relations($database, $artistIds, $albumIds);
            $image=upload_publication_image($_FILES['imagen']??[], $post['img']??null);
            $nowPublished=$state==='publicado';
            if ($mode==='edit') {
                $slug=unique_editorial_slug($database,$title,(int)$post['id_blog']);
                $s=$database->prepare('UPDATE blog SET titulo=:title, `desc`=:description, img=:image, tags=:tags, slug=:slug WHERE id_blog=:id');
                $s->execute(['title'=>$title,'description'=>$description ?: null,'image'=>$image,'tags'=>$tags ?: null,'slug'=>$slug,'id'=>$post['id_blog']]);
                $s=$database->prepare("UPDATE blog_detalle SET contenido=:content, estado=:state, fecha_publicacion=CASE WHEN :set_published=1 AND fecha_publicacion IS NULL THEN NOW() WHEN :clear_published=1 THEN NULL ELSE fecha_publicacion END WHERE blog_id=:id");
                $s->execute(['content'=>$content,'state'=>$state,'set_published'=>$nowPublished?1:0,'clear_published'=>$nowPublished?0:1,'id'=>$post['id_blog']]); $blogId=(int)$post['id_blog'];
            } else {
                $slug=unique_editorial_slug($database,$title);
                $s=$database->prepare('INSERT INTO blog (titulo, `desc`, img, tags, slug) VALUES (:title,:description,:image,:tags,:slug)');
                $s->execute(['title'=>$title,'description'=>$description ?: null,'image'=>$image,'tags'=>$tags ?: null,'slug'=>$slug]); $blogId=(int)$database->lastInsertId();
                $s=$database->prepare('INSERT INTO blog_detalle (blog_id, contenido, autor_id, estado, fecha_publicacion) VALUES (:id,:content,:author,:state, CASE WHEN :published=1 THEN NOW() ELSE NULL END)');
                $s->execute(['id'=>$blogId,'content'=>$content,'author'=>(int)$_SESSION['user_id'],'state'=>$state,'published'=>$nowPublished?1:0]);
            }
            foreach (['blog_artista'=>'artistas','blog_album'=>'albumes'] as $table=>$field) { $column=$table==='blog_artista'?'artista_id':'album_id'; $database->prepare("DELETE FROM $table WHERE blog_id=:id")->execute(['id'=>$blogId]); if ($post[$field]) { $s=$database->prepare("INSERT INTO $table (blog_id, $column) VALUES (:blog,:item)"); foreach ($post[$field] as $item) $s->execute(['blog'=>$blogId,'item'=>$item]); } }
            $database->commit(); flash('Publicación guardada.'); redirect('editorial.php?view=edit&id='.$blogId);
        } catch (Throwable $exception) {
            if ($database->inTransaction()) $database->rollBack();
            error_log(sprintf('[SIDE B editorial] save failed: mode=%s post=%d user=%d error=%s', $mode, (int) ($post['id_blog'] ?? 0), (int) ($_SESSION['user_id'] ?? 0), $exception->getMessage()));
            $error='No fue posible guardar la publicación. Verifica los datos e intenta de nuevo.';
        }
    }
}
$artists=$database->query('SELECT id_artista,nombre FROM artista WHERE activo=TRUE ORDER BY nombre')->fetchAll();
$albums=$database->query('SELECT al.id_album, al.artista_id, al.titulo, ar.nombre AS artista FROM album al INNER JOIN artista ar ON ar.id_artista=al.artista_id ORDER BY ar.nombre,al.titulo')->fetchAll();
$selectorArtists = array_map(static fn(array $artist): array => ['id'=>(int)$artist['id_artista'], 'name'=>$artist['nombre']], $artists);
$selectorAlbums = array_map(static fn(array $album): array => ['id'=>(int)$album['id_album'], 'artistId'=>(int)$album['artista_id'], 'artist'=>$album['artista'], 'title'=>$album['titulo']], $albums);
$posts=[];
if ($mode==='list') { $sql='SELECT b.id_blog,b.titulo,b.slug,d.autor_id,d.estado,d.fecha_actualizacion,COALESCE(p.alias,u.nom_usr) autor FROM blog b INNER JOIN blog_detalle d ON d.blog_id=b.id_blog INNER JOIN usuario u ON u.id_usr=d.autor_id LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr'; if (!$isExecutive) $sql.=' WHERE d.autor_id=:user'; $sql.=' ORDER BY d.fecha_actualizacion DESC'; $s=$database->prepare($sql); $s->execute($isExecutive?[]:['user'=>(int)$_SESSION['user_id']]); $posts=$s->fetchAll(); }
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Editorial — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css"><link rel="stylesheet" href="assets/css/global.css"><link rel="stylesheet" href="assets/css/components.css"><link rel="stylesheet" href="assets/css/backstage.css"></head><body>
<header class="site-header"><div class="container header-inner"><a class="brand" href="index.php">SIDE B<span class="brand-dot">.</span></a><nav class="primary-nav"><a class="nav-link" href="index.php">Ver sitio</a><?php if (in_array((int)$_SESSION['role_id'],[3,4],true)): ?><a class="nav-link" href="backstage.php">Backstage</a><?php endif; ?></nav><form class="logout-form" method="post" action="logout.php"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><button type="submit">Cerrar sesión</button></form></div></header>
<main class="backstage-page container"><div class="backstage-heading"><div><p class="eyebrow">Contenido</p><h1>Editorial<br><em>SIDE B.</em></h1></div><p class="backstage-intro"><?= $isExecutive ? 'Gestiona todas las publicaciones y sus autores.' : 'Gestiona únicamente tus propias publicaciones.' ?></p></div>
<?php if ($error): ?><p class="editorial-feedback" role="alert"><?= e($error) ?></p><?php endif; ?><?php if ($message=flash()): ?><p class="editorial-feedback is-success"><?= e($message) ?></p><?php endif; ?>
<?php if ($mode==='list'): ?><div class="editorial-actions"><a class="button button-primary" href="editorial.php?view=new">Crear publicación</a></div><div class="editorial-list"><?php if (!$posts): ?><p class="backstage-note">Aún no hay publicaciones.</p><?php endif; ?><?php foreach($posts as $item): ?><article><div><p class="card-kicker"><?= e($item['estado']) ?></p><h2><?= e($item['titulo']) ?></h2><p>Por <?= e($item['autor']) ?> · Actualizada <?= e(date('d/m/Y',strtotime($item['fecha_actualizacion']))) ?></p></div><div><a class="text-link" href="editorial.php?view=edit&id=<?= (int)$item['id_blog'] ?>">Editar →</a><?php if($item['estado']==='publicado'): ?> <a class="text-link" href="publicacion.php?slug=<?= rawurlencode($item['slug']) ?>">Ver →</a><?php endif; ?></div></article><?php endforeach; ?></div>
<?php else: ?><form class="editorial-form" method="post" enctype="multipart/form-data"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save"><div class="form-field"><label for="titulo">Título</label><input id="titulo" name="titulo" maxlength="200" required value="<?= e($post['titulo']) ?>"></div><div class="form-field"><label for="descripcion">Descripción breve</label><textarea id="descripcion" name="descripcion" maxlength="500"><?= e($post['desc']) ?></textarea></div><div class="form-field"><label for="imagen">Imagen de portada (JPG, PNG o WebP; 5 MB máximo)</label><input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp"><?php if($post['img']): ?><small>Portada actual: <?= e($post['img']) ?></small><?php endif; ?></div><div class="form-field"><label for="tags">Etiquetas (separadas por comas)</label><input id="tags" name="tags" maxlength="255" value="<?= e($post['tags']) ?>"></div><div class="form-field"><label for="contenido">Contenido</label><div class="editor-tools"><button type="button" data-command="bold">Negrita</button><button type="button" data-command="italic">Cursiva</button><button type="button" data-command="formatBlock" data-value="h2">Subtítulo</button><button type="button" data-command="insertUnorderedList">Lista</button></div><div id="editor" class="content-editor" contenteditable="true"><?= $post['contenido'] ?></div><textarea id="contenido" name="contenido" hidden></textarea></div><div class="editorial-form-grid"><div class="form-field"><label for="estado">Estado</label><select id="estado" name="estado"><option value="borrador" <?= $post['estado']==='borrador'?'selected':'' ?>>Borrador</option><option value="revision" <?= $post['estado']==='revision'?'selected':'' ?>>En revisión</option><option value="publicado" <?= $post['estado']==='publicado'?'selected':'' ?>>Publicado</option><option value="archivado" <?= $post['estado']==='archivado'?'selected':'' ?>>Archivado</option></select></div><div class="form-field"><label for="artistas">Artistas relacionados</label><select id="artistas" name="artistas[]" multiple><?php foreach($artists as $artist): ?><option value="<?= (int)$artist['id_artista'] ?>" <?= in_array($artist['id_artista'],$post['artistas'])?'selected':'' ?>><?= e($artist['nombre']) ?></option><?php endforeach; ?></select></div><div class="form-field"><label for="albumes">Álbumes relacionados</label><select id="albumes" name="albumes[]" multiple><?php foreach($albums as $album): ?><option value="<?= (int)$album['id_album'] ?>" <?= in_array($album['id_album'],$post['albumes'])?'selected':'' ?>><?= e($album['artista'].' — '.$album['titulo']) ?></option><?php endforeach; ?></select></div></div><div class="editorial-actions"><button class="button button-secondary" type="button" id="preview">Vista previa</button><button class="button button-primary" type="submit">Guardar publicación</button><a class="text-link" href="editorial.php">Cancelar</a></div></form><section id="preview-pane" class="editorial-preview" hidden><p class="eyebrow">Vista previa</p><h2 id="preview-title"></h2><p id="preview-description"></p><article id="preview-content"></article></section><?php if($mode==='edit'): ?><form method="post" class="delete-form" onsubmit="return confirm('¿Eliminar esta publicación? Esta acción no se puede deshacer.');"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$post['id_blog'] ?>"><button type="submit">Eliminar publicación</button></form><?php endif; ?><?php endif; ?></main>
<script>
const relationshipData = <?= json_encode(['artists'=>$selectorArtists, 'albums'=>$selectorAlbums], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
(() => {
  const artistSelect = document.getElementById('artistas'), albumSelect = document.getElementById('albumes');
  if (!artistSelect || !albumSelect) return;
  const selectedIds = select => new Set([...select.selectedOptions].map(option => Number(option.value)));
  const setSelected = (select, ids) => [...select.options].forEach(option => option.selected = ids.has(Number(option.value)));
  const makeSelector = (select, optionsFor, emptyMessage) => {
    const field = select.closest('.form-field'), label = field.querySelector('label');
    select.classList.add('relationship-native');
    const box = document.createElement('div'); box.className = 'relationship-selector';
    const chips = document.createElement('div'); chips.className = 'relationship-chips'; chips.setAttribute('aria-live', 'polite');
    const input = document.createElement('input'); input.type = 'search'; input.className = 'relationship-search'; input.autocomplete = 'off'; input.placeholder = 'Buscar…'; input.setAttribute('aria-label', label.textContent);
    const list = document.createElement('div'); list.className = 'relationship-options'; list.setAttribute('role', 'listbox'); list.hidden = true;
    const status = document.createElement('p'); status.className = 'relationship-status'; status.setAttribute('aria-live', 'polite');
    box.append(chips, input, list, status); select.after(box);
    let activeIndex = -1, onChange = () => {};
    const options = () => optionsFor();
    const isEnabled = () => !input.disabled;
    const close = () => { list.hidden = true; activeIndex = -1; };
    const render = () => {
      const query = input.value.trim().toLocaleLowerCase(); const values = options(); const selected = selectedIds(select);
      chips.replaceChildren();
      [...selected].forEach(id => {
        const item = relationshipData.artists.concat(relationshipData.albums).find(value => value.id === id && (select === artistSelect ? 'name' in value : 'title' in value));
        if (!item) return;
        const chip = document.createElement('button'); chip.type = 'button'; chip.className = 'relationship-chip';
        chip.textContent = (item.name || `${item.artist} — ${item.title}`) + ' ×'; chip.setAttribute('aria-label', `Quitar ${item.name || item.title}`);
        chip.addEventListener('click', () => { selected.delete(id); setSelected(select, selected); render(); onChange(false); input.focus(); }); chips.append(chip);
      });
      list.replaceChildren();
      if (!values.length) { const empty = document.createElement('p'); empty.className='relationship-empty'; empty.textContent=emptyMessage(); list.append(empty); return; }
      const matches = values.filter(value => (value.name || `${value.artist} ${value.title}`).toLocaleLowerCase().includes(query) && !selected.has(value.id));
      if (!matches.length) { const empty = document.createElement('p'); empty.className='relationship-empty'; empty.textContent='No existen resultados.'; list.append(empty); return; }
      matches.forEach((value, index) => { const option = document.createElement('button'); option.type='button'; option.className='relationship-option'; option.setAttribute('role','option'); option.dataset.index=index; option.textContent=value.name || `${value.artist} — ${value.title}`; option.addEventListener('click', () => { selected.add(value.id); setSelected(select, selected); input.value=''; render(); onChange(false); input.focus(); }); list.append(option); });
    };
    input.addEventListener('focus', () => { if (isEnabled()) { list.hidden=false; render(); } });
    input.addEventListener('input', () => { list.hidden=false; render(); });
    input.addEventListener('keydown', event => { const buttons=[...list.querySelectorAll('.relationship-option')]; if (event.key==='Escape') { close(); return; } if (event.key==='ArrowDown' || event.key==='ArrowUp') { event.preventDefault(); activeIndex=Math.max(0,Math.min(buttons.length-1,activeIndex+(event.key==='ArrowDown'?1:-1))); buttons[activeIndex]?.focus(); } if (event.key==='Enter' && buttons[0]) { event.preventDefault(); buttons[activeIndex < 0 ? 0 : activeIndex].click(); } });
    document.addEventListener('click', event => { if (!box.contains(event.target)) close(); });
    return { render, setEnabled(enabled, message='') { input.disabled=!enabled; box.classList.toggle('is-disabled', !enabled); status.textContent=message; if (!enabled) close(); }, setStatus(message, warning=false) { status.textContent=message; status.classList.toggle('is-warning', warning); }, onChange(callback) { onChange=callback; } };
  };
  const artistPicker = makeSelector(artistSelect, () => relationshipData.artists, () => 'No hay artistas registrados.');
  const albumPicker = makeSelector(albumSelect, () => { const artists=selectedIds(artistSelect); return relationshipData.albums.filter(album => artists.has(album.artistId)); }, () => selectedIds(artistSelect).size ? 'Este artista todavía no tiene álbumes registrados.' : 'Selecciona primero un artista.');
  const refreshAlbums = (initial=false) => { const artists=selectedIds(artistSelect), albums=selectedIds(albumSelect); const invalid=[...albums].filter(id => { const album=relationshipData.albums.find(value => value.id===id); return !album || !artists.has(album.artistId); }); albumPicker.setEnabled(artists.size > 0, artists.size ? '' : 'Selecciona primero un artista.'); if (invalid.length) { if (!initial) { invalid.forEach(id => albums.delete(id)); setSelected(albumSelect, albums); albumPicker.setStatus('Se retiraron álbumes que ya no pertenecen a los artistas seleccionados.', true); } else albumPicker.setStatus('Hay álbumes relacionados que no pertenecen a los artistas seleccionados. Corrige la selección antes de guardar.', true); } albumPicker.render(); };
  artistPicker.onChange(() => { artistPicker.render(); refreshAlbums(); });
  artistPicker.render(); refreshAlbums(true);
})();
</script>
<script>const editor=document.getElementById('editor'),content=document.getElementById('contenido');document.querySelectorAll('[data-command]').forEach(b=>b.onclick=()=>{document.execCommand(b.dataset.command,false,b.dataset.value||null);editor.focus()});document.querySelector('.editorial-form')?.addEventListener('submit',()=>content.value=editor.innerHTML);document.getElementById('preview')?.addEventListener('click',()=>{content.value=editor.innerHTML;document.getElementById('preview-title').textContent=document.getElementById('titulo').value;document.getElementById('preview-description').textContent=document.getElementById('descripcion').value;document.getElementById('preview-content').innerHTML=editor.innerHTML;document.getElementById('preview-pane').hidden=false;document.getElementById('preview-pane').scrollIntoView({behavior:'smooth'})});</script><?= sideb_footer() ?></body></html>
