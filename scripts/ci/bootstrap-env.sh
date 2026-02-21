#!/usr/bin/env bash
set -euo pipefail

put() {
  local k="$1"
  local v="${2:-}"
  if [[ -z "${!k:-}" && -n "$v" ]]; then
    echo "$k=$v" >> "$GITHUB_ENV"
    export "$k=$v"
  fi
}

# Laravel boot
put APP_ENV "${APP_ENV:-testing}"
put APP_DEBUG "${APP_DEBUG:-true}"

# ENCRYPTION_KEY (gera no CI)
if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  put ENCRYPTION_KEY "$KEY"
fi

# Broadcast / Pusher (evitar null)
put BROADCAST_DRIVER "${BROADCAST_DRIVER:-log}"
put BROADCAST_CONNECTION "${BROADCAST_CONNECTION:-log}"

put PUSHER_APP_ID "${PUSHER_APP_ID:-ci}"
put PUSHER_APP_KEY "${PUSHER_APP_KEY:-ci}"
put PUSHER_APP_SECRET "${PUSHER_APP_SECRET:-ci}"
put PUSHER_APP_CLUSTER "${PUSHER_APP_CLUSTER:-mt1}"

# Reverb (se seu app usa)
put REVERB_APP_ID "${REVERB_APP_ID:-ci}"
put REVERB_APP_KEY "${REVERB_APP_KEY:-ci}"
put REVERB_APP_SECRET "${REVERB_APP_SECRET:-ci}"
put REVERB_HOST "${REVERB_HOST:-127.0.0.1}"
put REVERB_PORT "${REVERB_PORT:-9000}"
put REVERB_SCHEME "${REVERB_SCHEME:-http}"

# Evo (seu padrão)
put EVO_BASE_URL "${EVO_BASE_URL:-http://127.0.0.1}"
put EVO_GLOBAL_API "${EVO_GLOBAL_API:-ci}"
put EVO_API_VERSION "${EVO_API_VERSION:-V1}"
put EVO_DDI "${EVO_DDI:-55}"
put EVO_DDD "${EVO_DDD:-11}"

echo "bootstrap-env.sh OK"
