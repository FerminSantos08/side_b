<?php
declare(strict_types=1);

require_once __DIR__ . '/comprobante_template.php';

function download_admin_report(PDO $database, string $type, string $title, string $from, string $to): never
{
    $root = dirname(__DIR__, 2);
    if (!is_file($root . '/vendor/autoload.php')) {
        http_response_code(503);
        exit('El generador de PDF no está instalado.');
    }
    require_once $root . '/vendor/autoload.php';

    $params = [];
    $period = '';
    switch ($type) {
        case 'pedidos':
            $columns = ['Pedido', 'Fecha del pedido', 'Estado', 'Importe'];
            $sql = 'SELECT id_pedido, fecha, estado, total FROM pedido WHERE 1=1';
            $labels = ['pendiente' => 'Pendiente', 'pagado' => 'Pagado', 'preparando' => 'En preparación',
                'enviado' => 'Enviado', 'entregado' => 'Entregado', 'cancelado' => 'Cancelado'];
            if ($from !== '') {
                $sql .= ' AND fecha >= :desde';
                $params['desde'] = $from;
            }
            if ($to !== '') {
                $sql .= ' AND DATE(fecha) <= :hasta';
                $params['hasta'] = $to;
            }
            $sql .= ' ORDER BY fecha DESC, id_pedido DESC';
            $period = 'Pedidos por fecha de creación';
            $period .= $from !== '' ? ' desde ' . date('d/m/Y', strtotime($from)) : '';
            $period .= $to !== '' ? ' hasta ' . date('d/m/Y', strtotime($to)) : '';
            $format = static fn(array $row): array => [
                'SB-' . str_pad((string) $row['id_pedido'], 6, '0', STR_PAD_LEFT),
                date('d/m/Y H:i', strtotime($row['fecha'])),
                $labels[$row['estado']] ?? $row['estado'],
                cart_money((float) $row['total']) . ' MXN',
            ];
            break;
        case 'inventario':
            $columns = ['Producto', 'Categoría', 'Estado', 'Stock'];
            $sql = 'SELECT p.nombre, c.nombre AS categoria, p.activo, p.stock
                FROM producto p JOIN categoria_producto c ON c.id_categoria=p.categoria_id
                ORDER BY p.nombre, p.id_producto';
            $format = static fn(array $row): array => [
                $row['nombre'], $row['categoria'], $row['activo'] ? 'Activo' : 'Oculto', $row['stock'],
            ];
            break;
        case 'editorial':
            $columns = ['Publicación', 'Estado', 'Última actualización'];
            $sql = 'SELECT b.titulo, d.estado, d.fecha_actualizacion
                FROM blog b JOIN blog_detalle d ON d.blog_id=b.id_blog
                ORDER BY d.fecha_actualizacion DESC, b.id_blog DESC';
            $labels = ['borrador' => 'Borrador', 'revision' => 'En revisión', 'publicado' => 'Publicado', 'archivado' => 'Archivado'];
            $format = static fn(array $row): array => [
                $row['titulo'], $labels[$row['estado']], date('d/m/Y H:i', strtotime($row['fecha_actualizacion'])),
            ];
            break;
        default:
            throw new InvalidArgumentException('Tipo de reporte inválido.');
    }

    $statement = $database->prepare($sql);
    $statement->execute($params);
    $bodyRows = '';
    $count = 0;
    $lastColumn = count($columns) - 1;
    foreach ($statement as $row) {
        $bodyRows .= '<tr>';
        foreach ($format($row) as $index => $value) {
            $class = $index === $lastColumn ? ' class="amount"' : '';
            $bodyRows .= '<td' . $class . '>' . e((string) $value) . '</td>';
        }
        $bodyRows .= '</tr>';
        $count++;
    }
    if ($count === 0) {
        $bodyRows = '<tr><td class="empty" colspan="' . count($columns) . '">No hay registros para este reporte.</td></tr>';
    }

    $headings = '';
    foreach ($columns as $index => $label) {
        $class = $index === $lastColumn ? ' class="amount"' : '';
        $headings .= '<th' . $class . '>' . e($label) . '</th>';
    }
    $html = sideb_document_header('Reporte de administración', $title, 'Generado el ' . date('d/m/Y'))
        . '<div class="customer-section"><p class="section-label">Resumen</p><h2>'
        . number_format($count) . ($count === 1 ? ' registro' : ' registros') . '</h2>'
        . ($period !== '' ? '<p>' . e($period) . '</p>' : '') . '</div>'
        . '<section class="items-section"><table class="receipt-items"><thead><tr>' . $headings
        . '</tr></thead><tbody>' . $bodyRows . '</tbody></table></section>';

    $pdf = new \Mpdf\Mpdf([
        'format' => 'A4', 'margin_left' => 15, 'margin_right' => 15,
        'margin_top' => 14, 'margin_bottom' => 16, 'tempDir' => sys_get_temp_dir(),
    ]);
    $pdf->SetTitle('Reporte de ' . $title . ' — SIDE B');
    $pdf->SetBasePath($root);
    $pdf->DefHTMLFooterByName('reporte', '<table style="border-top:1px solid #d8d1c5;color:#625f56;font-size:8pt;"><tr><td>SIDE B · Backstage</td><td style="text-align:right;">{PAGENO}/{nbpg}</td></tr></table>');
    $css = (string) file_get_contents($root . '/assets/css/comprobante-pdf.css');
    $pdf->WriteHTML($css . "\n@page { footer: html_reporte; }", \Mpdf\HTMLParserMode::HEADER_CSS);
    $pdf->WriteHTML($html, \Mpdf\HTMLParserMode::HTML_BODY);
    $pdf->Output('sideb-reporte-' . $type . '-' . date('Y-m-d') . '.pdf', \Mpdf\Output\Destination::DOWNLOAD);
    exit;
}
