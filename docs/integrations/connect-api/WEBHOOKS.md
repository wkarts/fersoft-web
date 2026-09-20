# Webhooks Connect|API

## Endpoint

```http
POST /api/webhooks/connect-api
```

O endpoint é configurado automaticamente na instância durante o provisionamento.

## Segurança

Defina:

```env
CONNECT_API_WEBHOOK_SECRET=
```

Quando configurado, o segredo é anexado à URL de webhook gerada pelo ERP. O controller também aceita `X-Connect-Webhook-Secret`, `X-Webhook-Secret` ou Bearer para permitir evolução futura do contrato.

O `empresa_id` nunca é aceito do payload como autoridade. O ERP resolve:

```text
instance_name recebido
  -> connect_api_instances local
  -> empresa_id local
```

Se a instância não existir no banco local, o webhook é respondido como `ignored / foreign_instance` e não participa das regras do ERP.

## Inbox e idempotência

Cada entrega é persistida em `connect_api_webhook_events`.

Estados:

```text
received
processing
processed
retry
ignored
failed
```

A chave de deduplicação considera instância, tipo, message id, timestamp e payload. Reentregas não devem executar a mesma regra duas vezes.

## Eventos iniciais

A configuração padrão registra:

- MESSAGES_UPSERT
- MESSAGES_UPDATE
- MESSAGES_DELETE
- CONNECTION_UPDATE
- QRCODE_UPDATED
- CONTACTS_UPSERT
- LOGOUT_INSTANCE
- REMOVE_INSTANCE

O normalizador converte os nomes para o vocabulário interno em minúsculas com hífen, por exemplo `messages-upsert` e `connection-update`.

## Processamento

O webhook HTTP apenas valida, resolve a instância, persiste o evento e dispara `ProcessConnectApiWebhookEvent`. Regras de negócio devem acontecer fora da requisição HTTP do webhook.
