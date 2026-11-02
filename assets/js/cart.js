document.addEventListener('DOMContentLoaded', () => {
  const list = document.querySelector('[data-cart-list]');
  if (!list) return;
  const empty = document.querySelector('[data-cart-empty]');
  const subtotal = document.querySelector('[data-cart-subtotal]');
  const shipping = document.querySelector('[data-cart-shipping]');
  const total = document.querySelector('[data-cart-total]');
  const counters = document.querySelectorAll('.cart-count');
  const money = (value) => `$${value.toLocaleString('en-US')}`;
  function updateCart() {
    const items = [...list.querySelectorAll('[data-cart-item]')]; let subtotalValue = 0; let itemCount = 0;
    items.forEach((item) => { const quantity = Number(item.querySelector('[data-quantity]').textContent); const lineTotal = Number(item.dataset.price) * quantity; subtotalValue += lineTotal; itemCount += quantity; const linePrice = item.querySelector('.cart-price'); if (linePrice) linePrice.textContent = money(lineTotal); });
    const shippingValue = itemCount ? 99 : 0; subtotal.textContent = money(subtotalValue); shipping.textContent = money(shippingValue); total.textContent = money(subtotalValue + shippingValue); counters.forEach((counter) => { counter.textContent = itemCount; }); empty.hidden = items.length !== 0; list.hidden = items.length === 0;
  }
  list.addEventListener('click', (event) => { const control = event.target.closest('[data-action]'); if (!control) return; const item = control.closest('[data-cart-item]'); const quantityNode = item.querySelector('[data-quantity]'); let quantity = Number(quantityNode.textContent); if (control.dataset.action === 'increase') quantity += 1; if (control.dataset.action === 'decrease') quantity = Math.max(1, quantity - 1); if (control.dataset.action === 'remove') item.remove(); else quantityNode.textContent = quantity; updateCart(); });
  updateCart();
});
