#!/usr/bin/env bash
set -euo pipefail

WEBHOOK_URL="${WEBHOOK_URL:-}"
WEBHOOK_TOKEN="${WEBHOOK_TOKEN:-}"
PAYLOAD_FILE="${1:-}"
MAX_RETRIES="${WEBHOOK_MAX_RETRIES:-3}"
BACKOFF_SECONDS="${WEBHOOK_BACKOFF_SECONDS:-2}"

if [[ -z "$WEBHOOK_URL" ]]; then
  echo "[notify:webhook] WEBHOOK_URL não definido; notificação ignorada."
  exit 0
fi

if [[ -z "$PAYLOAD_FILE" || ! -f "$PAYLOAD_FILE" ]]; then
  echo "[notify:webhook] Arquivo de payload inválido: $PAYLOAD_FILE" >&2
  exit 1
fi

headers=(-H 'Content-Type: application/json')
if [[ -n "$WEBHOOK_TOKEN" ]]; then
  headers+=(-H "Authorization: Bearer $WEBHOOK_TOKEN")
fi

attempt=1
while [[ "$attempt" -le "$MAX_RETRIES" ]]; do
  echo "[notify:webhook] Tentativa $attempt/$MAX_RETRIES para enviar webhook."
  http_code=$(curl -sS -L -o /tmp/webhook_response.txt -w "%{http_code}" -X POST "$WEBHOOK_URL" "${headers[@]}" --data-binary "@$PAYLOAD_FILE" || true)

  if [[ "$http_code" =~ ^2[0-9]{2}$ ]]; then
    echo "[notify:webhook] Webhook enviado com sucesso (HTTP $http_code)."
    exit 0
  fi

  echo "[notify:webhook] Falha no envio do webhook (HTTP $http_code)."
  if [[ "$attempt" -lt "$MAX_RETRIES" ]]; then
    sleep $((BACKOFF_SECONDS * attempt))
  fi
  attempt=$((attempt + 1))
done

echo "[notify:webhook] Todas as tentativas falharam."
exit 1
