<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

function can_manage_post(array $post): bool
{
    return is_executive_producer() || (int) ($post['autor_id'] ?? 0) === (int) ($_SESSION['user_id'] ?? 0);
}

function editorial_slug(string $title): string
{
    $value = trim($title);
    $value = function_exists('iconv') ? (string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
    $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
    return trim($value, '-') ?: 'publicacion';
}

function unique_editorial_slug(PDO $database, string $title, ?int $exceptId = null): string
{
    $base = substr(editorial_slug($title), 0, 210);
    $slug = $base; $number = 2;
    do {
        $sql = 'SELECT id_blog FROM blog WHERE slug = :slug' . ($exceptId ? ' AND id_blog <> :id' : '') . ' LIMIT 1';
        $statement = $database->prepare($sql);
        $params = ['slug' => $slug]; if ($exceptId) $params['id'] = $exceptId;
        $statement->execute($params);
        if (!$statement->fetch()) return $slug;
        $suffix = '-' . $number++;
        $slug = substr($base, 0, 220 - strlen($suffix)) . $suffix;
    } while (true);
}

function clean_editorial_html(string $html): string
{
    $html = trim($html);
    if ($html === '') return '';
    if (!class_exists('DOMDocument')) return nl2br(e(strip_tags($html)));
    $allowed = ['p'=>[], 'br'=>[], 'strong'=>[], 'b'=>[], 'em'=>[], 'i'=>[], 'h2'=>[], 'h3'=>[], 'blockquote'=>[], 'ul'=>[], 'ol'=>[], 'li'=>[], 'a'=>['href']];
    $document = new DOMDocument('1.0', 'UTF-8');
    libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8" ?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $walk = function (DOMNode $node) use (&$walk, $allowed): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!isset($allowed[$tag])) {
                    // Limpiar también el contenido antes de retirar su contenedor.
                    $walk($child);
                    while ($child->firstChild) $child->parentNode->insertBefore($child->firstChild, $child);
                    $child->parentNode->removeChild($child);
                    continue;
                }
                foreach (iterator_to_array($child->attributes) as $attribute) {
                    if (!in_array(strtolower($attribute->name), $allowed[$tag], true)) $child->removeAttributeNode($attribute);
                }
                if ($tag === 'a' && !preg_match('#^(https?://|mailto:)#i', (string) $child->getAttribute('href'))) $child->removeAttribute('href');
                $walk($child);
            }
        }
    };
    $walk($document->documentElement);
    $result = ''; foreach ($document->documentElement->childNodes as $node) $result .= $document->saveHTML($node);
    return trim($result);
}

function upload_publication_image(array $file, ?string $previous = null): ?string
{
    $path = upload_backstage_image($file, 'publicaciones');
    return $path === null ? $previous : basename($path);
}
