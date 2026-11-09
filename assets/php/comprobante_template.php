<?php
declare(strict_types=1);

/** @param list<array<string,mixed>> $detalles */
function sideb_comprobante_html(array $pedido, array $detalles): string
{
    $e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $money = static fn(mixed $value): string => '$' . number_format((float) $value, 2, '.', ',') . ' MXN';
    $folio = 'SB-' . str_pad((string) (int) $pedido['id_pedido'], 6, '0', STR_PAD_LEFT);
    $status = ($pedido['estado'] ?? '') === 'pagado' ? 'PAGO CONFIRMADO' : 'PAGO CONFIRMADO · ' . strtoupper((string) $pedido['estado']);
    $rows = '';
    foreach ($detalles as $detail) {
        $quantity = max(1, (int) $detail['cantidad']); $price = (float) $detail['precio_unitario'];
        $image = trim((string) ($detail['imagen'] ?? ''));
        $cover = $image !== '' ? '<img class="product-cover" src="' . $e($image) . '" alt="">' : '<span class="cover-fallback" aria-hidden="true">SB</span>';
        $rows .= '<tr><td class="product-cell"><span class="cover">' . $cover . '</span><span><strong>' . $e($detail['nombre_producto']) . '</strong><small>Artículo SIDE B</small></span></td><td class="center">' . $quantity . '</td><td class="amount">' . $money($price) . '</td><td class="amount">' . $money($quantity * $price) . '</td></tr>';
    }
    if ($rows === '') $rows = '<tr><td class="empty" colspan="4">No hay artículos registrados para este pedido.</td></tr>';
    $date = (string) ($pedido['fecha_pago'] ?: $pedido['fecha']);
    $dateLabel = $date !== '' ? date('d/m/Y · H:i', strtotime($date)) : 'No registrada';
    $shipping = (float) $pedido['envio']; $discount = (float) $pedido['descuento']; $tax = (float) $pedido['impuestos'];
    return '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Comprobante ' . $e($folio) . ' — SIDE B</title></head><body><main class="receipt-canvas"><article class="page">'
        . '<header class="receipt-header"><div class="brand-block"><div class="brand">SIDE B<span>.</span></div><p>Música · Cultura · Comunidad</p></div><div class="document-block"><p class="eyebrow">Comprobante de compra</p><h1>' . $e($folio) . '</h1><p>' . $e($dateLabel) . '</p><span class="status">' . $e($status) . '</span></div></header>'
        . '<section class="customer-section"><div><p class="section-label">Cliente</p><h2>' . $e($pedido['cliente'] ?: 'No registrado') . '</h2><p>' . $e($pedido['correo'] ?: 'Correo no registrado') . '</p></div><div><p class="section-label">Entrega</p><p>' . nl2br($e($pedido['direccion_envio'] ?: 'Dirección no registrada')) . '</p><p class="order-date">Pedido realizado: ' . $e(date('d/m/Y', strtotime((string) $pedido['fecha']))) . '</p></div></section>'
        . '<section class="items-section"><p class="section-label">Artículos</p><table><thead><tr><th>Producto</th><th class="center">Cant.</th><th class="amount">Precio</th><th class="amount">Importe</th></tr></thead><tbody>' . $rows . '</tbody></table></section>'
        . '<section class="summary-section"><div class="payment-info"><p class="section-label">Pago</p><p><strong>Proveedor:</strong> PayPal</p><p><strong>Orden:</strong> ' . $e($pedido['paypal_order_id'] ?: 'No registrada') . '</p><p><strong>Transacción:</strong> ' . $e($pedido['paypal_capture_id'] ?: 'No registrada') . '</p><p><strong>Confirmación:</strong> ' . $e($dateLabel) . '</p></div><table class="totals"><tr><td>Subtotal</td><td>' . $money($pedido['subtotal']) . '</td></tr><tr><td>Envío</td><td>' . $money($shipping) . '</td></tr>' . ($discount != 0.0 ? '<tr><td>Descuentos</td><td>−' . $money(abs($discount)) . '</td></tr>' : '') . ($tax != 0.0 ? '<tr><td>Impuestos</td><td>' . $money($tax) . '</td></tr>' : '') . '<tr class="grand-total"><td>Total pagado</td><td>' . $money($pedido['total']) . '</td></tr></table></section>'
        . '<footer class="receipt-footer"><div><strong>Gracias por escuchar distinto.</strong><p>Tu compra apoya a la música, sus historias y a quienes las hacen posibles.</p></div><p>Este documento es un comprobante de compra y no constituye una factura fiscal ni un CFDI.</p></footer></article></main></body></html>';
}
