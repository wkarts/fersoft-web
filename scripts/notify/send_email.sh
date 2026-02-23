#!/usr/bin/env bash
set -euo pipefail

SMTP_HOST="${SMTP_HOST:-}"
SMTP_PORT="${SMTP_PORT:-}"
SMTP_USER="${SMTP_USER:-}"
SMTP_PASS="${SMTP_PASS:-}"
SMTP_FROM="${SMTP_FROM:-}"
SMTP_TO="${SMTP_TO:-}"
EMAIL_SUBJECT="${EMAIL_SUBJECT:-}"
EMAIL_BODY_FILE="${1:-}"

if [[ -z "$SMTP_HOST" || -z "$SMTP_PORT" || -z "$SMTP_USER" || -z "$SMTP_PASS" || -z "$SMTP_FROM" || -z "$SMTP_TO" ]]; then
  echo "[notify:email] Configuração SMTP incompleta; notificação ignorada."
  exit 0
fi

if [[ -z "$EMAIL_BODY_FILE" || ! -f "$EMAIL_BODY_FILE" ]]; then
  echo "[notify:email] Arquivo de corpo de e-mail inválido: $EMAIL_BODY_FILE" >&2
  exit 1
fi

python3 - <<'PY'
import os
import smtplib
from email.mime.text import MIMEText

smtp_host = os.environ['SMTP_HOST']
smtp_port = int(os.environ['SMTP_PORT'])
smtp_user = os.environ['SMTP_USER']
smtp_pass = os.environ['SMTP_PASS']
smtp_from = os.environ['SMTP_FROM']
smtp_to = [item.strip() for item in os.environ['SMTP_TO'].split(',') if item.strip()]
subject = os.environ.get('EMAIL_SUBJECT', 'Notificação de release')
body_file = os.environ['EMAIL_BODY_FILE']

with open(body_file, 'r', encoding='utf-8') as fh:
    body = fh.read()

msg = MIMEText(body, 'plain', 'utf-8')
msg['Subject'] = subject
msg['From'] = smtp_from
msg['To'] = ', '.join(smtp_to)

with smtplib.SMTP(smtp_host, smtp_port, timeout=30) as server:
    server.starttls()
    server.login(smtp_user, smtp_pass)
    server.sendmail(smtp_from, smtp_to, msg.as_string())

print('[notify:email] E-mail enviado com sucesso.')
PY
