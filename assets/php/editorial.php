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
                if (!isset($allowed[$tag])) { while ($child->firstChild) $child->parentNode->insertBefore($child->firstChild, $child); $child->parentNode->removeChild($child); continue; }
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
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    // Algunos entornos PHP envían un campo vacío sin UPLOAD_ERR_NO_FILE.
    if ($uploadError === UPLOAD_ERR_NO_FILE || empty($file['tmp_name']) || (int) ($file['size'] ?? 0) === 0) return $previous;
    if ($uploadError !== UPLOAD_ERR_OK || (int) ($file['size'] ?? 0) > 5 * 1024 * 1024) throw new RuntimeException('La imagen debe pesar como máximo 5 MB.');
    if (!class_exists('finfo')) throw new RuntimeException('El servidor no puede validar imágenes en este momento.');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
    if (!isset($extensions[$mime])) throw new RuntimeException('Usa una imagen JPG, PNG o WebP válida.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    $directory = dirname(__DIR__, 2) . '/uploads/publicaciones';
    if (!is_dir($directory) && !mkdir($directory, 0755, true)) throw new RuntimeException('No fue posible preparar el directorio de imágenes.');
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) throw new RuntimeException('No fue posible guardar la imagen.');
    return $name;
}
