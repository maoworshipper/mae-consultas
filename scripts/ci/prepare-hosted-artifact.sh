#!/usr/bin/env bash
# Prepara dirs de fallback public/tenants/<id> (fotos reales viven en mae-v8).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"

echo "==> Dirs de tenants (fallback local)"
mkdir -p "$ROOT/public/tenants"
for id in "$ROOT"/branding/*/features.json; do
  [[ -f "$id" ]] || continue
  client="$(basename "$(dirname "$id")")"
  mkdir -p "$ROOT/public/tenants/$client/fotos" \
    "$ROOT/public/tenants/$client/convenios"
done

echo "==> Artefacto hosted listo"
