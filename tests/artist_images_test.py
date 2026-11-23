import importlib.util
import io
from pathlib import Path
import tempfile
import unittest
from unittest.mock import Mock, patch
from PIL import Image

script = Path(__file__).resolve().parents[1] / 'scripts/download-artist-images.py'
if not script.exists():
    script = Path(__file__).with_name('download-artist-images.py')
spec = importlib.util.spec_from_file_location('artists', script)
a = importlib.util.module_from_spec(spec)
spec.loader.exec_module(a)

class ArtistTests(unittest.TestCase):
    def test_ambiguous_artist_is_not_selected(self):
        first = {'name': 'Joji', 'id': '12345678-1234-1234-1234-123456789abc'}
        self.assertEqual(a.unique_artist('Joji', [first]), first)
        self.assertIsNone(a.unique_artist('Joji', [first, {**first, 'id': '22345678-1234-1234-1234-123456789abc'}]))
        self.assertIsNone(a.unique_artist('Other', [first]))

    def test_commons_requires_author_and_license(self):
        client = Mock()
        info = {'mime': 'image/jpeg', 'thumburl': 'https://thumb.wikimedia.org/test.jpg',
                'descriptionurl': 'https://commons.wikimedia.org/wiki/File:test.jpg',
                'extmetadata': {'Artist': {'value': '<a>Author</a>'}, 'LicenseShortName': {'value': 'CC BY 4.0'}}}
        client.get.return_value = __import__('json').dumps({'query': {'pages': {'1': {'imageinfo': [info]}}}}).encode()
        self.assertEqual(a.commons_image(client, 'test.jpg')['author'], 'Author')
        del info['extmetadata']['Artist']
        client.get.return_value = __import__('json').dumps({'query': {'pages': {'1': {'imageinfo': [info]}}}}).encode()
        self.assertIsNone(a.commons_image(client, 'test.jpg'))

    def test_failed_database_update_cleans_files(self):
        with tempfile.TemporaryDirectory() as folder:
            data = io.BytesIO()
            Image.new('RGB', (30, 30)).save(data, 'JPEG')
            client = Mock()
            client.get.return_value = data.getvalue()
            with patch.object(a, 'ROOT', Path(folder)), patch.object(a, 'commons_image', return_value={'image_url': 'https://upload.wikimedia.org/test.jpg'}), patch.object(a, 'database', return_value={'updated': 0}):
                with self.assertRaises(RuntimeError):
                    a.install_image({'id_artista': 2, 'nombre': 'Test', 'imagen': None}, {'filename': 'test.jpg'}, client)
            self.assertEqual(list((Path(folder) / 'uploads/artistas').iterdir()), [])

    def test_preview_escapes_metadata(self):
        with tempfile.TemporaryDirectory() as folder:
            output = Path(folder) / 'preview.html'
            a.write_preview([{'nombre': '<script>bad</script>', 'status': 'review'}], output)
            self.assertNotIn('<script>', output.read_text())

if __name__ == '__main__':
    unittest.main()
