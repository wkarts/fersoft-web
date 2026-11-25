#!/usr/bin/env bash
set -euo pipefail

# Conserva a autenticação do PHPClasses já configurada pelo CI.
if [[ -n "${COMPOSER_GITHUB_TOKEN:-}" ]]; then
  COMPOSER_AUTH=$(jq -c '.["github-oauth"]["github.com"] = env.COMPOSER_GITHUB_TOKEN' <<< "${COMPOSER_AUTH:-"{}"}")
  export COMPOSER_AUTH
fi

if env -u COMPOSER_GITHUB_TOKEN composer install "$@"; then
  exit 0
else
  install_status=$?
fi

# Distingue falhas de acesso ao arquivo privado de outros erros do Composer.
ref=$(jq -r '.packages[] | select(.name == "argws/laravel-updater") | .dist.reference' composer.lock)
if [[ "$ref" =~ ^[0-9a-f]{40}$ && -n "${COMPOSER_GITHUB_TOKEN:-}" ]]; then
  status=$(curl --silent --output /dev/null --write-out '%{http_code}' --max-time 15 \
    --header "Authorization: Bearer $COMPOSER_GITHUB_TOKEN" \
    "https://api.github.com/repos/wkarts/laravel-updater/zipball/$ref") || status=000
  case "$status" in
    401|403|404)
      echo "::error::GitHub devolveu HTTP $status para o arquivo privado de wkarts/laravel-updater. Confira a permissão Contents: read do token usado pelo Composer."
      ;;
  esac
fi
exit "$install_status"
