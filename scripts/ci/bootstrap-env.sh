#!/usr/bin/env bash
set -euo pipefail

# Escreve variáveis no GITHUB_ENV
write_env() {
  local key="$1"
  local val="$2"
  # Só escreve se ainda não estiver definido no ambiente
  if [[ -z "${!key:-}" ]]; then
    echo "${key}=${val}" >> "${GITHUB_ENV}"
    export "${key}=${val}"
  fi
}

# 1) ENCRYPTION_KEY (sua helper exige isso no boot)
if [[ -z "${ENCRYPTION_KEY:-}" ]]; then
  KEY="$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")"
  write_env "ENCRYPTION_KEY" "${KEY}"
fi

# 2) Evitar que Broadcast/Pusher exploda no package:discover
# Laravel 11 usa BROADCAST_CONNECTION; muitos projetos ainda tem BROADCAST_DRIVER.
write_env "BROADCAST_CONNECTION" "${BROADCAST_CONNECTION:-log}"
write_env "BROADCAST_DRIVER"     "${BROADCAST_DRIVER:-log}"

# Se em algum ponto o Pusher for instanciado, garante strings não-nulas:
write_env "PUSHER_APP_ID"       "${PUSHER_APP_ID:-ci}"
write_env "PUSHER_APP_KEY"      "${PUSHER_APP_KEY:-ci}"
write_env "PUSHER_APP_SECRET"   "${PUSHER_APP_SECRET:-ci}"
write_env "PUSHER_APP_CLUSTER"  "${PUSHER_APP_CLUSTER:-mt1}"

# 3) Evo API (para não dar null em property string no boot)
# Ajuste os nomes se no seu config/evoapi.php forem outros.
write_env "EVOAPI_BASE_URL"     "${EVOAPI_BASE_URL:-http://127.0.0.1}"
write_env "EVOAPI_GLOBAL_API"   "${EVOAPI_GLOBAL_API:-ci}"
write_env "EVOAPI_DDI"          "${EVOAPI_DDI:-55}"
write_env "EVOAPI_DDD"          "${EVOAPI_DDD:-11}"
write_env "EVOAPI_VERSION"      "${EVOAPI_VERSION:-V1}"

# 4) Opcional: reduzir “surpresas” do Laravel em CI
write_env "APP_ENV"   "${APP_ENV:-testing}"
write_env "APP_DEBUG" "${APP_DEBUG:-true}"

echo "CI bootstrap-env concluído."
