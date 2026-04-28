#!/usr/bin/env bash
set -euo pipefail

CI_RUN_FULL_TESTS="${CI_RUN_FULL_TESTS:-false}"
PHPUNIT_TIMEOUT_SECONDS="${PHPUNIT_TIMEOUT_SECONDS:-1800}"
PHPUNIT_HEARTBEAT_SECONDS="${PHPUNIT_HEARTBEAT_SECONDS:-30}"

run_with_timeout() {
  local cmd="$1"
  local log_file
  log_file="$(mktemp)"

  echo "Executando: ${cmd}"

  set +e
  timeout --foreground --signal=TERM --kill-after=30s "${PHPUNIT_TIMEOUT_SECONDS}" bash -lc "${cmd}" > >(tee "${log_file}") 2>&1 &
  local pid=$!
  local started_at=$SECONDS

  while kill -0 "${pid}" 2>/dev/null; do
    sleep "${PHPUNIT_HEARTBEAT_SECONDS}"

    if kill -0 "${pid}" 2>/dev/null; then
      local elapsed=$((SECONDS - started_at))
      echo "[heartbeat] PHPUnit em execução há ${elapsed}s (timeout=${PHPUNIT_TIMEOUT_SECONDS}s)."
    fi
  done

  wait "${pid}"
  local exit_code=$?
  set -e

  if [ "${exit_code}" -eq 124 ]; then
    echo "PHPUnit excedeu ${PHPUNIT_TIMEOUT_SECONDS}s e foi encerrado por timeout controlado."
  fi

  if [ "${exit_code}" -ne 0 ]; then
    echo "Últimas 50 linhas da execução de teste:"
    tail -n 50 "${log_file}" || true
    rm -f "${log_file}"
    return "${exit_code}"
  fi

  rm -f "${log_file}"
}

if [ -x vendor/bin/phpunit ]; then
  if grep -q 'testsuite name="Unit"' phpunit.xml 2>/dev/null; then
    run_with_timeout "./vendor/bin/phpunit --testsuite=Unit --testdox --colors=never"
  else
    run_with_timeout "./vendor/bin/phpunit --testdox --colors=never"
  fi

  if [ "${CI_RUN_FULL_TESTS}" = "true" ]; then
    run_with_timeout "./vendor/bin/phpunit --testdox --colors=never"
  else
    echo "CI_RUN_FULL_TESTS=false: suíte completa ignorada para manter estabilidade e tempo de execução do CI."
  fi
elif [ -f artisan ]; then
  run_with_timeout "php artisan test --testsuite=Unit --colors=never"
else
  echo "Nenhum runner de teste PHP encontrado; etapa ignorada."
fi
