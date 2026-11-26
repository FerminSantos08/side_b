<?php
declare(strict_types=1);

function sideb_comprobante_pdf_image(string $source): string
{
    if (strtolower(pathinfo($source, PATHINFO_EXTENSION)) !== 'webp') return $source;
    if (!function_exists('imagecreatefromwebp')) return '';

    $root = dirname(__DIR__, 2);
    $path = realpath($root . '/' . $source);
    if ($path === false || !str_starts_with($path, $root . '/')) return '';

    // mPDF converts WebP to JPEG, which discards transparency.
    $image = @imagecreatefromwebp($path);
    if (!$image) return '';
    imagesavealpha($image, true);
    ob_start();
    $written = imagepng($image);
    $png = ob_get_clean();
    return $written ? 'data:image/png;base64,' . base64_encode($png) : '';
}

function sideb_document_header(string $document, string $title, string $date, string $status = ''): string
{
    $escape = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return '<div class="receipt-header"><table class="receipt-columns" role="presentation"><tr>'
        . '<td class="receipt-column brand-block"><img class="receipt-logo" src="assets/favicons/sideb_sf.png" alt=""><br>'
        . '<strong class="brand">SIDE B<span>.</span></strong><p>Música · Cultura · Comunidad</p></td>'
        . '<td class="receipt-column document-block"><p class="eyebrow">' . $escape($document) . '</p>'
        . '<h1>' . $escape($title) . '</h1><p>' . $escape($date) . '</p>'
        . ($status === '' ? '' : '<span class="status">' . $escape($status) . '</span>')
        . '</td></tr></table></div>';
}

/** @param list<array<string,mixed>> $detalles */
function sideb_comprobante_html(array $pedido, array $detalles, ?array $invoice = null): string
{
    $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $money = static fn(mixed $value): string => '$' . number_format((float) $value, 2, '.', ',') . ' MXN';
    $folio = 'SB-' . str_pad((string) (int) $pedido['id_pedido'], 6, '0', STR_PAD_LEFT);
    $status = ($pedido['estado'] ?? '') === 'pagado' ? 'PAGO CONFIRMADO' : 'PAGO CONFIRMADO · ' . strtoupper((string) $pedido['estado']);
    $rows = '';
    foreach ($detalles as $detail) {
        $quantity = max(1, (int) $detail['cantidad']); $price = (float) $detail['precio_unitario'];
        $image = trim((string) ($detail['imagen'] ?? ''));
        $cover = $image !== '' ? '<img class="product-cover" src="' . $e($image) . '" alt="">' : '<span class="cover-fallback">SB</span>';
        $rows .= '<tr><td class="product-cell"><table class="product-layout" role="presentation"><tr><td class="cover">' . $cover . '</td><td class="product-name"><strong>' . $e($detail['nombre_producto']) . '</strong><br><small>Artículo SIDE B</small></td></tr></table></td><td class="center">' . $quantity . '</td><td class="amount">' . $money($price) . '</td><td class="amount">' . $money($quantity * $price) . '</td></tr>';
    }
    if ($rows === '') $rows = '<tr><td class="empty" colspan="4">No hay artículos registrados para este pedido.</td></tr>';
    $date = (string) ($pedido['fecha_pago'] ?: $pedido['fecha']);
    $dateLabel = $date !== '' ? date('d/m/Y · H:i', strtotime($date)) : 'No registrada';
    $subtotal = (float) $pedido['subtotal'];
    $shipping = (float) $pedido['envio'];
    $discount = (float) $pedido['descuento'];
    $tax = (float) $pedido['impuestos'];
    $total = (float) $pedido['total'];
    $includedVat = $invoice !== null && $tax === 0.0;
    if ($includedVat) {
        // Separate included VAT from the amount captured, keeping the total exact to the cent.
        $netTotal = round($total / 1.16, 2);
        $tax = round($total - $netTotal, 2);
        $shipping = round($shipping / 1.16, 2);
        $discount = round($discount / 1.16, 2);
        $subtotal = round($netTotal - $shipping + $discount, 2);
    }
    $priceLabel = $includedVat ? 'Precio c/IVA' : 'Precio';
    $amountLabel = $includedVat ? 'Importe c/IVA' : 'Importe';
    $subtotalLabel = $includedVat ? 'Artículos sin IVA' : 'Subtotal';
    $shippingLabel = $includedVat ? 'Envío sin IVA' : 'Envío';
    $discountLabel = $includedVat ? 'Descuentos sin IVA' : 'Descuentos';
    $taxLabel = $includedVat ? 'IVA incluido (16%)' : 'Impuestos';
    $document = $invoice ? 'Factura de ejemplo' : 'Comprobante de compra';
    $notice = $invoice
        ? 'Documento de demostración sin validez fiscal.'
        : 'Este documento es un comprobante de compra y no constituye una factura fiscal ni un CFDI.';
    $billing = '';
    if ($invoice) {
        $billing = '<div class="customer-section"><p class="section-label">Datos de facturación</p>'
            . '<h2>' . $e($invoice['nombre_fiscal']) . '</h2><p><strong>RFC:</strong> ' . $e($invoice['rfc']) . '</p>'
            . '<p><strong>Código postal fiscal:</strong> ' . $e($invoice['codigo_postal']) . '</p></div>';
    }
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $e($document) . ' ' . $e($folio) . ' — SIDE B</title></head><body><main class="receipt-canvas"><article class="page">'
        . sideb_document_header($document, $folio, $dateLabel, $status)
        . '<div class="customer-section"><table class="receipt-columns" role="presentation"><tr><td class="receipt-column"><p class="section-label">Cliente</p><h2>' . $e($pedido['cliente'] ?: 'No registrado') . '</h2><p>' . $e($pedido['correo'] ?: 'Correo no registrado') . '</p></td><td class="receipt-column"><p class="section-label">Entrega</p><p>' . nl2br($e($pedido['direccion_envio'] ?: 'Dirección no registrada')) . '</p><p class="order-date">Pedido realizado: ' . $e(date('d/m/Y', strtotime((string) $pedido['fecha']))) . '</p></td></tr></table></div>'
        . $billing
        . '<section class="items-section"><p class="section-label">Artículos</p><table class="receipt-items"><thead><tr><th>Producto</th><th class="center">Cant.</th><th class="amount">' . $priceLabel . '</th><th class="amount">' . $amountLabel . '</th></tr></thead><tbody>' . $rows . '</tbody></table></section>'
        . '<div class="summary-section"><table class="receipt-columns" role="presentation"><tr><td class="receipt-column payment-info"><p class="section-label">Pago</p><p><strong>Proveedor:</strong> PayPal</p><p><strong>Orden:</strong> ' . $e($pedido['paypal_order_id'] ?: 'No registrada') . '</p><p><strong>Transacción:</strong> ' . $e($pedido['paypal_capture_id'] ?: 'No registrada') . '</p><p><strong>Confirmación:</strong> ' . $e($dateLabel) . '</p></td><td class="receipt-column"><table class="totals"><tr><td>' . $subtotalLabel . '</td><td>' . $money($subtotal) . '</td></tr><tr><td>' . $shippingLabel . '</td><td>' . $money($shipping) . '</td></tr>' . ($discount != 0.0 ? '<tr><td>' . $discountLabel . '</td><td>−' . $money(abs($discount)) . '</td></tr>' : '') . ($tax != 0.0 ? '<tr><td>' . $taxLabel . '</td><td>' . $money($tax) . '</td></tr>' : '') . '<tr class="grand-total"><td>Total pagado</td><td>' . $money($total) . '</td></tr></table></td></tr></table></div>'
        . '<footer class="receipt-footer"><div><strong>Gracias por escuchar distinto.</strong><p>Tu compra apoya a la música, sus historias y a quienes las hacen posibles.</p></div><p>' . $e($notice) . '</p></footer></article></main></body></html>';
}
