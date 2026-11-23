#!/usr/bin/env python3
"""Prepara fotos de artistas para revisión; --apply --from-preview aplica la selección."""
import argparse
import html
import importlib.util
import json
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
from urllib.parse import urlencode, urlparse

ROOT = Path(__file__).resolve().parent.parent
spec = importlib.util.spec_from_file_location('album_covers', Path(__file__).with_name('download-album-covers.py'))
common = importlib.util.module_from_spec(spec)
spec.loader.exec_module(common)
BRIDGE = r'''
require $argv[1] . '/assets/php/bootstrap.php';
$i=json_decode(stream_get_contents(STDIN),true,512,JSON_THROW_ON_ERROR); $db=db();
if ($i['action']==='list') {
 echo json_encode($db->query('SELECT id_artista,nombre,imagen FROM artista WHERE activo=TRUE ORDER BY id_artista')->fetchAll(),JSON_THROW_ON_ERROR);
} elseif ($i['action']==='update') {
 $s=$db->prepare('UPDATE artista SET imagen=:new WHERE id_artista=:id AND imagen <=> :old');
 $s->execute(['new'=>$i['new'],'id'=>$i['id'],'old'=>$i['old']]); echo json_encode(['updated'=>$s->rowCount()]);
} else { throw new RuntimeException('Acción inválida.'); }
'''


def database(payload):
    run = subprocess.run(['php', '-r', BRIDGE, str(ROOT)], input=json.dumps(payload), capture_output=True, text=True)
    if run.returncode:
        raise RuntimeError('No se pudo acceder a MySQL. Revisa PHP, PDO y .env.')
    return json.loads(run.stdout)


def api(client, endpoint, params):
    data = client.get(endpoint + '?' + urlencode(params))
    result = json.loads(data) if data else {}
    if 'error' in result:
        raise RuntimeError('La API rechazó la consulta: ' + result['error'].get('code', 'error'))
    return result


def unique_artist(name, candidates):
    matches = {c['id']: c for c in candidates if common.normalized(c.get('name', '')) == common.normalized(name)
               and re.fullmatch(r'[a-fA-F0-9-]{36}', c.get('id', ''))}
    return next(iter(matches.values())) if len(matches) == 1 else None


def commons_image(client, filename):
    data = api(client, 'https://commons.wikimedia.org/w/api.php', {
        'action': 'query', 'format': 'json', 'prop': 'imageinfo', 'titles': 'File:' + filename,
        'iiprop': 'url|extmetadata|mime', 'iiurlwidth': 1200})
    for page in data.get('query', {}).get('pages', {}).values():
        for image in page.get('imageinfo', []):
            if image.get('mime') not in ('image/jpeg', 'image/png', 'image/webp'):
                continue
            metadata = image.get('extmetadata', {})
            field = lambda key: metadata.get(key, {}).get('value', '')
            # Keep HTML from metadata out of generated pages.
            clean = lambda text: html.unescape(re.sub('<[^>]+>', '', text)).strip()
            author, license_name = clean(field('Artist')), clean(field('LicenseShortName'))
            if not author or not license_name:
                continue
            url = image.get('thumburl') or image.get('url', '')
            if urlparse(url).scheme != 'https' or urlparse(url).hostname not in ('upload.wikimedia.org', 'thumb.wikimedia.org'):
                continue
            return {'filename': filename, 'image_url': url, 'source': image.get('descriptionurl', ''),
                    'author': author, 'license': license_name, 'license_url': field('LicenseUrl'),
                    'credit': clean(field('Credit'))}
    return None


def find_candidate(artist, client):
    results = api(client, 'https://musicbrainz.org/ws/2/artist/', {
        'query': 'artist:' + common.quoted(artist['nombre']), 'fmt': 'json', 'limit': 100}).get('artists', [])
    match = unique_artist(artist['nombre'], results)
    if not match:
        return {**artist, 'status': 'review', 'candidates': [
            {'name': c.get('name'), 'id': c.get('id'), 'detail': c.get('disambiguation')} for c in results[:5]]}
    details = api(client, f'https://musicbrainz.org/ws/2/artist/{match["id"]}', {'inc': 'url-rels', 'fmt': 'json'})
    qids = set()
    for relation in details.get('relations', []):
        url = relation.get('url', {}).get('resource', '')
        found = re.fullmatch(r'https?://www.wikidata.org/wiki/(Q[0-9]+)', url)
        if relation.get('type') == 'wikidata' and found:
            qids.add(found[1])
    if len(qids) != 1:
        return {**artist, 'status': 'no_wikidata'}
    qid = qids.pop()
    entity = api(client, 'https://www.wikidata.org/w/api.php', {
        'action': 'wbgetentities', 'ids': qid, 'props': 'claims', 'format': 'json'}).get('entities', {}).get(qid, {})
    claims = [c for c in entity.get('claims', {}).get('P18', []) if c.get('rank') != 'deprecated']
    preferred = [c for c in claims if c.get('rank') == 'preferred']
    claims = preferred or claims
    filenames = {c.get('mainsnak', {}).get('datavalue', {}).get('value') for c in claims}
    filenames.discard(None)
    if len(filenames) != 1:
        return {**artist, 'status': 'no_image' if not filenames else 'review', 'wikidata': qid}
    image = commons_image(client, filenames.pop())
    if not image:
        return {**artist, 'status': 'no_image', 'wikidata': qid}
    return {**artist, **image, 'musicbrainz_id': match['id'], 'wikidata': qid, 'status': 'preview'}


def install_image(artist, candidate, client):
    # Refresh Commons metadata; download URLs from a preview file are never trusted.
    image = commons_image(client, candidate['filename'])
    if not image:
        raise RuntimeError('La imagen ya no está disponible con sus créditos.')
    data = client.get(image['image_url'])
    if not data:
        raise RuntimeError('No se pudo descargar la foto.')
    directory = ROOT / 'uploads/artistas'
    directory.mkdir(parents=True, exist_ok=True)
    fd, name = tempfile.mkstemp(prefix=f'artista-{int(artist["id_artista"])}-', suffix='.webp', dir=directory)
    os.close(fd)
    destination = Path(name)
    sidecar = destination.with_suffix('.credits.json')
    try:
        common.save_webp(data, destination)
        destination.chmod(0o644)
        relative = destination.relative_to(ROOT).as_posix()
        credits = {**image, 'artist': artist['nombre'], 'saved': relative,
                   'changes': 'Conversión a WebP, calidad 82; reducción proporcional a máximo 1920 px.'}
        sidecar.write_text(json.dumps(credits, ensure_ascii=False, indent=2) + '\n')
        sidecar.chmod(0o644)
        update = database({'action': 'update', 'id': artist['id_artista'], 'old': artist['imagen'], 'new': relative})
        if update['updated'] != 1:
            raise RuntimeError('La foto cambió durante la descarga; no se sobrescribió.')
    except BaseException:
        destination.unlink(missing_ok=True)
        sidecar.unlink(missing_ok=True)
        raise
    return {**artist, **image, 'status': 'downloaded', 'saved': relative, 'credits': sidecar.relative_to(ROOT).as_posix()}


def write_preview(results, destination):
    e = lambda value: html.escape(str(value or ''), quote=True)
    cards = []
    for row in results:
        content = f'<h2>{e(row["nombre"])}</h2><p>{e(row["status"])}</p>'
        if row.get('image_url'):
            content += (f'<img src="{e(row["image_url"])}" alt="{e(row["nombre"])}">'
                        f'<p>{e(row["author"])} · {e(row["license"])}</p>'
                        f'<p><a href="{e(row["source"])}">Fuente y condiciones de uso</a></p>')
        cards.append('<article>' + content + '</article>')
    destination.write_text('<!doctype html><html lang="es"><meta charset="utf-8">'
                          '<meta name="viewport" content="width=device-width,initial-scale=1">'
                          '<title>Fotos de artistas — Side B</title><style>'
                          'body{font:16px system-ui;margin:32px;background:#eee9dd;color:#292925}'
                          'main{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px}'
                          'article{background:white;padding:20px;border-radius:12px}img{width:100%;height:320px;object-fit:contain}'
                          '</style><h1>Revisión de fotos de artistas</h1>'
                          '<p>Las imágenes se cargan desde Wikimedia. Revisa las fotos y sus créditos antes de aplicar.</p>'
                          '<main>' + ''.join(cards) + '</main></html>')


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--apply', action='store_true')
    parser.add_argument('--from-preview', type=Path, help='JSON de una vista previa revisada.')
    parser.add_argument('--artist', help='Procesar solo este artista.')
    parser.add_argument('--limit', type=int)
    parser.add_argument('--report', type=Path, default=Path('/tmp/side-b-artistas.json'))
    parser.add_argument('--preview', type=Path, default=Path('/tmp/side-b-artistas.html'))
    args = parser.parse_args()
    if args.apply and not args.from_preview:
        parser.error('--apply necesita --from-preview con el JSON que revisaste')
    if args.limit is not None and args.limit < 1:
        parser.error('--limit debe ser positivo')
    try:
        from PIL import features
        if not features.check('webp'):
            raise RuntimeError('Pillow no tiene soporte WebP.')
        artists = database({'action': 'list'})
        manifest = json.loads(args.from_preview.read_text()) if args.from_preview else []
        approved = {int(c['id_artista']): c for c in manifest if c.get('status') == 'preview'}
    except (ImportError, ValueError, OSError, RuntimeError) as error:
        parser.exit(1, str(error) + '\n')
    results, attempted = [], 0
    client = common.Client()
    print('Modo: ' + ('APLICAR FOTOS DE LA VISTA PREVIA' if args.apply else 'VISTA PREVIA; no cambia la BD'), flush=True)
    for artist in artists:
        if args.artist and common.normalized(artist['nombre']) != common.normalized(args.artist):
            continue
        if args.limit and attempted >= args.limit:
            break
        try:
            if common.existing_cover(ROOT, artist['imagen']):
                result = {**artist, 'status': 'existing'}
            elif args.apply:
                candidate = approved.get(artist['id_artista'])
                if candidate is None:
                    result = {**artist, 'status': 'not_selected'}
                elif candidate.get('imagen') != artist['imagen'] or candidate.get('nombre') != artist['nombre']:
                    result = {**artist, 'status': 'review_changed'}
                else:
                    result = install_image(artist, candidate, client)
            else:
                result = find_candidate(artist, client)
        except (RuntimeError, OSError, ValueError, KeyError) as error:
            result = {**artist, 'status': 'error', 'error': str(error)}
        results.append(result)
        if result['status'] != 'existing':
            attempted += 1
        print(f'{artist["nombre"]}: {result["status"]}', flush=True)
        # Applying with the default report must not overwrite the reviewed manifest.
        report = args.report
        if args.apply and report.resolve() == args.from_preview.resolve():
            report = report.with_name(report.stem + '-resultado.json')
        report.write_text(json.dumps(results, ensure_ascii=False, indent=2) + '\n')
    if not args.apply:
        write_preview(results, args.preview)
        print(f'Revisa las fotos: {args.preview}\nSelección JSON: {args.report}')
    return 1 if any(r['status'] == 'error' for r in results) else 0


if __name__ == '__main__':
    sys.exit(main())
