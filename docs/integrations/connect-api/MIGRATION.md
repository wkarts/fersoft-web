# Migração da integração WhatsApp anterior

## Regra

Sessões e conexões existentes não são reaproveitadas.

São descartados:

- token remoto anterior;
- instance id remoto anterior;
- sessão WhatsApp anterior;
- QR anterior;
- status remoto anterior.

São preservados apenas dados locais úteis à continuidade:

- empresa_id;
- usuario_id/filial_id quando existentes;
- nome da instância;
- bloqueio administrativo.

A migration de transição popula `connect_api_instances` com status `awaiting_provisioning`.

## Novo ciclo

```text
registro local
 -> provisionar Connect|API
 -> gerar token automaticamente
 -> configurar webhook
 -> provisionar templates padrão
 -> QR Code ou código de pareamento
 -> connection.update = open
```

## Compatibilidade de banco

Migrations históricas antigas não são renomeadas nem apagadas. Elas continuam existindo no histórico para que instalações já atualizadas não tentem executá-las novamente. O runtime novo não depende desses componentes.


## Webhook por instância

Instalações que já possuíam registros `connect_api_instances` recebem os novos campos de webhook de forma aditiva e nullable.

Nenhuma sessão WhatsApp é recriada para essa adaptação. Na primeira consulta de status, pareamento ou sincronização administrativa, o FERSOFT WEB:

```text
gera token exclusivo da instância
-> detecta o domínio público em uso
-> configura /api/webhooks/connect-api/{token} na Connect|API
-> registra webhook_configured_at
```

`CONNECT_API_WEBHOOK_SECRET` e `CONNECT_API_WEBHOOK_URL` deixam de existir. Não é necessário editar o `.env` para cada instalação ou tenant.
