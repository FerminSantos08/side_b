<?php
declare(strict_types=1);

require_once __DIR__ . '/assets/php/bootstrap.php';
require_executive_producer();
header('Cache-Control: no-store');

$reports = [
    'pedidos' => 'Pedidos',
    'inventario' => 'Inventario',
    'editorial' => 'Publicaciones',
];
$type = $_GET['tipo'] ?? 'pedidos';
$from = $_GET['desde'] ?? '';
$to = $_GET['hasta'] ?? '';
$error = '';

function report_date_is_valid(mixed $value): bool
{
    return $value === '' || (is_string($value)
        && preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $value, $parts)
        && (int) $parts[1] >= 1000 && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]));
}

if (isset($_GET['descargar'])) {
    if (!is_string($type) || !isset($reports[$type])) {
        $error = 'Selecciona un tipo de reporte válido.';
    } elseif ($type === 'pedidos' && (!report_date_is_valid($from) || !report_date_is_valid($to)
        || ($from !== '' && $to !== '' && $from > $to))) {
        $error = 'Revisa el rango de fechas de los pedidos.';
    } else {
        require __DIR__ . '/assets/php/reporte_pdf.php';
        download_admin_report(db(), $type, $reports[$type], $type === 'pedidos' ? $from : '', $type === 'pedidos' ? $to : '');
    }
    http_response_code(400);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes — SIDE B</title>
    <link rel="stylesheet" href="assets/css/variables.css?v=20261120-7">
    <link rel="stylesheet" href="assets/css/global.css?v=20261118">
    <link rel="stylesheet" href="assets/css/components.css?v=20261118">
    <link rel="stylesheet" href="assets/css/backstage.css?v=20261120-6">
    <link rel="stylesheet" href="assets/css/admin-navigation.css?v=20261120-2">
</head>
<body>
<?= sideb_admin_header() ?>
<main class="backstage-page container">
    <header class="backstage-heading">
        <div><p class="eyebrow">Administración</p><h1>Reportes PDF</h1></div>
    </header>
    <?php if ($error !== ''): ?><p class="editorial-feedback" role="alert"><?= e($error) ?></p><?php endif; ?>
    <form class="editorial-form" method="get">
        <div class="form-field">
            <label for="tipo">Tipo de reporte</label>
            <select id="tipo" name="tipo">
                <?php foreach ($reports as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $type === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <fieldset id="fechas">
            <legend>Fechas de pedido (opcionales)</legend>
            <div class="editorial-form-grid">
                <div class="form-field">
                    <label for="desde">Desde</label>
                    <input id="desde" name="desde" type="date" value="<?= is_string($from) ? e($from) : '' ?>">
                </div>
                <div class="form-field">
                    <label for="hasta">Hasta</label>
                    <input id="hasta" name="hasta" type="date" value="<?= is_string($to) ? e($to) : '' ?>">
                </div>
            </div>
        </fieldset>
        <div class="editorial-actions">
            <button class="button button-primary" name="descargar" value="1">Descargar PDF</button>
        </div>
    </form>
</main>
<?= sideb_footer() ?>
<script>
const reportType = document.getElementById('tipo');
const reportDates = document.getElementById('fechas');
function updateReportDates() {
    reportDates.hidden = reportDates.disabled = reportType.value !== 'pedidos';
}
reportType.addEventListener('change', updateReportDates);
updateReportDates();
</script>
</body>
</html>
