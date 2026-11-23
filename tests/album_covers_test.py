import importlib.util
import io
from pathlib import Path
import tempfile
import unittest
from unittest.mock import patch
from PIL import Image

script = Path(__file__).resolve().parents[1] / 'scripts/download-album-covers.py'
if not script.exists():
    script = Path(__file__).with_name('download-album-covers.py')
spec = importlib.util.spec_from_file_location('covers', script)
covers = importlib.util.module_from_spec(spec)
spec.loader.exec_module(covers)

ALBUM = {'id_album': 4, 'titulo': 'AM', 'artista': 'Arctic Monkeys', 'portada': None}
GROUP = {'id': '12345678-1234-1234-1234-123456789abc', 'title': 'AM',
         'artist-credit': [{'artist': {'name': 'Arctic Monkeys'}}]}

class CoverTests(unittest.TestCase):
    def test_matching_rejects_wrong_artist_and_ambiguity(self):
        self.assertEqual(covers.choose_match(ALBUM, [GROUP]), GROUP)
        wrong = {**GROUP, 'artist-credit': [{'artist': {'name': 'Other'}}]}
        self.assertIsNone(covers.choose_match(ALBUM, [wrong]))
        self.assertIsNone(covers.choose_match(ALBUM, [GROUP, {**GROUP, 'id': '22345678-1234-1234-1234-123456789abc'}]))
        self.assertIsNone(covers.choose_match({**ALBUM, 'titulo': 'AM deluxe'}, [GROUP]))

    def test_convert_preserves_transparency_and_proportions(self):
        with tempfile.TemporaryDirectory() as folder:
            data = io.BytesIO()
            Image.new('RGBA', (2400, 1200), (0, 0, 0, 0)).save(data, 'PNG')
            target = Path(folder) / 'cover.webp'
            covers.save_webp(data.getvalue(), target)
            with Image.open(target) as image:
                self.assertEqual(image.format, 'WEBP')
                self.assertEqual(image.size, (1920, 960))
                self.assertEqual(image.getpixel((0, 0))[3], 0)
            with self.assertRaises(OSError):
                covers.save_webp(b'not an image', target)

    def test_preview_does_not_write_or_update(self):
        with tempfile.TemporaryDirectory() as folder:
            client = unittest.mock.Mock()
            client.search.return_value = [GROUP]
            with patch.object(covers, 'database') as database:
                self.assertEqual(covers.process(ALBUM, Path(folder), client)['status'], 'matched_preview')
                database.assert_not_called()
                client.get.assert_not_called()
                self.assertEqual(list(Path(folder).iterdir()), [])

    def test_db_failure_removes_download(self):
        with tempfile.TemporaryDirectory() as folder:
            client = unittest.mock.Mock()
            client.search.return_value = [GROUP]
            data = io.BytesIO()
            Image.new('RGB', (30, 30)).save(data, 'JPEG')
            client.get.return_value = data.getvalue()
            with patch.object(covers, 'database', return_value={'updated': 0}):
                with self.assertRaises(RuntimeError):
                    covers.process(ALBUM, Path(folder), client, True)
            self.assertEqual(list((Path(folder) / 'uploads/albumes').iterdir()), [])

    def test_existing_cover_skips_search(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            (root / 'uploads').mkdir()
            Image.new('RGB', (10, 10)).save(root / 'uploads/cover.webp', 'WEBP')
            client = unittest.mock.Mock()
            result = covers.process({**ALBUM, 'portada': 'uploads/cover.webp'}, root, client, True)
            self.assertEqual(result['status'], 'existing')
            client.search.assert_not_called()
            self.assertFalse(covers.existing_cover(root, '../outside.jpg'))

if __name__ == '__main__':
    unittest.main()
