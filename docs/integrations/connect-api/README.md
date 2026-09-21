# Integração Connect|API — FERSOFT WEB

A Connect|API é o único canal WhatsApp ativo do FERSOFT WEB.

## Princípios

- o banco local do ERP define quais instâncias pertencem à instalação;
- o painel nunca lista todas as instâncias existentes na Connect|API;
- cada registro local é vinculado a um `empresa_id`;
- instâncias alheias de outras instalações ou produtos são ignoradas;
- não existe seleção de Legacy/Evo/Provider no ERP;
- tokens são gerados automaticamente;
- cada instância possui seu próprio token de webhook;
- a URL pública do webhook é detectada automaticamente pelo domínio em uso;
- não existem `CONNECT_API_WEBHOOK_URL` ou `CONNECT_API_WEBHOOK_SECRET` globais;
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
  -> POST /api/webhooks/connect-api/{token-da-instancia}
  -> resolve ConnectApiInstance pelo hash do token
  -> ConnectApiWebhookEvent
  -> ProcessConnectApiWebhookEvent
  -> ConnectApiAutomationDispatcher
  -> Ponto / automações / demais módulos
```

## Configuração mínima

```env
CONNECT_API_BASE_URL=
CONNECT_API_BOOTSTRAP_KEY=
CONNECT_API_TIMEOUT=30
CONNECT_API_CONNECT_TIMEOUT=10
CONNECT_API_DDI=55
CONNECT_API_DDD=75
CONNECT_API_INSTANCE_PREFIX=
```

A URL e o token do webhook não são configurações de ambiente.

Consulte também:

- [WEBHOOKS.md](WEBHOOKS.md)
- [AUTOMATIONS.md](AUTOMATIONS.md)
- [TEMPLATES.md](TEMPLATES.md)
- [POINT-INTEGRATION.md](POINT-INTEGRATION.md)
- [MIGRATION.md](MIGRATION.md)


## Provisionamento com telefone

Ao provisionar ou reprovisionar uma instância, o Master informa o número do WhatsApp com DDI + DDD + número.

Exemplo:

```text
5575999999999
```

O FERSOFT normaliza o telefone e envia à Connect|API junto com o bootstrap técnico da instância. O provider técnico necessário ao contrato da Connect|API é definido internamente e não é exposto como escolha ao usuário do ERP.

O fluxo é:

```text
empresa + telefone
 -> POST /instance/create
 -> integration técnica WHATSAPP-ZAPO
 -> token da instância
 -> número internacional
 -> pairing/QR
 -> webhook automático
```

Erros remotos passam a preservar a mensagem de validação retornada pela Connect|API, evitando exibir apenas `Bad Request` quando houver uma causa mais específica.


## Gerenciador Master

O gerenciador Connect|API preserva o comportamento funcional da antiga tela de instâncias:

- usuário `super` enxerga todas as empresas cadastradas;
- tenant comum enxerga somente a própria empresa;
- empresas sem instância também aparecem na grade;
- provisionamento individual por empresa;
- provisionamento manual pelo botão superior;
- QR Code e código de pareamento;
- teste de envio;
- restart e disconnect;
- bloqueio/desbloqueio;
- sincronização do webhook;
- reprovisionamento;
- exclusão local + remota pelo Master.

A tela não depende de jQuery/Select2 para funcionar. Os modais e ações do módulo usam JavaScript nativo, evitando falhas de `$ is not defined` quando o tema carrega scripts em ordem diferente.

As operações administrativas relevantes escrevem no log da aplicação sem registrar API keys ou tokens.
