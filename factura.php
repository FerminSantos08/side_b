<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';

$userId = require_login();
header('Cache-Control: no-store');
$orderId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$orderId) { http_response_code(400); exit('Selecciona un pedido válido.'); }

$adminView = ($_GET['vista'] ?? '') === 'backstage' && is_executive_producer();
$database = db();
$statement = $database->prepare('SELECT usr_id, estado, paypal_capture_id FROM pedido
    WHERE id_pedido=:pedido AND (usr_id=:usuario OR :administrador=1)');
$statement->execute(['pedido' => $orderId, 'usuario' => $userId, 'administrador' => (int) $adminView]);
$order = $statement->fetch();
if (!$order) { http_response_code(404); exit('Pedido no encontrado.'); }
if (!in_array($order['estado'], ['pagado', 'preparando', 'enviado', 'entregado'], true) || empty($order['paypal_capture_id'])) {
    http_response_code(409);
    exit('La solicitud está disponible después de confirmar el pago.');
}

$personType = match ($_POST['tipo_persona'] ?? '') {
    'fisica' => 'fisica',
    'moral' => 'moral',
    default => '',
};
$nameLabels = [
    '' => 'Nombre completo o razón social',
    'fisica' => 'Nombre completo',
    'moral' => 'Razón social',
];
$fields = [
    'rfc' => ['RFC', 13, '[A-ZÑ&]{3,4}[0-9]{6}[A-Z0-9]{3}'],
    'nombre_fiscal' => [$nameLabels[$personType], 254, ''],
    'codigo_postal' => ['Código postal fiscal', 5, '[0-9]{5}'],
];
$requestQuery = $database->prepare('SELECT rfc,nombre_fiscal,codigo_postal
    FROM solicitud_factura WHERE pedido_id=:pedido');
$requestQuery->execute(['pedido' => $orderId]);
$request = $requestQuery->fetch();
$previewUrl = 'comprobante.php?pedido=' . $orderId . '&tipo=factura';
$values = [];
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($adminView) { http_response_code(405); exit; }
    if (!is_string($_POST['csrf_token'] ?? null) || $_POST['csrf_token'] === '' || !valid_csrf()) {
        http_response_code(400);
        $error = 'La sesión del formulario venció. Intenta de nuevo.';
    } elseif (!$request) {
        foreach ($fields as $key => [$label, $length, $pattern]) {
            $input = $_POST[$key] ?? '';
            $value = is_string($input) ? trim($input) : '';
            if ($key === 'rfc') {
                $value = mb_strtoupper($value, 'UTF-8');
            }
            $values[$key] = $value;
            if ($value === '' || mb_strlen($value, 'UTF-8') > $length || ($pattern && !preg_match('~\A' . $pattern . '\z~u', $value))) {
                $error ??= 'Revisa el campo «' . $label . '».';
            }
        }
        if ($error === null) {
            $insert = $database->prepare('INSERT INTO solicitud_factura
                (pedido_id,rfc,nombre_fiscal,codigo_postal)
                SELECT id_pedido,:rfc,:nombre_fiscal,:codigo_postal
                FROM pedido WHERE id_pedido=:pedido AND usr_id=:usuario
                  AND estado IN ("pagado","preparando","enviado","entregado") AND paypal_capture_id<>""
                ON DUPLICATE KEY UPDATE solicitud_factura.pedido_id=solicitud_factura.pedido_id');
            $insert->execute($values + ['pedido' => $orderId, 'usuario' => $userId]);
            header('Location: ' . $previewUrl, true, 303);
            exit;
        }
        http_response_code(422);
    } else {
        header('Location: ' . $previewUrl, true, 303);
        exit;
    }
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Factura de ejemplo — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/purchase-result.css?v=20261119">
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido</a>
<?= sideb_public_header('tienda') ?>
<main id="contenido" class="container purchase-result">
    <section class="purchase-result-card invoice-request">
        <p class="eyebrow">Pedido SB-<?= str_pad((string) $orderId, 6, '0', STR_PAD_LEFT) ?></p>
        <h1><?= $request ? 'Factura de ejemplo disponible' : 'Datos de facturación' ?></h1>
        <?php if ($request): ?>
            <p>Tu documento está listo para consultar o descargar.</p>
            <?php if ($adminView): ?>
                <dl class="invoice-fields">
                    <?php foreach ($fields as $key => [$label]): ?>
                        <div><dt><?= e($label) ?></dt><dd><?= e($request[$key]) ?></dd></div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
            <div class="purchase-actions">
                <a class="button button-primary" href="<?= e($previewUrl . '&formato=pdf') ?>">Descargar PDF</a>
                <a class="button button-secondary" href="<?= e($previewUrl) ?>">Ver factura</a>
            </div>
        <?php elseif ($adminView): ?>
            <p>Este pedido todavía no tiene datos de facturación.</p>
        <?php else: ?>
            <p>Captura los datos que quieres incluir en la factura de ejemplo.</p>
            <?php if ($error): ?><p class="editorial-feedback" role="alert"><?= e($error) ?></p><?php endif; ?>
            <form method="post" class="invoice-fields">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div class="form-field">
                    <label for="tipo_persona">Tipo de persona</label>
                    <select id="tipo_persona" name="tipo_persona">
                        <option value="" data-label="<?= e($nameLabels['']) ?>">Selecciona una opción</option>
                        <option value="fisica" data-label="<?= e($nameLabels['fisica']) ?>"<?= $personType === 'fisica' ? ' selected' : '' ?>>Persona física</option>
                        <option value="moral" data-label="<?= e($nameLabels['moral']) ?>"<?= $personType === 'moral' ? ' selected' : '' ?>>Persona moral</option>
                    </select>
                </div>
                <?php foreach ($fields as $key => [$label, $length, $pattern]): ?>
                    <div class="form-field">
                        <label for="<?= e($key) ?>"><?= e($label) ?></label>
                        <input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($values[$key] ?? '') ?>"
                            maxlength="<?= $length ?>"<?= $pattern && $key !== 'rfc' ? ' pattern="' . e($pattern) . '"' : '' ?>
                            <?= $key === 'codigo_postal' ? 'inputmode="numeric"' : '' ?> required>
                    </div>
                <?php endforeach; ?>
                <button class="button button-primary" type="submit">Generar factura de ejemplo</button>
            </form>
            <script>
            (() => {
                const type = document.getElementById('tipo_persona');
                const label = document.querySelector('label[for="nombre_fiscal"]');
                type.addEventListener('change', () => {
                    label.textContent = type.selectedOptions[0].dataset.label;
                });
            })();
            </script>
        <?php endif; ?>
        <a class="text-link" href="<?= $adminView ? 'admin.php?module=pedidos' : 'pedidos.php' ?>">Volver a pedidos</a>
    </section>
</main>
<?= sideb_footer() ?>
</body>
</html>
