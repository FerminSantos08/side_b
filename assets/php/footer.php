<?php
declare(strict_types=1);

function sideb_footer(): string
{
    $footer = <<<'HTML'
<footer class="site-footer">
  <div class="container footer-top">
    <a class="brand brand--footer" href="index.php">SIDE B<span class="brand-dot">.</span></a>
    <p>Lo que descubres<br>cuando escuchas más.</p>
    <nav aria-label="Navegación secundaria">
      <a href="#nosotros">Sobre SIDE B</a><a href="#contacto">Contacto</a><a href="#ayuda">Ayuda</a><a href="#privacidad">Privacidad</a>
    </nav>
    <div class="footer-social"><a href="#instagram" aria-label="Instagram">ig</a><a href="#x" aria-label="X">x</a><a href="#newsletter" aria-label="Newsletter">→</a></div>
  </div>
  <div class="container footer-bottom"><span>© 2026 SIDE B — Sitio demostrativo</span><span>Hecho para escuchar distinto.</span></div>
</footer>
HTML;
    $administrativePage = in_array(basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')), ['backstage.php', 'admin.php', 'editorial.php'], true);
    if (basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'tienda.php') {
        $footer .= '<style>.catalog-grid{grid-template-columns:repeat(auto-fill,minmax(210px,280px));justify-content:start}.catalog-grid .product-image::before,.catalog-grid .product-image::after{display:none!important}.catalog-grid .product-image img{display:block;height:100%;object-fit:contain;width:100%}</style>';
    }
    if (!$administrativePage) return $footer . '<script>(function(){var style=document.querySelector("link[href*=\"assets/css/components.css\"]");if(style)style.href="assets/css/components.css?v=2";})();</script>';
    return $footer . '<link rel="stylesheet" href="assets/css/admin-navigation.css?v=1"><script>(function(){var style=document.querySelector("link[href*=\"assets/css/backstage.css\"]");if(style)style.href="assets/css/backstage.css?v=6";})();</script>';
}
