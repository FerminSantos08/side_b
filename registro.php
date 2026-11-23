<?php
require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__ . '/assets/php/auth_layout.php';

if (!empty($_SESSION['user_id'])) redirect(in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true) ? 'backstage.php' : 'index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = (string) ($_POST['password'] ?? '');
    $confirmation = (string) ($_POST['password_confirmation'] ?? '');
    if (!valid_csrf()) {
        $error = 'La sesión del formulario venció. Intenta de nuevo.';
    } elseif (strlen($username) < 3 || strlen($username) > 100) {
        $error = 'El nombre de usuario debe tener entre 3 y 100 caracteres.';
    } elseif (!$email) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif (strlen($password) < 8) {
        $error = 'La contraseña debe tener al menos 8 caracteres.';
    } elseif (!hash_equals($password, $confirmation)) {
        $error = 'Las contraseñas no coinciden.';
    } elseif (empty($_POST['terms'])) {
        $error = 'Debes aceptar los términos y condiciones.';
    } else {
        try {
            $database = db();
            $database->beginTransaction();
            $insertUser = $database->prepare('INSERT INTO usuario (nom_usr, mail, pass, tip_usu) VALUES (:name, :email, :password, 1)');
            $insertUser->execute(['name' => $username, 'email' => $email, 'password' => password_hash($password, PASSWORD_DEFAULT)]);
            $insertProfile = $database->prepare('INSERT INTO perfil_usuario (usr_id, alias) VALUES (:id, :alias)');
            $insertProfile->execute(['id' => (int) $database->lastInsertId(), 'alias' => $username]);
            $database->commit();
            flash('Cuenta creada. Ya puedes iniciar sesión.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if (isset($database) && $database->inTransaction()) $database->rollBack();
            $error = $exception->getCode() === '23000' ? 'Ese correo ya está registrado.' : 'No fue posible crear la cuenta. Revisa la conexión a la base de datos.';
        } catch (Throwable) {
            if (isset($database) && $database->inTransaction()) $database->rollBack();
            $error = 'No fue posible crear la cuenta. Intenta más tarde.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Crea tu cuenta en SIDE B."><title>Crear cuenta — SIDE B</title>
<link rel="icon" href="assets/favicons/sideb_sf.png" type="image/png">
<link rel="apple-touch-icon" href="assets/favicons/sideb_sf.png">
<link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/auth.css?v=20261119-3">
</head>
<body class="auth-body">
  <?php sideb_auth_header(); ?>
  <main id="contenido" class="auth-page auth-page--register">
    <section class="auth-main" aria-labelledby="register-title"><div class="auth-card">
      <header class="auth-card-header"><p class="eyebrow">Empieza tu recorrido</p><h1 id="register-title">Crea tu cuenta</h1><p>Forma parte de una comunidad que escucha con curiosidad.</p></header>
      <form class="auth-form" method="post" novalidate>
        <?php if ($error): ?><p class="auth-feedback" data-state="error" role="alert"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="register-name">Nombre de usuario</label><input id="register-name" name="username" type="text" autocomplete="username" placeholder="tu_nombre" value="<?= e($username ?? '') ?>" required></div>
        <div class="form-field"><label for="register-email">Correo electrónico</label><input id="register-email" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" value="<?= e(is_string($_POST['email'] ?? null) ? $_POST['email'] : '') ?>" required></div>
        <?php sideb_auth_password('register-password', 'password', 'Contraseña', 'new-password', 'Usa al menos 8 caracteres.'); ?>
        <?php sideb_auth_password('register-confirm', 'password_confirmation', 'Confirmar contraseña', 'new-password', 'Repite tu contraseña.'); ?>
        <label class="checkbox-control"><input type="checkbox" name="terms" required> Acepto los términos y condiciones</label>
        <button class="button button-primary auth-submit" type="submit">Crear cuenta <span aria-hidden="true">↗</span></button>
      </form>
      <p class="auth-switch">¿Ya tienes cuenta? <a class="auth-link" href="login.php">Inicia sesión</a></p>
    </div></section>
    <aside class="auth-aside"><div class="auth-art-copy"><p class="eyebrow">Una comunidad con curiosidad</p><h2>Únete al<br><em>otro lado.</em></h2><p>Guarda lo que te mueve, comparte tus hallazgos y escucha con otras personas.</p></div><p class="auth-mark">Música · Cultura · Comunidad</p></aside>
  </main>
<?php sideb_auth_footer(); ?>
<script src="assets/js/auth.js?v=20261119-2" defer></script>
</body>
</html>
