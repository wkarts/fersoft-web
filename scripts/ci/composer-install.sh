#!/usr/bin/env bash
set -euo pipefail

# Conserva a autenticação do PHPClasses já configurada pelo CI.
if [[ -n "${COMPOSER_GITHUB_TOKEN:-}" ]]; then
  COMPOSER_AUTH=$(jq -c '.["github-oauth"]["github.com"] = env.COMPOSER_GITHUB_TOKEN' <<< "${COMPOSER_AUTH:-"{}"}")
  export COMPOSER_AUTH
fi

unset COMPOSER_GITHUB_TOKEN
exec composer install "$@"
