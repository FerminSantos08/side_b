<?php
declare(strict_types=1);require_once __DIR__.'/assets/php/bootstrap.php';$userId=require_login();if(in_array((int)($_SESSION['role_id']??0),[3,4],true))redirect('backstage.php');$db=db();
if($_SERVER['REQUEST_METHOD']==='POST'){try{if(!valid_csrf())throw new RuntimeException('La sesión del formulario venció.');$a=$_POST['action']??'';if($a==='profile'){$alias=trim((string)$_POST['alias']);$bio=trim((string)$_POST['biografia']);if($alias===''||mb_strlen($alias)>50||mb_strlen($bio)>300)throw new RuntimeException('Revisa el alias y la biografía.');$s=$db->prepare('SELECT avatar_url FROM perfil_usuario WHERE usr_id=:id');$s->execute(['id'=>$userId]);$avatar=upload_profile_avatar($_FILES['avatar']??[],$s->fetchColumn()?:null);$db->prepare('INSERT INTO perfil_usuario(usr_id,alias,biografia,avatar_url) VALUES(:id,:alias,:bio,:avatar) ON DUPLICATE KEY UPDATE alias=VALUES(alias),biografia=VALUES(biografia),avatar_url=VALUES(avatar_url)')->execute(['id'=>$userId,'alias'=>$alias,'bio'=>$bio?:null,'avatar'=>$avatar]);flash('Perfil actualizado.');}elseif($a==='address'){$label=trim((string)$_POST['label']);$address=trim((string)$_POST['address']);if($label===''||$address===''||mb_strlen($label)>60||mb_strlen($address)>255)throw new RuntimeException('Escribe una dirección válida.');$main=isset($_POST['principal']);$db->beginTransaction();if($main)$db->prepare('UPDATE direccion_usuario SET es_principal=FALSE WHERE usr_id=:id')->execute(['id'=>$userId]);$db->prepare('INSERT INTO direccion_usuario(usr_id,etiqueta,direccion,es_principal) VALUES(:u,:l,:d,:m)')->execute(['u'=>$userId,'l'=>$label,'d'=>$address,'m'=>$main]);if($main)$db->prepare('UPDATE usuario SET dir_usr=:d WHERE id_usr=:u')->execute(['d'=>$address,'u'=>$userId]);$db->commit();flash('Dirección guardada.');}elseif($a==='main_address'){$id=filter_input(INPUT_POST,'address_id',FILTER_VALIDATE_INT)?:0;$db->beginTransaction();$s=$db->prepare('SELECT direccion FROM direccion_usuario WHERE id_direccion=:id AND usr_id=:u');$s->execute(['id'=>$id,'u'=>$userId]);$d=$s->fetchColumn();if(!$d)throw new RuntimeException('Dirección no encontrada.');$db->prepare('UPDATE direccion_usuario SET es_principal=FALSE WHERE usr_id=:u')->execute(['u'=>$userId]);$db->prepare('UPDATE direccion_usuario SET es_principal=TRUE WHERE id_direccion=:id AND usr_id=:u')->execute(['id'=>$id,'u'=>$userId]);$db->prepare('UPDATE usuario SET dir_usr=:d WHERE id_usr=:u')->execute(['d'=>$d,'u'=>$userId]);$db->commit();flash('Dirección principal actualizada.');}redirect('perfil.php');}catch(Throwable $e){if($db->inTransaction())$db->rollBack();flash($e->getMessage());redirect('perfil.php');}}
$s=$db->prepare('SELECT u.nom_usr,u.mail,u.fecha_creacion,COALESCE(p.alias,u.nom_usr) alias,p.biografia,p.avatar_url FROM usuario u LEFT JOIN perfil_usuario p ON p.usr_id=u.id_usr WHERE u.id_usr=:id');$s->execute(['id'=>$userId]);$p=$s->fetch();if(!$p){session_destroy();redirect('login.php');}$s=$db->prepare('SELECT * FROM direccion_usuario WHERE usr_id=:id ORDER BY es_principal DESC,fecha_creacion DESC');$s->execute(['id'=>$userId]);$addresses=$s->fetchAll();$initial=mb_strtoupper(mb_substr($p['alias'],0,1,'UTF-8'),'UTF-8');$message=flash();
require_once __DIR__ . '/assets/php/account_layout.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Mi perfil — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile-menu-icons.css?v=20261118">
    <link rel="stylesheet" href="assets/css/profile-edit.css?v=20261119-2">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('') ?>
<main id="contenido" class="profile-page account-page container">
    <header class="account-heading">
        <p class="eyebrow">Tu espacio en SIDE B</p>
        <h1>Mi perfil<em>.</em></h1>
    </header>
    <?php if ($message): ?><p class="editorial-feedback" role="status"><?= e($message) ?></p><?php endif; ?>

    <div class="profile-shell account-layout">
        <?php sideb_account_identity($p, $initial, 'perfil'); ?>

        <div class="account-panels">
            <section class="account-card" aria-labelledby="personal-title">
                <h2 id="personal-title">Datos personales</h2>
                <dl class="account-facts">
                    <div><dt>Alias</dt><dd><?= e($p['alias']) ?></dd></div>
                    <div><dt>Correo electrónico</dt><dd><?= e($p['mail']) ?></dd></div>
                    <div><dt>Biografía</dt><dd><?= e($p['biografia'] ?: 'Sin biografía') ?></dd></div>
                </dl>
                <details class="profile-edit">
                    <summary>Editar datos y foto</summary>
                    <form class="profile-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="profile">
                        <label>Foto de perfil<input class="field-control" type="file" name="avatar" accept="image/jpeg,image/png,image/webp"></label>
                        <label>Alias<input class="field-control" name="alias" value="<?= e($p['alias']) ?>" required></label>
                        <label>Biografía<textarea class="field-control" name="biografia"><?= e($p['biografia'] ?? '') ?></textarea></label>
                        <button class="button button-primary" type="submit">Guardar cambios</button>
                    </form>
                </details>
            </section>

            <section class="account-card" aria-labelledby="addresses-title">
                <h2 id="addresses-title">Mis direcciones</h2>
                <?php if (!$addresses): ?><p class="account-empty">Aún no tienes direcciones guardadas.</p><?php endif; ?>
                <div class="account-addresses">
                    <?php foreach ($addresses as $address): ?>
                        <article class="account-address">
                            <h3><?= e($address['etiqueta']) ?>
                                <?php if ($address['es_principal']): ?><span class="account-address-badge">Principal</span><?php endif; ?>
                            </h3>
                            <p><?= e($address['direccion']) ?></p>
                            <?php if (!$address['es_principal']): ?>
                                <form method="post">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="main_address">
                                    <input type="hidden" name="address_id" value="<?= (int) $address['id_direccion'] ?>">
                                    <button class="button button-secondary" type="submit">Usar como principal</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
                <details class="profile-edit">
                    <summary>Añadir dirección</summary>
                    <form class="profile-form" method="post">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="address">
                        <label>Etiqueta<input class="field-control" name="label" value="Casa" required></label>
                        <label>Dirección completa<textarea class="field-control" name="address" required></textarea></label>
                        <label class="check"><input type="checkbox" name="principal" <?= !$addresses ? 'checked' : '' ?>> Usar como principal</label>
                        <button class="button button-primary" type="submit">Guardar dirección</button>
                    </form>
                </details>
            </section>
        </div>
    </div>
</main>
<?= sideb_footer() ?>
</body>
</html>
