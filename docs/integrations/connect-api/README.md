# Integração Connect|API — FERSOFT WEB

A Connect|API é o único canal WhatsApp ativo do FERSOFT WEB.

## Princípios

- o banco local do ERP define quais instâncias pertencem à instalação;
- o painel nunca lista todas as instâncias existentes na Connect|API;
- cada registro local é vinculado a um `empresa_id`;
- instâncias alheias de outras instalações ou produtos são ignoradas;
- não existe seleção de Legacy/Evo/Provider no ERP;
- tokens são gerados automaticamente;
- QR Code e código de pareamento são solicitados sob demanda;
- sessões anteriores não são migradas: cada empresa deve realizar novo pareamento;
- nomes de instância existentes são preservados na transição;
- mensagens, mídia e templates passam pelo namespace `App\Services\ConnectApi`.

## Fluxo

```text
FERSOFT WEB
  -> connect_api_instances (autoridade local)
  -> ConnectApiIntegrationResolver
  -> ConnectApiClient
  -> Connect|API
  -> WhatsApp
```

No retorno:

```text
Connect|API
  -> POST /api/webhooks/connect-api
  -> ConnectApiWebhookEvent
  -> ProcessConnectApiWebhookEvent
  -> ConnectApiAutomationDispatcher
  -> Ponto / automações / demais módulos
```

Consulte também:

- [WEBHOOKS.md](WEBHOOKS.md)
- [AUTOMATIONS.md](AUTOMATIONS.md)
- [TEMPLATES.md](TEMPLATES.md)
- [POINT-INTEGRATION.md](POINT-INTEGRATION.md)
- [MIGRATION.md](MIGRATION.md)
