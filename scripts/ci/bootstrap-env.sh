#!/usr/bin/env bash
set -euo pipefail

write_env() {
  local key="$1"
  local val="$2"
  if [[ -z "${!key:-}" && -n "${val}" ]]; then
    echo "${key}=${val}" >> "${GITHUB_ENV}"
    export "${key}=${val}"
  fi
}

# ENCRYPTION_KEY (gera no CI se não vier)
if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  write_env "ENCRYPTION_KEY" "${KEY}"
fi

# Garantir boot sem depender de serviços externos
write_env "APP_ENV"   "${APP_ENV:-testing}"
write_env "APP_DEBUG" "${APP_DEBUG:-true}"

# Broadcast/Pusher/Reverb - evitar null
write_env "BROADCAST_CONNECTION" "${BROADCAST_CONNECTION:-log}"
write_env "BROADCAST_DRIVER"     "${BROADCAST_DRIVER:-log}"

write_env "PUSHER_APP_ID"       "${PUSHER_APP_ID:-ci}"
write_env "PUSHER_APP_KEY"      "${PUSHER_APP_KEY:-ci}"
write_env "PUSHER_APP_SECRET"   "${PUSHER_APP_SECRET:-ci}"
write_env "PUSHER_APP_CLUSTER"  "${PUSHER_APP_CLUSTER:-mt1}"

# EVO API - nomes iguais ao seu .env
write_env "EVO_BASE_URL"     "${EVO_BASE_URL:-http://127.0.0.1}"
write_env "EVO_GLOBAL_API"   "${EVO_GLOBAL_API:-ci}"
write_env "EVO_API_VERSION"  "${EVO_API_VERSION:-V1}"
write_env "EVO_DDI"          "${EVO_DDI:-55}"
write_env "EVO_DDD"          "${EVO_DDD:-11}"

echo "CI bootstrap-env concluído."
