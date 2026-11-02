<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

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
<link rel="icon" href="assets/favicons/favicon.ico" sizes="any">
<link rel="icon" href="assets/favicons/favicon.svg" type="image/svg+xml">
<link rel="icon" href="assets/favicons/favicon-96x96.png" type="image/png" sizes="96x96">
<link rel="apple-touch-icon" href="assets/favicons/apple-touch-icon.png">
<link rel="manifest" href="assets/favicons/site.webmanifest">
  <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/variables.css"><link rel="stylesheet" href="assets/css/global.css"><link rel="stylesheet" href="assets/css/components.css"><link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
  <main class="auth-page">
    <aside class="auth-aside"><a class="auth-brand" href="index.php" aria-label="SIDE B, inicio">SIDE B<span>.</span></a><div class="auth-art-copy"><p class="eyebrow">Una comunidad con curiosidad</p><h1>Únete al<br><em>otro lado.</em></h1><p>Guarda lo que te mueve, comparte tus hallazgos y escucha con otras personas.</p></div><p class="auth-mark">Música · Cultura · Comunidad</p></aside>
    <section class="auth-main" aria-labelledby="register-title"><div class="auth-card">
      <header class="auth-card-header"><p class="eyebrow">Empieza tu recorrido</p><h2 id="register-title">Crea tu cuenta</h2><p>Forma parte de una comunidad que escucha con curiosidad.</p></header>
      <form class="auth-form" method="post" novalidate>
        <?php if ($error): ?><p class="form-feedback is-visible" aria-live="polite"><?= e($error) ?></p><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="register-name">Nombre de usuario</label><input id="register-name" name="username" type="text" autocomplete="username" placeholder="tu_nombre" required></div>
        <div class="form-field"><label for="register-email">Correo electrónico</label><input id="register-email" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" required></div>
        <div class="form-field"><label for="register-password">Contraseña</label><input id="register-password" name="password" type="password" autocomplete="new-password" placeholder="••••••••" minlength="8" required></div>
        <p class="password-note">Usa al menos 8 caracteres.</p>
        <div class="form-field"><label for="register-confirm">Confirmar contraseña</label><input id="register-confirm" name="password_confirmation" type="password" autocomplete="new-password" placeholder="••••••••" minlength="8" required></div>
        <label class="checkbox-control"><input type="checkbox" name="terms" required> Acepto los términos y condiciones</label>
        <button class="auth-submit" type="submit">Crear cuenta <span aria-hidden="true">↗</span></button>
      <p class="form-feedback" data-form-feedback aria-live="polite"></p></form><p class="auth-switch">¿Ya tienes cuenta? <a class="auth-link" href="login.php">Inicia sesión</a></p>
    </div></section>
  </main>
<?= sideb_footer() ?>
</body>
</html>




