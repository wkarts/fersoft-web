# Webhooks Connect|API

## Endpoint por instância

Cada instância recebe um token exclusivo de webhook. O endpoint efetivo é:

```http
POST /api/webhooks/connect-api/{TOKEN_DA_INSTANCIA}
```

O token é criado automaticamente pelo FERSOFT WEB quando a instância é provisionada. Não existe URL de webhook nem segredo global para configurar no `.env`.

## Detecção automática da URL pública

No provisionamento ou sincronização, o FERSOFT WEB detecta a origem pública da requisição atual:

```text
scheme + host em uso
  -> https://cliente.fersofterp.com.br
```

e monta automaticamente:

```text
https://cliente.fersofterp.com.br/api/webhooks/connect-api/{TOKEN}
```

Quando não existe contexto HTTP, como em CLI/queue, `APP_URL` é usado somente como fallback.

A detecção respeita o request do Laravel e, portanto, o comportamento de proxy/Cloudflare configurado na aplicação.

## Segurança por instância

`connect_api_instances` mantém:

```text
webhook_token              criptografado em repouso
webhook_token_hash         SHA-256 para lookup/autenticação
webhook_configured_at
webhook_last_received_at
```

O token puro não é pesquisado no banco. Quando uma chamada chega, o controller calcula SHA-256 do token da URL e resolve exatamente uma instância local.

O payload externo nunca define `empresa_id`. A autoridade é:

```text
token da URL
  -> webhook_token_hash
  -> connect_api_instances
  -> empresa_id
```

Se o payload também informar `instance_name`, o valor deve corresponder à instância resolvida pelo token. Divergência é rejeitada.

## Provisionamento

```text
criar instância
  -> gerar token exclusivo
  -> detectar URL pública atual
  -> montar endpoint por instância
  -> POST /webhook/set/{instanceName} na Connect|API
  -> registrar webhook_configured_at
```

Reprovisionar uma instância gera um novo token de webhook. Restart/reconnect comum não rotaciona o token.

Instâncias criadas antes deste modelo recebem o token automaticamente na primeira sincronização/status/pareamento. O Master também dispõe da ação **Sincronizar webhook** para reparo explícito sem recriar sessão ou pareamento.

## Inbox e idempotência

Cada entrega válida é persistida em `connect_api_webhook_events`.

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

O webhook HTTP apenas autentica a instância pelo token, valida a identidade do payload, persiste o evento e dispara `ProcessConnectApiWebhookEvent`. Regras de negócio devem acontecer fora da requisição HTTP do webhook.
