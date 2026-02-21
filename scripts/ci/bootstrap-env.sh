#!/usr/bin/env bash
set -euo pipefail

# -------------------------------------------------------------------
# scripts/ci/bootstrap-env.sh
# - Deve ser usado com: source scripts/ci/bootstrap-env.sh
# - Exporta variáveis para o shell atual (vale no mesmo step)
# - E também persiste no GitHub Actions via $GITHUB_ENV
# -------------------------------------------------------------------

_ci_export() {
  local key="$1"
  local val="${2:-}"

  # exporta no shell atual (vale imediatamente)
  export "${key}=${val}"

  # persiste para próximos steps (se estiver no GitHub Actions)
  if [[ -n "${GITHUB_ENV:-}" ]]; then
    echo "${key}=${val}" >> "${GITHUB_ENV}"
  fi
}

# Detecta se está no GitHub Actions
IS_GHA=0
if [[ -n "${GITHUB_ACTIONS:-}" ]]; then
  IS_GHA=1
fi

# --------------------------
# Ambiente base de CI
# --------------------------
_ci_export APP_ENV "${APP_ENV:-testing}"
_ci_export APP_DEBUG "${APP_DEBUG:-true}"
_ci_export APP_URL "${APP_URL:-http://localhost}"
_ci_export APP_TIMEZONE "${APP_TIMEZONE:-America/Bahia}"

# --------------------------
# Laravel APP_KEY (CI)
# --------------------------
if [[ -z "${APP_KEY:-}" ]]; then
  APP_KEY_GEN="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  _ci_export APP_KEY "${APP_KEY_GEN}"
fi

# --------------------------
# ENCRYPTION_KEY (CI)
# - vem do Repo Secret ENCRYPTION_KEY
# - se não vier, gera (pra CI rodar SEM depender do secret)
# --------------------------
if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  ENCRYPTION_KEY_GEN="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  _ci_export ENCRYPTION_KEY "${ENCRYPTION_KEY_GEN}"
fi

# --------------------------
# Database: SQLite (CI)
# --------------------------
_ci_export DB_CONNECTION "${DB_CONNECTION:-sqlite}"
_ci_export DB_DATABASE "${DB_DATABASE:-database/database.sqlite}"
_ci_export DB_FOREIGN_KEYS "${DB_FOREIGN_KEYS:-true}"

# --------------------------
# Broadcast / Pusher / Reverb
# - Evita crash no package:discover quando Pusher recebe null
# --------------------------
_ci_export BROADCAST_DRIVER "${BROADCAST_DRIVER:-log}"
_ci_export BROADCAST_CONNECTION "${BROADCAST_CONNECTION:-log}"

# defaults “ci” para não quebrar o construtor do Pusher
_ci_export PUSHER_APP_ID "${PUSHER_APP_ID:-ci}"
_ci_export PUSHER_APP_KEY "${PUSHER_APP_KEY:-ci}"
_ci_export PUSHER_APP_SECRET "${PUSHER_APP_SECRET:-ci}"
_ci_export PUSHER_APP_CLUSTER "${PUSHER_APP_CLUSTER:-mt1}"

# Reverb (se você usa no projeto)
_ci_export REVERB_APP_ID "${REVERB_APP_ID:-1}"
_ci_export REVERB_APP_KEY "${REVERB_APP_KEY:-ci}"
_ci_export REVERB_APP_SECRET "${REVERB_APP_SECRET:-ci}"
_ci_export REVERB_HOST "${REVERB_HOST:-127.0.0.1}"
_ci_export REVERB_PORT "${REVERB_PORT:-9000}"
_ci_export REVERB_SCHEME "${REVERB_SCHEME:-http}"

# --------------------------
# PHPClasses (Composer Auth)
# - só configura se tiver token
# --------------------------
if [[ -n "${PHPCLASSES_TOKEN:-}" ]]; then
  COMPOSER_AUTH_JSON='{"http-basic":{"www.phpclasses.org":{"username":"wkarts","password":"'"${PHPCLASSES_TOKEN}"'"},"phpclasses.org":{"username":"wkarts","password":"'"${PHPCLASSES_TOKEN}"'"}}}'
  _ci_export COMPOSER_AUTH "${COMPOSER_AUTH_JSON}"
fi

# --------------------------
# Garantias mínimas de filesystem
# --------------------------
mkdir -p storage/framework/{cache,sessions,views} bootstrap/cache database
touch database/database.sqlite || true
