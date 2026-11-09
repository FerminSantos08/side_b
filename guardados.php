<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
$userId = require_login();
if (in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true)) redirect('backstage.php');
$query = db()->prepare('SELECT u.nom_usr,u.fecha_creacion,COALESCE(p.alias,u.nom_usr) alias,p.avatar_url FROM usuario u LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr WHERE u.id_usr=:id');
$query->execute(['id' => $userId]); $profile = $query->fetch();
if (!$profile) { session_destroy(); redirect('login.php'); }
$initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($profile['alias'], 0, 1, 'UTF-8'), 'UTF-8') : strtoupper(substr($profile['alias'], 0, 1));
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Guardados — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css"><link rel="stylesheet" href="assets/css/global.css"><link rel="stylesheet" href="assets/css/components.css"><link rel="stylesheet" href="assets/css/profile.css"><link rel="stylesheet" href="assets/css/profile-menu-icons.css"></head><body><a class="skip-link" href="#contenido">Saltar al contenido</a><?=sideb_public_header('')?><main id="contenido" class="profile-page container"><div class="profile-shell"><aside class="profile-sidebar"><div class="profile-avatar" aria-hidden="true"><?php if($profile['avatar_url']):?><img src="<?=e($profile['avatar_url'])?>" alt=""><?php else:?><?=e($initial)?><?php endif;?></div><h1><?=e($profile['alias'])?></h1><p>@<?=e($profile['nom_usr'])?> · Miembro desde <?=e(date('Y',strtotime($profile['fecha_creacion'])))?></p><?=sideb_profile_menu('guardados')?></aside><section class="profile-content" aria-labelledby="saved-title"><p class="eyebrow">Tu colección personal</p><h2 id="saved-title">Guardados.</h2><p class="profile-note">Todavía no tienes contenido guardado. Cuando encuentres una publicación, artista o producto que quieras conservar, aparecerá aquí.</p></section></div></main><?=sideb_footer()?></body></html>
