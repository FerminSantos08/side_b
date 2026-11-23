#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if ! command -v php >/dev/null; then
  echo 'Falta PHP. Consulta los requisitos del README.' >&2
  exit 1
fi
exec php -S 127.0.0.1:8080 -t . scripts/router.php
