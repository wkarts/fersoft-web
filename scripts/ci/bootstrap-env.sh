#!/usr/bin/env bash
set -euo pipefail

# ==========================================================
# Bootstrap único de variáveis (CI/Workflows)
# - Cria .env a partir de .env.ci
# - Injeta secrets/envs do runner
# - Exporta pro $GITHUB_ENV
# - Garante defaults pra não quebrar artisan/package:discover
# ==========================================================

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT_DIR"

ENV_TEMPLATE="${ENV_TEMPLATE:-.env.ci}"
ENV_TARGET="${ENV_TARGET:-.env}"

echo "==> Bootstrap env"
echo "    Template: ${ENV_TEMPLATE}"
echo "    Target:   ${ENV_TARGET}"

if [ ! -f "$ENV_TEMPLATE" ]; then
  echo "ERROR: Arquivo ${ENV_TEMPLATE} não existe no repo."
  echo "Crie um .env.ci (baseado no seu .env) e com placeholders pros segredos."
  exit 1
fi

# 1) Cria .env a partir do template
cp -f "$ENV_TEMPLATE" "$ENV_TARGET"

# 2) Helpers
set_kv () {
  # set_kv KEY VALUE [FILE]
  local key="$1"
  local value="$2"
  local file="${3:-$ENV_TARGET}"

  # escapa barras e &
  local esc
  esc="$(printf '%s' "$value" | sed -e 's/[\/&]/\\&/g')"

  if grep -qE "^${key}=" "$file"; then
    sed -i -E "s|^${key}=.*|${key}=${esc}|g" "$file"
  else
    printf "\n%s=%s\n" "$key" "$value" >> "$file"
  fi
}

export_to_github_env () {
  # export_to_github_env KEY VALUE
  local key="$1"
  local value="$2"

  # Exporta também pro processo atual
  export "${key}=${value}"

  # Exporta pro GitHub Actions (se disponível)
  if [ -n "${GITHUB_ENV:-}" ]; then
    {
      echo "${key}<<__EOF__"
      echo "${value}"
      echo "__EOF__"
    } >> "$GITHUB_ENV"
  fi
}

# 3) Garante chaves (ENCRYPTION_KEY / APP_KEY)
if [ -z "${ENCRYPTION_KEY:-}" ]; then
  ENCRYPTION_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
fi
export_to_github_env "ENCRYPTION_KEY" "$ENCRYPTION_KEY"
set_kv "ENCRYPTION_KEY" "$ENCRYPTION_KEY"

# Se você quiser também garantir APP_KEY (em CI geralmente é útil)
if [ -z "${APP_KEY:-}" ]; then
  APP_KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
fi
export_to_github_env "APP_KEY" "$APP_KEY"
set_kv "APP_KEY" "$APP_KEY"

# 4) Defaults de CI para NÃO quebrar package:discover
#    (o seu erro do Pusher estourou aqui: auth_key null)
#    Então em CI: força broadcast a não precisar Pusher real.
export_to_github_env "APP_ENV" "${APP_ENV:-testing}"
export_to_github_env "APP_DEBUG" "${APP_DEBUG:-true}"

# Laravel moderno usa BROADCAST_CONNECTION; muitos projetos usam BROADCAST_DRIVER.
export_to_github_env "BROADCAST_CONNECTION" "${BROADCAST_CONNECTION:-log}"
export_to_github_env "BROADCAST_DRIVER" "${BROADCAST_DRIVER:-log}"

# Reverb também pode existir, mas não pode quebrar se faltar.
export_to_github_env "REVERB_APP_ID" "${REVERB_APP_ID:-1}"
export_to_github_env "REVERB_APP_KEY" "${REVERB_APP_KEY:-local}"
export_to_github_env "REVERB_APP_SECRET" "${REVERB_APP_SECRET:-local}"
export_to_github_env "REVERB_HOST" "${REVERB_HOST:-127.0.0.1}"
export_to_github_env "REVERB_PORT" "${REVERB_PORT:-9000}"
export_to_github_env "REVERB_SCHEME" "${REVERB_SCHEME:-http}"

# Pusher dummy (evita null)
export_to_github_env "PUSHER_APP_ID" "${PUSHER_APP_ID:-1}"
export_to_github_env "PUSHER_APP_KEY" "${PUSHER_APP_KEY:-local}"
export_to_github_env "PUSHER_APP_SECRET" "${PUSHER_APP_SECRET:-local}"
export_to_github_env "PUSHER_APP_CLUSTER" "${PUSHER_APP_CLUSTER:-mt1}"

# Preenche no .env também
set_kv "APP_ENV" "${APP_ENV:-testing}"
set_kv "APP_DEBUG" "${APP_DEBUG:-true}"
set_kv "BROADCAST_CONNECTION" "${BROADCAST_CONNECTION:-log}"
set_kv "BROADCAST_DRIVER" "${BROADCAST_DRIVER:-log}"

set_kv "REVERB_APP_ID" "${REVERB_APP_ID:-1}"
set_kv "REVERB_APP_KEY" "${REVERB_APP_KEY:-local}"
set_kv "REVERB_APP_SECRET" "${REVERB_APP_SECRET:-local}"
set_kv "REVERB_HOST" "${REVERB_HOST:-127.0.0.1}"
set_kv "REVERB_PORT" "${REVERB_PORT:-9000}"
set_kv "REVERB_SCHEME" "${REVERB_SCHEME:-http}"

set_kv "PUSHER_APP_ID" "${PUSHER_APP_ID:-1}"
set_kv "PUSHER_APP_KEY" "${PUSHER_APP_KEY:-local}"
set_kv "PUSHER_APP_SECRET" "${PUSHER_APP_SECRET:-local}"
set_kv "PUSHER_APP_CLUSTER" "${PUSHER_APP_CLUSTER:-mt1}"

# 5) Injeta secrets/envs “do seu .env” se existirem no runner.
#    Aqui você bota todos que você listou como Repository Secrets.
#    (Se o Secret existir, ele sobrescreve o .env.ci.)
declare -a SECRET_KEYS=(
  "PHPCLASSES_TOKEN"
  "SMTP_HOST" "SMTP_PORT" "SMTP_USER" "SMTP_PASS" "SMTP_FROM" "SMTP_TO"
  "WEBHOOK_URL" "WEBHOOK_TOKEN"
  "REPOSITORY_PERSONAL_TOKEN"

  # EVO
  "EVO_DDI" "EVO_DDD" "EVO_BASE_URL" "EVO_GLOBAL_API" "EVO_API_VERSION"
  "EVO_QR_LOGO_BASE64" "EVO_QR_LOGO_SIZE"
  "EVO_TOKEN_PREFIX" "EVO_TOKEN_ALPHABET"
  "EVO_WHATSAPP_MODAL_HINTBUTTON" "EVO_WHATSAPP_MODAL_TITLE" "EVO_WHATSAPP_MODAL_HINTFOOTER"

  # VITE / REVERB / PUSHER (se você também setou em secrets)
  "VITE_APP_NAME" "VITE_REVERB_APP_KEY" "VITE_REVERB_HOST" "VITE_REVERB_PORT" "VITE_REVERB_SCHEME"
  "VITE_PUSHER_APP_KEY" "VITE_PUSHER_HOST" "VITE_PUSHER_PORT" "VITE_PUSHER_SCHEME" "VITE_PUSHER_APP_CLUSTER"
)

for k in "${SECRET_KEYS[@]}"; do
  v="${!k:-}"
  if [ -n "$v" ]; then
    export_to_github_env "$k" "$v"
    set_kv "$k" "$v"
  fi
done

# 6) COMPOSER_AUTH para PHPClasses (sem gravar auth.json no repo)
if [ -n "${PHPCLASSES_TOKEN:-}" ]; then
  COMPOSER_AUTH_JSON=$(cat <<JSON
{"http-basic":{"www.phpclasses.org":{"username":"wkarts","password":"${PHPCLASSES_TOKEN}"},"phpclasses.org":{"username":"wkarts","password":"${PHPCLASSES_TOKEN}"}}}
JSON
)
  export_to_github_env "COMPOSER_AUTH" "$COMPOSER_AUTH_JSON"
fi

echo "==> .env gerado e variáveis exportadas com sucesso."
