<?php
declare(strict_types=1);

function sideb_auth_header(): void
{
    ?>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="auth-header">
        <div class="container auth-header-inner">
            <a class="brand" href="index.php" aria-label="SIDE B, inicio">SIDE B<span class="brand-dot">.</span></a>
            <a class="auth-link" href="index.php"><span aria-hidden="true">←</span> Volver al sitio</a>
        </div>
    </header>
    <?php
}

function sideb_auth_footer(): void
{
    ?>
    <footer class="auth-footer">
        <div class="container auth-footer-inner">
            <span>© <?= date('Y') ?> SIDE B</span>
            <a class="auth-link" href="informacion.php?pagina=privacidad">Privacidad</a>
        </div>
    </footer>
    <?php
}

function sideb_auth_password(string $id, string $name, string $label, string $autocomplete, ?string $hint = null): void
{
    ?>
    <div class="form-field">
        <label for="<?= e($id) ?>"><?= e($label) ?></label>
        <div class="auth-password">
            <input id="<?= e($id) ?>" name="<?= e($name) ?>" type="password" autocomplete="<?= e($autocomplete) ?>"
                <?= $hint !== null ? 'aria-describedby="' . e($id) . '-hint"' : '' ?> required>
            <button class="auth-password-toggle" type="button" data-password-toggle aria-controls="<?= e($id) ?>"
                aria-label="Mostrar contraseña" hidden>
                <svg class="public-action-icon auth-eye" aria-hidden="true" viewBox="0 0 24 24">
                    <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z"/>
                    <circle cx="12" cy="12" r="2.5"/>
                </svg>
                <svg class="public-action-icon auth-eye-off" aria-hidden="true" viewBox="0 0 24 24">
                    <path d="m3 3 18 18M9.5 6.3A11 11 0 0 1 12 6c6.5 0 10 6 10 6a15 15 0 0 1-3 3.3M6.4 7.8A15 15 0 0 0 2 12s3.5 6 10 6a11 11 0 0 0 4.2-.8"/>
                </svg>
            </button>
        </div>
        <?php if ($hint !== null): ?>
            <p class="auth-field-hint" id="<?= e($id) ?>-hint" aria-live="polite"><?= e($hint) ?></p>
        <?php endif; ?>
    </div>
    <?php
}
