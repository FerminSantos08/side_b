<?php
declare(strict_types=1);
ob_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/assets/php/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__.'/assets/php/mobile_payment.php';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !hash_equals($_SESSION['csrf_token'] ?? '', (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) throw new RuntimeException('Solicitud no autorizada.');
    $input = json_decode((string) file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    $action = (string) ($input['action'] ?? ''); $orderId = filter_var($input['order_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$orderId) throw new RuntimeException('Pedido inválido.');
    $userId = mobile_payment_user_id((int)$orderId);
    $database = db();
    $resultRedirect = mobile_payment_return((int)$orderId) ? 'mobile_payment_result.php?pedido='.$orderId : 'pago_exitoso.php?pedido='.$orderId.'&descargar=1';
    $statement = $database->prepare('SELECT * FROM pedido WHERE id_pedido=:id AND usr_id=:user LIMIT 1');
    $statement->execute(['id'=>$orderId,'user'=>$userId]); $order=$statement->fetch();
    if (!$order) throw new RuntimeException('Pedido no encontrado.');

    if ($action === 'create') {
        $database->beginTransaction();
        $lock = $database->prepare('SELECT * FROM pedido WHERE id_pedido=:id AND usr_id=:user FOR UPDATE');
        $lock->execute(['id'=>$orderId,'user'=>$userId]); $order=$lock->fetch();
        if ($order['estado'] !== 'pendiente') throw new RuntimeException('Este pedido ya fue procesado.');
        if (!empty($order['paypal_order_id'])) {
            $existing = paypal_request('GET', '/v2/checkout/orders/' . rawurlencode((string) $order['paypal_order_id']));
            if (($existing['status'] ?? '') === 'COMPLETED') {
                $database->commit(); ob_clean(); echo json_encode(['id'=>$order['paypal_order_id'], 'completed'=>true]); exit;
            }
            if (in_array(($existing['status'] ?? ''), ['CREATED','APPROVED','SAVED','PAYER_ACTION_REQUIRED'], true)) {
                $database->commit(); ob_clean(); echo json_encode(['id'=>$order['paypal_order_id']]); exit;
            }
        }
        $paypal = paypal_request('POST', '/v2/checkout/orders', ['intent'=>'CAPTURE','purchase_units'=>[['reference_id'=>'sideb-'.$orderId,'custom_id'=>(string)$orderId,'amount'=>['currency_code'=>'MXN','value'=>number_format((float)$order['total'],2,'.','')]]]], 'sideb-create-'.$orderId);
        if (empty($paypal['id'])) throw new RuntimeException('PayPal no devolvió una orden válida.');
        $database->prepare('UPDATE pedido SET paypal_order_id=:paypal WHERE id_pedido=:id AND usr_id=:user AND estado=\'pendiente\'')->execute(['paypal'=>$paypal['id'],'id'=>$orderId,'user'=>$userId]);
        $database->commit(); ob_clean(); echo json_encode(['id'=>$paypal['id']]); exit;
    }

    if ($action === 'capture') {
        $paypalOrderId = trim((string) ($input['paypal_order_id'] ?? ''));
        $database->beginTransaction();
        $locked = $database->prepare('SELECT * FROM pedido WHERE id_pedido=:id AND usr_id=:user FOR UPDATE');
        $locked->execute(['id'=>$orderId,'user'=>$userId]); $order=$locked->fetch();
        if (!$order || $paypalOrderId === '' || $paypalOrderId !== (string)$order['paypal_order_id']) throw new RuntimeException('La orden de PayPal no coincide con el pedido.');
        if (in_array($order['estado'], ['pagado','preparando','enviado','entregado'], true) && !empty($order['paypal_capture_id'])) {
            $database->commit(); ob_clean(); echo json_encode(['ok'=>true,'redirect'=>$resultRedirect]); exit;
        }
        if ($order['estado'] !== 'pendiente') throw new RuntimeException('El pedido ya fue procesado.');
        $details=$database->prepare('SELECT d.*,p.stock,p.activo FROM pedido_detalle d JOIN producto p ON p.id_producto=d.producto_id WHERE d.pedido_id=:order ORDER BY d.producto_id FOR UPDATE');
        $details->execute(['order'=>$orderId]); $items=$details->fetchAll();
        if (!$items) throw new RuntimeException('El pedido no contiene productos.');
        foreach($items as $item) if(!$item['activo'] || (int)$item['stock'] < (int)$item['cantidad']) throw new RuntimeException('El inventario cambió. No se realizó una nueva captura. Contacta a SIDE B para revisar tu pedido.');
        try {
            $paypal = paypal_request('POST', '/v2/checkout/orders/' . rawurlencode($paypalOrderId) . '/capture', null, 'sideb-capture-'.$orderId);
        } catch (RuntimeException $captureError) {
            $paypal = paypal_request('GET', '/v2/checkout/orders/' . rawurlencode($paypalOrderId));
            if (($paypal['status'] ?? '') !== 'COMPLETED') throw $captureError;
        }
        $capture = $paypal['purchase_units'][0]['payments']['captures'][0] ?? null;
        if (($paypal['status'] ?? '') !== 'COMPLETED' || !is_array($capture) || ($capture['status'] ?? '') !== 'COMPLETED' || ($capture['amount']['currency_code'] ?? '') !== 'MXN' || ($capture['amount']['value'] ?? '') !== number_format((float)$order['total'],2,'.','')) throw new RuntimeException('PayPal no confirmó el pago esperado.');
        $decrease=$database->prepare('UPDATE producto SET stock=stock-:quantity WHERE id_producto=:product AND stock>=:minimum_quantity');
        foreach($items as $item){$decrease->execute(['quantity'=>$item['cantidad'],'minimum_quantity'=>$item['cantidad'],'product'=>$item['producto_id']]);if($decrease->rowCount()!==1)throw new RuntimeException('No fue posible confirmar el inventario.');}
        $paid=$database->prepare("UPDATE pedido SET estado='pagado',paypal_capture_id=:capture,fecha_pago=NOW() WHERE id_pedido=:id AND estado='pendiente'");
        $paid->execute(['capture'=>$capture['id'],'id'=>$orderId]); if($paid->rowCount()!==1)throw new RuntimeException('No fue posible registrar el pago.');
        $database->commit(); ob_clean(); echo json_encode(['ok'=>true,'redirect'=>$resultRedirect]); exit;
    }
    throw new RuntimeException('Acción de PayPal no válida.');
} catch (Throwable $exception) {
    if (isset($database) && $database->inTransaction()) $database->rollBack();
    ob_clean(); http_response_code(422); echo json_encode(['error'=>$exception->getMessage()]);
}
