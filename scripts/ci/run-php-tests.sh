#!/usr/bin/env bash
set -euo pipefail

CI_RUN_FULL_TESTS="${CI_RUN_FULL_TESTS:-false}"
PHPUNIT_TIMEOUT_SECONDS="${PHPUNIT_TIMEOUT_SECONDS:-1800}"
PHPUNIT_HEARTBEAT_SECONDS="${PHPUNIT_HEARTBEAT_SECONDS:-30}"

on_termination() {
  echo "Runner de testes recebeu sinal de término externo (SIGTERM/SIGINT)."
  echo "Isso normalmente indica cancelamento do job/workflow pelo GitHub Actions (não falha de assert do PHPUnit)."
  exit 143
}

trap on_termination TERM INT

run_with_timeout() {
  local cmd="$1"
  local log_file
  local pid
  local started_at
  local elapsed
  local timed_out=false

  log_file="$(mktemp)"
  echo "Executando: ${cmd}"

  set +e
  bash -lc "${cmd}" > >(tee "${log_file}") 2>&1 &
  pid=$!
  started_at=$SECONDS

  while kill -0 "${pid}" 2>/dev/null; do
    sleep "${PHPUNIT_HEARTBEAT_SECONDS}"

    if ! kill -0 "${pid}" 2>/dev/null; then
      break
    fi

    elapsed=$((SECONDS - started_at))
    echo "[heartbeat] PHPUnit em execução há ${elapsed}s (timeout=${PHPUNIT_TIMEOUT_SECONDS}s)."

    if [ "${elapsed}" -ge "${PHPUNIT_TIMEOUT_SECONDS}" ] && [ "${timed_out}" = "false" ]; then
      timed_out=true
      echo "Timeout atingido (${PHPUNIT_TIMEOUT_SECONDS}s). Enviando SIGTERM ao processo de teste."
      kill -TERM "${pid}" 2>/dev/null || true
      sleep 30
      if kill -0 "${pid}" 2>/dev/null; then
        echo "Processo não encerrou após SIGTERM; enviando SIGKILL."
        kill -KILL "${pid}" 2>/dev/null || true
      fi
    fi
  done

  wait "${pid}"
  local exit_code=$?
  set -e

  if [ "${timed_out}" = "true" ]; then
    echo "PHPUnit excedeu ${PHPUNIT_TIMEOUT_SECONDS}s e foi encerrado por timeout controlado."
    echo "Últimas 50 linhas da execução de teste:"
    tail -n 50 "${log_file}" || true
    rm -f "${log_file}"
    return 124
  fi

  if [ "${exit_code}" -eq 143 ]; then
    echo "Processo de teste recebeu SIGTERM externo (exit 143)."
    echo "Causa provável: cancelamento externo do job/workflow no GitHub Actions."
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
