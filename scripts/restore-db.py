#!/usr/bin/env python3
"""Restaura el respaldo de SIDE B solo cuando la base está vacía."""
import os
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parent.parent
if shutil.which('php') is None or shutil.which('mysql') is None:
    raise SystemExit('Se necesitan los comandos php y mysql. Consulta README.md.')

config = dict(os.environ)
env_file = root / '.env'
if env_file.is_file():
    for line in env_file.read_text().splitlines():
        line = line.strip()
        if not line or line.startswith('#') or '=' not in line:
            continue
        key, value = line.split('=', 1)
        key, value = key.strip(), value.strip()
        if len(value) >= 2 and value[0] == value[-1] and value[0] in "\"'":
            value = value[1:-1]
        if re.fullmatch(r'[A-Z_][A-Z0-9_]*', key):
            config.setdefault(key, value)

name = config.get('DB_NAME', 'side_b')
if name != 'side_b':
    raise SystemExit('El respaldo apunta a side_b. DB_NAME debe ser side_b para restaurarlo.')
if not config.get('DB_PASSWORD'):
    raise SystemExit('Falta DB_PASSWORD en .env o en el entorno.')

def option(value):
    return '"' + str(value).replace('\\', '\\\\').replace('"', '\\"').replace('\n', '\\n').replace('\r', '\\r') + '"'

# mysql lee la contraseña desde un archivo temporal privado, nunca desde sus argumentos.
with tempfile.TemporaryDirectory(prefix='sideb-mysql-') as directory:
    defaults = Path(directory) / 'client.cnf'
    defaults.write_text('[client]\n' + '\n'.join(
        key + '=' + option(value) for key, value in {
            'host': config.get('DB_HOST', '127.0.0.1'),
            'port': config.get('DB_PORT', '3306'),
            'user': config.get('DB_USER', 'scored'),
            'password': config['DB_PASSWORD'],
            'protocol': 'TCP',
        }.items()) + '\n')
    defaults.chmod(0o600)
    command = ['mysql', f'--defaults-extra-file={defaults}']
    count = subprocess.check_output(command + ['--batch', '--skip-column-names', '-e',
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='side_b'"], text=True).strip()
    if int(count) != 0:
        raise SystemExit('La base side_b ya contiene tablas. No se sobrescribió nada. Para actualizar: php scripts/migrate.php')
    dump = root / 'assets/sql/side_b_2026-11-09.sql'
    with dump.open('rb') as source:
        subprocess.run(command, stdin=source, check=True)

subprocess.run(['php', str(root / 'scripts/migrate.php')], cwd=root, env=config, check=True)
print('Respaldo restaurado y migración aplicada. Inicia la web con: bash scripts/serve.sh')
