<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__ . '/assets/php/comprobante_template.php';
$userId = require_login(); $orderId = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT);
header('Cache-Control: no-store');
if (!$orderId) { http_response_code(400); exit('Selecciona un pedido válido.'); }
$statement = db()->prepare('SELECT p.*,u.nom_usr,u.mail,u.dir_usr FROM pedido p INNER JOIN usuario u ON u.id_usr=p.usr_id WHERE p.id_pedido=:order AND (p.usr_id=:user OR :is_admin=1) LIMIT 1');
$statement->execute(['order'=>$orderId,'user'=>$userId,'is_admin'=>is_executive_producer()?1:0]); $pedido=$statement->fetch();
if (!$pedido) { http_response_code(404); exit('El pedido no existe o no tienes autorización para consultarlo.'); }
$paidStates=['pagado','preparando','enviado','entregado'];
if (!in_array($pedido['estado'],$paidStates,true) || empty($pedido['paypal_capture_id'])) { http_response_code(409); exit('El comprobante estará disponible sólo después de confirmar el pago.'); }
$invoice = null;
if (($_GET['tipo'] ?? '') === 'factura') {
    $invoiceQuery = db()->prepare('SELECT rfc,nombre_fiscal,codigo_postal
        FROM solicitud_factura WHERE pedido_id=:pedido');
    $invoiceQuery->execute(['pedido' => $orderId]);
    $invoice = $invoiceQuery->fetch();
    if (!$invoice) {
        http_response_code(409);
        exit('Completa los datos de facturación antes de generar el PDF.');
    }
}
$documentName = $invoice ? 'Factura de ejemplo' : 'Comprobante';
$filename = ($invoice ? 'Factura_ejemplo' : 'Comprobante') . '_SB-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
$detailsStatement=db()->prepare('SELECT d.nombre_producto,d.cantidad,d.precio_unitario,p.imagen FROM pedido_detalle d LEFT JOIN producto p ON p.id_producto=d.producto_id WHERE d.pedido_id=:order ORDER BY d.id_detalle');
$detailsStatement->execute(['order'=>$orderId]); $details=$detailsStatement->fetchAll();
// Los pedidos históricos no tenían desglose: se deriva de sus líneas y del total ya cobrado.
$linesSubtotal=array_sum(array_map(static fn(array $line):float=>(float)$line['cantidad']*(float)$line['precio_unitario'],$details));
if ((float)$pedido['subtotal']===0.0 && $linesSubtotal>0.0) { $pedido['subtotal']=$linesSubtotal; $pedido['envio']=max(0,(float)$pedido['total']-$linesSubtotal); }
$pedido['cliente']=$pedido['nom_usr']; $pedido['correo']=$pedido['mail'];
if ((string)($_GET['formato']??'')==='pdf') {
    $autoload=__DIR__.'/vendor/autoload.php'; if(!is_file($autoload)){http_response_code(503);exit('El generador de PDF no está instalado.');} require_once $autoload;
    foreach ($details as &$detail) {
        $detail['imagen'] = sideb_comprobante_pdf_image((string) $detail['imagen']);
    }
    unset($detail);
    $html = sideb_comprobante_html($pedido, $details, $invoice);
    $css=(string)file_get_contents(__DIR__.'/assets/css/comprobante-pdf.css'); $body=preg_replace('~^.*<body[^>]*>|</body>.*$~is','',$html)?:$html;
    $mpdf=new \Mpdf\Mpdf(['format'=>'A4','orientation'=>'P','margin_left'=>15,'margin_right'=>15,'margin_top'=>14,'margin_bottom'=>16,'tempDir'=>sys_get_temp_dir()]);
    $mpdf->SetTitle($documentName . ' SB-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT)); $mpdf->SetBasePath(__DIR__); $mpdf->WriteHTML($css,\Mpdf\HTMLParserMode::HEADER_CSS); $mpdf->WriteHTML($body,\Mpdf\HTMLParserMode::HTML_BODY);
    $mpdf->Output($filename . '.pdf',\Mpdf\Output\Destination::DOWNLOAD); exit;
}
$html = sideb_comprobante_html($pedido, $details, $invoice);
$css=(string)file_get_contents(__DIR__.'/assets/css/comprobante-pdf.css');
$download = '<p class="preview-actions"><a href="comprobante.php?pedido=' . (int) $orderId
    . ($invoice ? '&amp;tipo=factura' : '') . '&amp;formato=pdf">Descargar ' . e(strtolower($documentName)) . ' en PDF</a></p>';
$html = str_replace('</article>', '</article>' . $download, $html);
$html = str_replace('<body>', '<body class="receipt-preview">', $html);
echo str_replace('</head>','<style>'.$css.'</style></head>',$html);
