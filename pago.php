<?php
declare(strict_types=1);
require_once __DIR__ . '/assets/php/bootstrap.php';
require_once __DIR__.'/assets/php/mobile_payment.php';
$orderId=filter_input(INPUT_GET,'pedido',FILTER_VALIDATE_INT); $userId=mobile_payment_user_id((int)$orderId); if(!$orderId){http_response_code(400);exit('Pedido inválido.');}
$statement=db()->prepare('SELECT * FROM pedido WHERE id_pedido=:id AND usr_id=:user'); $statement->execute(['id'=>$orderId,'user'=>$userId]); $order=$statement->fetch(); if(!$order){http_response_code(404);exit('Pedido no encontrado.');}
$paypalReady=true; try{$paypal=paypal_config();}catch(Throwable){$paypalReady=false;$paypal=['client_id'=>''];}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Pago con PayPal — SIDE B</title><link rel="stylesheet" href="assets/css/variables.css?v=20261120-7"><link rel="stylesheet" href="assets/css/global.css?v=20261118"><link rel="stylesheet" href="assets/css/components.css?v=20261118"><link rel="stylesheet" href="assets/css/home.css?v=20261118"><link rel="stylesheet" href="assets/css/cart.css?v=20261118"></head><body><a class="skip-link" href="#contenido">Saltar al contenido</a><?=sideb_public_header('tienda')?><main id="contenido" class="cart-page container"><h1>Pago.</h1><section class="cart-summary payment-panel"><h2>Pedido #<?=$orderId?></h2><div class="summary-line summary-total"><span>Total</span><span><?=cart_money((float)$order['total'])?> MXN</span></div><?php if($order['estado']==='pagado'):?><p>Tu pago fue confirmado.</p><a class="button button-primary" href="comprobante.php?pedido=<?=$orderId?>">Ver comprobante</a><?php elseif(!$paypalReady):?><p class="editorial-feedback" role="alert">PayPal no está configurado. Agrega las credenciales de Sandbox o Live al entorno del servidor.</p><?php else:?><p>Serás redirigido de forma segura a PayPal para aprobar el pago.</p><div id="paypal-button-container"></div><p id="paypal-feedback" class="editorial-feedback" hidden role="alert"></p><script src="https://www.paypal.com/sdk/js?client-id=<?=rawurlencode($paypal['client_id'])?>&currency=MXN&intent=capture"></script><script>
const mobilePayment=<?=mobile_payment_return((int)$orderId)?'true':'false'?>;
const csrf=<?=json_encode(csrf_token())?>, localOrder=<?=$orderId?>, feedback=document.getElementById('paypal-feedback');
async function paypalApi(payload){
  const response=await fetch('paypal_api.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify(payload)});
  const body=await response.text();
  let data;
  try{data=JSON.parse(body);}catch{
    console.error('Respuesta no JSON de paypal_api.php:',body);
    throw new Error('El servidor devolvió un error técnico al procesar PayPal. Revisa el registro de errores de Apache.');
  }
  if(!response.ok)throw new Error(data.error||'No fue posible procesar el pago.');
  return data;
}
let paypalButtons;
paypalButtons=paypal.Buttons({
  createOrder:()=>paypalApi({action:'create',order_id:localOrder}).then(data=>{
    if(!data.completed)return data.id;
    return paypalApi({action:'capture',order_id:localOrder,paypal_order_id:data.id}).then(result=>{
      window.location.assign(result.redirect);
      return data.id;
    });
  }),
  onApprove:data=>paypalApi({action:'capture',order_id:localOrder,paypal_order_id:data.orderID})
    .then(result=>window.location.assign(result.redirect))
    .catch(error=>{
      if(error.message==='PAYPAL_INSTRUMENT_DECLINED') return paypalButtons.restart();
      throw error;
    }),
  onCancel:()=>{if(mobilePayment){window.location.assign('mobile_payment_result.php?pedido='+localOrder);}},
  onError:error=>{feedback.hidden=false;feedback.textContent=error.message||'El pago no pudo completarse. Intenta nuevamente.';}
});
paypalButtons.render('#paypal-button-container');
</script><?php endif;?></section></main><?=sideb_footer()?></body></html>
