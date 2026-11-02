document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-static-form]').forEach((form) => {
    const feedback = form.querySelector('[data-form-feedback]');
    form.addEventListener('submit', (event) => {
      event.preventDefault(); if (!form.checkValidity()) { form.reportValidity(); return; }
      const password = form.querySelector('[name="password"]'); const confirmation = form.querySelector('[name="password_confirmation"]');
      if (confirmation && password.value !== confirmation.value) { confirmation.setCustomValidity('Las contraseñas no coinciden.'); confirmation.reportValidity(); confirmation.setCustomValidity(''); return; }
      feedback.textContent = form.dataset.formType === 'register' ? 'Validación local completada. La conexión con el servidor queda pendiente.' : 'Formulario válido. La autenticación se conectará posteriormente.'; feedback.classList.add('is-visible');
    });
  });
});
