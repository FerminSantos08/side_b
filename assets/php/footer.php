<?php
declare(strict_types=1);

function sideb_image_credits(): string
{
    $items = '';
    $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    foreach (glob(dirname(__DIR__, 2) . '/uploads/artistas/*.credits.json') ?: [] as $file) {
        $credit = json_decode((string) file_get_contents($file), true);
        if (!is_array($credit) || empty($credit['source']) || empty($credit['author']) || empty($credit['license'])) continue;
        $source = (string) $credit['source'];
        if (parse_url($source, PHP_URL_SCHEME) !== 'https' || parse_url($source, PHP_URL_HOST) !== 'commons.wikimedia.org') continue;
        $items .= '<li>' . $escape($credit['artist'] ?? '') . ': ' . $escape($credit['author'])
            . ' — ' . $escape($credit['license']) . '. <a href="' . $escape($source) . '">Fuente y licencia</a>. '
            . $escape($credit['changes'] ?? '') . '</li>';
    }
    return $items === '' ? '' : '<details class="container image-credits"><summary>Créditos de fotografías</summary><ul>' . $items . '</ul></details>';
}

function sideb_footer(): string
{
    $footer = <<<'HTML'
<footer class="site-footer">
  <div class="container footer-top">
    <a class="brand brand--footer" href="index.php">SIDE B<span class="brand-dot">.</span></a>
    <p>Lo que descubres<br>cuando escuchas más.</p>
    <nav aria-label="Navegación secundaria">
      <a href="informacion.php?pagina=nosotros">Sobre SIDE B</a><a href="informacion.php?pagina=contacto">Contacto</a><a href="informacion.php?pagina=ayuda">Ayuda</a><a href="informacion.php?pagina=privacidad">Privacidad</a>
    </nav>
    <div class="footer-social"><a href="#instagram" aria-label="Instagram">ig</a><a href="#x" aria-label="X">x</a><a href="#newsletter" aria-label="Newsletter">→</a></div>
  </div>
  <div class="container footer-bottom"><span>© 2026 SIDE B — Sitio demostrativo</span><span>Hecho para escuchar distinto.</span></div>
</footer>
HTML;
    return str_replace('</footer>', sideb_image_credits() . '</footer>', $footer);
}
