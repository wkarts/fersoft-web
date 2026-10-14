#!/usr/bin/env bash
# Build isolado da camada web. Não altera package.json/package-lock.json do ERP.
# Evita dependências de automação legacy (Puppeteer/zoonru) que não são usadas no Vite.
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TOOL_ROOT="$(mktemp -d)"
PACKAGES="$TOOL_ROOT/ui-vite"

cleanup() {
    if [ -L "$PROJECT_ROOT/node_modules" ]; then
        if [ "$(readlink "$PROJECT_ROOT/node_modules")" = "$PACKAGES/node_modules" ]; then
            rm "$PROJECT_ROOT/node_modules"
        fi
    fi
    rm -rf "$TOOL_ROOT"
}
trap cleanup EXIT

if [ -e "$PROJECT_ROOT/node_modules" ] || [ -L "$PROJECT_ROOT/node_modules" ]; then
    echo "ERRO: node_modules já existe. Build isolado não substitui diretórios locais." >&2
    exit 1
fi

mkdir -p "$PACKAGES"
cat > "$PACKAGES/package.json" <<'JSON'
{
  "private": true,
  "type": "module",
  "dependencies": {
    "axios": "1.7.7",
    "laravel-echo": "2.3.0",
    "laravel-vite-plugin": "0.8.1",
    "pusher-js": "8.4.0",
    "vite": "4.5.5"
  }
}
JSON

npm install --prefix "$PACKAGES" --ignore-scripts=false --no-audit --no-fund --prefer-offline
ln -s "$PACKAGES/node_modules" "$PROJECT_ROOT/node_modules"

cd "$PROJECT_ROOT"
"$PROJECT_ROOT/node_modules/.bin/vite" build
test -s "$PROJECT_ROOT/public/build/manifest.json"

echo "Build Vite isolado concluído sem instalar Puppeteer nem modificar o lock original."
