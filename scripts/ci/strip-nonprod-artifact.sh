#!/usr/bin/env bash
# Elimina del árbol local lo que no debe llegar al cPanel (antes del FTPS).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

echo "==> Strip rutas / archivos de desarrollo"

rm -rf \
  docs \
  tests \
  test-results \
  .github \
  .cursor \
  .claude

rm -f \
  .env.example \
  .gitignore \
  .DS_Store \
  config/tenants.secrets.example.php

find . -name '.DS_Store' -type f -delete 2>/dev/null || true
find . -name '*.md' -type f -not -path './.git/*' -delete 2>/dev/null || true

echo "==> Strip scripts/"
rm -rf scripts

echo "==> Strip listo"
