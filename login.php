<?php
require_once __DIR__ . '/assets/php/bootstrap.php';

if (!empty($_SESSION['user_id'])) redirect(in_array((int) ($_SESSION['role_id'] ?? 0), [3, 4], true) ? 'backstage.php' : 'index.php');

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!valid_csrf()) {
        $error = 'La sesión del formulario venció. Intenta de nuevo.';
    } else {
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');
        if (!$email || $password === '') {
            $error = 'Escribe un correo y contraseña válidos.';
        } else {
            try {
                $statement = db()->prepare('SELECT u.id_usr, u.nom_usr, u.pass, u.tip_usu, r.nombre AS rol FROM usuario AS u INNER JOIN rol AS r ON r.id_rol = u.tip_usu WHERE u.mail = :email AND u.activo = TRUE LIMIT 1');
                $statement->execute(['email' => $email]);
                $user = $statement->fetch();
                if (!$user || !password_verify($password, $user['pass'])) {
                    $error = 'Correo o contraseña incorrectos.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int) $user['id_usr'];
                    $_SESSION['user_name'] = $user['nom_usr'];
                    $_SESSION['role_id'] = (int) $user['tip_usu'];
                    $_SESSION['role_name'] = $user['rol'];
                    redirect(in_array((int) $user['tip_usu'], [3, 4], true) ? 'backstage.php' : 'index.php');
                }
            } catch (Throwable) {
                $error = 'No fue posible iniciar sesión. Revisa la conexión a la base de datos.';
            }
        }
    }
}
$message = $error ?? flash();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Inicia sesión en SIDE B."><title>Iniciar sesión — SIDE B</title>
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
    <aside class="auth-aside"><a class="auth-brand" href="index.php" aria-label="SIDE B, inicio">SIDE B<span>.</span></a><div class="auth-art-copy"><p class="eyebrow">Tu lado de la música</p><h1>Vuelve a<br><em>escuchar.</em></h1><p>Historias, hallazgos y objetos para quienes quieren ir un poco más allá.</p></div><p class="auth-mark">Música · Cultura · Comunidad</p></aside>
    <section class="auth-main" aria-labelledby="login-title"><div class="auth-card">
      <header class="auth-card-header"><p class="eyebrow">Bienvenido de nuevo</p><h2 id="login-title">Inicia sesión</h2><p>Continúa descubriendo lo que ocurre en el otro lado.</p></header>
      <form class="auth-form" method="post" novalidate>
        <?php if ($message): ?><p class="form-feedback is-visible" aria-live="polite"><?= e($message) ?></p><?php endif; ?>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="form-field"><label for="login-email">Correo electrónico</label><input id="login-email" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" required></div>
        <div class="form-field"><label for="login-password">Contraseña</label><input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="••••••••" required></div>
        <div class="form-row"><label class="checkbox-control"><input type="checkbox" name="remember"> Recordarme</label><a class="auth-link" href="#recuperar">¿Olvidaste tu contraseña?</a></div>
        <button class="auth-submit" type="submit">Iniciar sesión <span aria-hidden="true">↗</span></button>
      <p class="form-feedback" data-form-feedback aria-live="polite"></p></form><p class="auth-switch">¿Todavía no tienes cuenta? <a class="auth-link" href="registro.php">Crear cuenta</a></p>
    </div></section>
  </main>
<?= sideb_footer() ?>
</body>
</html>




