<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');
$ticket = (string) ($_GET['ticket'] ?? '');
try {
    if (!preg_match('/^[A-Za-z0-9_-]{43}$/', $ticket)) throw new RuntimeException('Enlace no válido.');
    $db = db();
    $db->beginTransaction();
    $q = $db->prepare("SELECT s.usr_id,s.pedido_id FROM mobile_payment_session s
        JOIN usuario u ON u.id_usr=s.usr_id JOIN pedido p ON p.id_pedido=s.pedido_id AND p.usr_id=s.usr_id
        WHERE s.token_hash=:hash AND s.expires_at>NOW() AND s.consumed_at IS NULL
        AND u.activo=TRUE AND p.estado='pendiente' FOR UPDATE");
    $hash = hash('sha256', $ticket);
    $q->execute(['hash' => $hash]);
    $session = $q->fetch();
    if (!$session) throw new RuntimeException('Este enlace venció o ya fue utilizado. Regresa a la app para continuar el pago.');
    $db->prepare('UPDATE mobile_payment_session SET consumed_at=NOW() WHERE token_hash=:hash')->execute(['hash'=>$hash]);
    $db->commit();
    session_regenerate_id(true);
    $_SESSION['mobile_payment_user'] = (int) $session['usr_id'];
    $_SESSION['mobile_payment_order'] = (int) $session['pedido_id'];
    $_SESSION['mobile_payment_until'] = time() + 3600;
    unset($_SESSION['csrf_token']);
    header('Location: pago.php?pedido=' . (int) $session['pedido_id'], true, 303);
    exit;
} catch (Throwable $error) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(410);
    echo '<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pago SIDE B</title><body><h1>Regresa a SIDE B</h1><p>No pudimos abrir este enlace de pago. Puede haber vencido. Solicita uno nuevo desde tu pedido en la app.</p><a href="sideb://payment/result">Volver a SIDE B</a></body></html>';
}
