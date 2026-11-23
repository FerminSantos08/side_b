(() => {
    const form = document.querySelector('.auth-form');
    if (!form) return;

    for (const toggle of form.querySelectorAll('[data-password-toggle]')) {
        const input = document.getElementById(toggle.getAttribute('aria-controls'));
        toggle.hidden = false;
        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.toggleAttribute('data-password-visible', visible);
            toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
        });
    }

    const password = form.elements.password;
    const confirmation = form.elements.password_confirmation;

    if (confirmation) {
        const passwordHint = document.getElementById('register-password-hint');
        const confirmationHint = document.getElementById('register-confirm-hint');
        const bytes = new TextEncoder();

        function updatePasswordFeedback() {
            const longEnough = bytes.encode(password.value).length >= 8;
            const matches = confirmation.value !== '' && confirmation.value === password.value;
            passwordHint.dataset.state = password.value && longEnough ? 'success' : '';
            confirmationHint.dataset.state = confirmation.value ? (matches ? 'success' : 'error') : '';

            const lengthMessage = longEnough ? 'Longitud mínima cumplida.' : 'Usa al menos 8 caracteres.';
            const matchMessage = confirmation.value
                ? (matches ? 'Las contraseñas coinciden.' : 'Las contraseñas no coinciden.')
                : 'Repite tu contraseña.';

            if (passwordHint.textContent !== lengthMessage) passwordHint.textContent = lengthMessage;
            if (confirmationHint.textContent !== matchMessage) confirmationHint.textContent = matchMessage;

            password.setCustomValidity(password.value && !longEnough ? lengthMessage : '');
            confirmation.setCustomValidity(confirmation.value && !matches ? matchMessage : '');
        }

        form.addEventListener('input', updatePasswordFeedback);
        form.addEventListener('change', updatePasswordFeedback);
        window.addEventListener('pageshow', updatePasswordFeedback);
        form.addEventListener('submit', updatePasswordFeedback);
        updatePasswordFeedback();
    }

    function showValidity(input) {
        if (!input.willValidate) return;
        input.setAttribute('aria-invalid', String(!input.validity.valid));
    }

    form.addEventListener('input', (event) => {
        for (const input of [event.target, password, confirmation]) {
            if (input?.hasAttribute('aria-invalid')) showValidity(input);
        }
    });

    form.addEventListener('submit', (event) => {
        if (form.checkValidity()) return;
        event.preventDefault();
        for (const input of form.elements) showValidity(input);
        form.reportValidity();
    });
})();
