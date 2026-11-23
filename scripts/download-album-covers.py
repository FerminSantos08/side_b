#!/usr/bin/env python3
"""Busca portadas de Side B. Sin --apply solamente consulta y muestra resultados."""
import argparse
import io
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import time
import unicodedata
from urllib.error import HTTPError, URLError
from urllib.parse import urlencode
from urllib.request import Request, urlopen
import warnings

USER_AGENT = 'SideB-CoverImporter/1.0 (https://github.com/FerminSantos08/side_b)'
MAX_BYTES = 5 * 1024 * 1024
ROOT = Path(__file__).resolve().parent.parent
PHP_BRIDGE = r'''
require $argv[1] . '/assets/php/bootstrap.php';
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
$db = db();
if ($input['action'] === 'list') {
    echo json_encode($db->query('SELECT a.id_album,a.titulo,a.portada,r.nombre artista FROM album a JOIN artista r ON r.id_artista=a.artista_id ORDER BY a.id_album')->fetchAll(), JSON_THROW_ON_ERROR);
} elseif ($input['action'] === 'update') {
    $s = $db->prepare('UPDATE album SET portada=:new WHERE id_album=:id AND portada <=> :old');
    $s->execute(['new'=>$input['new'], 'id'=>$input['id'], 'old'=>$input['old']]);
    echo json_encode(['updated'=>$s->rowCount()]);
} else { throw new RuntimeException('Acción inválida.'); }
'''


def database(root, payload):
    result = subprocess.run(['php', '-r', PHP_BRIDGE, str(root)],
                            input=json.dumps(payload), capture_output=True, text=True)
    if result.returncode:
        # Do not expose configuration or database diagnostics in the public report.
        raise RuntimeError('No se pudo consultar/actualizar MySQL. Comprueba PHP, PDO MySQL y .env.')
    return json.loads(result.stdout)


def normalized(value):
    value = unicodedata.normalize('NFKD', value).casefold()
    return ''.join(c for c in value if c.isalnum() and not unicodedata.combining(c))


def quoted(value):
    return '"' + re.sub(r'([+\-!(){}\[\]^"~*?:\\/&|])', r'\\\1', value) + '"'


def choose_match(album, groups):
    """Require exact normalized title and an individually credited artist; reject ambiguity."""
    matches = []
    for group in groups:
        artists = [credit.get('artist', {}).get('name', '')
                   for credit in group.get('artist-credit', []) if isinstance(credit, dict)]
        if (normalized(group.get('title', '')) == normalized(album['titulo'])
                and normalized(album['artista']) in [normalized(a) for a in artists]
                and re.fullmatch(r'[0-9a-fA-F-]{36}', group.get('id', ''))):
            matches.append(group)
    unique = {group['id']: group for group in matches}
    return next(iter(unique.values())) if len(unique) == 1 else None


class Client:
    def __init__(self):
        self.last_request = 0

    def get(self, url, limit=MAX_BYTES):
        for attempt in range(3):
            # One sequential request per second, including retries.
            time.sleep(max(0, 1.1 - (time.monotonic() - self.last_request)))
            self.last_request = time.monotonic()
            try:
                request = Request(url, headers={'User-Agent': USER_AGENT})
                with urlopen(request, timeout=30) as response:
                    body = response.read(limit + 1)
                    if len(body) > limit:
                        raise RuntimeError('La respuesta supera el tamaño permitido.')
                    return body
            except HTTPError as error:
                if error.code == 404:
                    return None
                if error.code not in (429, 500, 502, 503, 504) or attempt == 2:
                    raise RuntimeError(f'HTTP {error.code} al consultar la fuente.') from error
                retry = error.headers.get('Retry-After', '')
                time.sleep(min(60, int(retry)) if retry.isdigit() else 2 ** (attempt + 1))
            except (URLError, TimeoutError) as error:
                if attempt == 2:
                    raise RuntimeError('No fue posible conectar con la fuente de portadas.') from error
                time.sleep(2 ** (attempt + 1))

    def search(self, album):
        query = f'releasegroup:{quoted(album["titulo"])} AND artist:{quoted(album["artista"])}'
        url = 'https://musicbrainz.org/ws/2/release-group/?' + urlencode(
            {'query': query, 'fmt': 'json', 'limit': 100})
        data = self.get(url)
        return json.loads(data)['release-groups'] if data else []


def existing_cover(root, relative):
    if not relative:
        return False
    path = (root / relative).resolve()
    uploads = (root / 'uploads').resolve()
    if not path.is_relative_to(uploads) or not path.is_file():
        return False
    from PIL import Image
    try:
        with Image.open(path) as image:
            image.verify()
        return True
    except (OSError, ValueError):
        return False


def save_webp(data, destination):
    from PIL import Image, ImageOps
    with warnings.catch_warnings():
        warnings.simplefilter('error', Image.DecompressionBombWarning)
        with Image.open(io.BytesIO(data)) as image:
            if image.format not in ('JPEG', 'PNG', 'WEBP'):
                raise ValueError('Formato de portada no permitido.')
            if image.width * image.height > 16000000 or max(image.size) > 12000:
                raise ValueError('La portada supera las dimensiones permitidas.')
            image.load()
            image = ImageOps.exif_transpose(image)
            image.thumbnail((1920, 1920))
            image = image.convert('RGBA' if 'A' in image.getbands() or 'transparency' in image.info else 'RGB')
            image.save(destination, 'WEBP', quality=82)


def process(album, root, client, apply=False):
    result = {**album, 'status': 'pending'}
    if existing_cover(root, album['portada']):
        return {**result, 'status': 'existing'}
    groups = client.search(album)
    match = choose_match(album, groups)
    if not match:
        candidates = [{'id': g.get('id'), 'title': g.get('title'),
                       'artists': g.get('artist-credit')} for g in groups[:5]]
        return {**result, 'status': 'review', 'candidates': candidates}
    cover_url = f'https://coverartarchive.org/release-group/{match["id"]}/front-1200'
    result.update(musicbrainz_id=match['id'], source=cover_url)
    if not apply:
        return {**result, 'status': 'matched_preview'}
    data = client.get(cover_url)
    if data is None:
        return {**result, 'status': 'no_cover'}
    directory = root / 'uploads' / 'albumes'
    directory.mkdir(parents=True, exist_ok=True)
    # Unique destination avoids replacing an existing file before a DB update succeeds.
    fd, filename = tempfile.mkstemp(prefix=f'album-{int(album["id_album"])}-', suffix='.webp', dir=directory)
    os.close(fd)
    destination = Path(filename)
    try:
        save_webp(data, destination)
        destination.chmod(0o644)
        relative = destination.relative_to(root).as_posix()
        updated = database(root, {'action': 'update', 'id': album['id_album'],
                                  'old': album['portada'], 'new': relative})
        if updated['updated'] != 1:
            raise RuntimeError('La portada cambió durante la descarga; no se sobrescribió.')
    except BaseException:
        destination.unlink(missing_ok=True)
        raise
    return {**result, 'status': 'downloaded', 'saved': relative}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true', help='Descargar y actualizar la BD; sin esta opción solo vista previa.')
    parser.add_argument('--artist', help='Filtrar por nombre de artista.')
    parser.add_argument('--limit', type=int, help='Máximo de álbumes pendientes que se buscarán.')
    parser.add_argument('--report', type=Path, help='Guardar informe JSON con fuentes y resultados.')
    args = parser.parse_args()
    if args.limit is not None and args.limit < 1:
        parser.error('--limit debe ser mayor que cero')
    try:
        from PIL import Image, features
        if not features.check('webp'):
            raise RuntimeError('Pillow necesita soporte WebP.')
        albums = database(ROOT, {'action': 'list'})
    except (ImportError, RuntimeError) as error:
        parser.exit(1, f'{error}\nInstala Pillow si falta: sudo dnf install python3-pillow\n')
    if args.artist:
        albums = [a for a in albums if normalized(a['artista']) == normalized(args.artist)]
    client = Client()
    results = []
    attempted = 0
    print('Modo: ' + ('DESCARGAR Y ACTUALIZAR' if args.apply else 'VISTA PREVIA (no guarda imágenes ni modifica la BD)'), flush=True)
    for album in albums:
        if args.limit and attempted >= args.limit:
            break
        try:
            result = process(album, ROOT, client, args.apply)
        except (RuntimeError, OSError, ValueError) as error:
            result = {**album, 'status': 'error', 'error': str(error)}
        if result['status'] != 'existing':
            attempted += 1
        results.append(result)
        print(f'{album["id_album"]}: {album["artista"]} — {album["titulo"]}: {result["status"]}', flush=True)
        if args.report:
            args.report.write_text(json.dumps(results, ensure_ascii=False, indent=2) + '\n')
    print(f'Listo: {len(results)} álbumes revisados. Las coincidencias dudosas quedan como review.')
    return 1 if any(r['status'] == 'error' for r in results) else 0


if __name__ == '__main__':
    sys.exit(main())
