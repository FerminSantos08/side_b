"""Pruebas del bloqueo de restauración usando clientes simulados, sin tocar MySQL."""
import os
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parent.parent


class RestoreDatabaseTest(unittest.TestCase):
    def run_restore(self, tables):
        with tempfile.TemporaryDirectory() as directory:
            work = Path(directory)
            mysql = work / 'mysql'
            mysql.write_text('''#!/usr/bin/python3
import os, sys
from pathlib import Path
if '-e' in sys.argv:
    print(os.environ['TEST_TABLES'])
else:
    Path(os.environ['TEST_IMPORT']).write_text(str(len(sys.stdin.buffer.read())))
''')
            php = work / 'php'
            php.write_text('#!/bin/sh\nprintf migrated > "$TEST_MIGRATE"\n')
            mysql.chmod(0o700)
            php.chmod(0o700)
            env = dict(os.environ, PATH=str(work), DB_NAME='side_b', DB_HOST='127.0.0.1',
                       DB_USER='test', DB_PASSWORD='test-only', TEST_TABLES=str(tables),
                       TEST_IMPORT=str(work / 'imported'), TEST_MIGRATE=str(work / 'migrated'))
            result = subprocess.run([sys.executable, str(ROOT / 'scripts/restore-db.py')],
                                    env=env, capture_output=True, text=True)
            return result, (work / 'imported').exists(), (work / 'migrated').exists()

    def test_existing_database_is_never_imported(self):
        result, imported, migrated = self.run_restore(18)
        self.assertNotEqual(result.returncode, 0)
        self.assertIn('No se sobrescribió nada', result.stderr)
        self.assertFalse(imported)
        self.assertFalse(migrated)

    def test_empty_database_is_imported_and_migrated(self):
        result, imported, migrated = self.run_restore(0)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertTrue(imported)
        self.assertTrue(migrated)


if __name__ == '__main__':
    unittest.main()
