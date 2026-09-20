# Integração com o sistema de Ponto

O Ponto é o primeiro consumidor de automação inbound da Connect|API.

## Fluxo

```text
Funcionário
 -> WhatsApp
 -> Connect|API
 -> messages.upsert
 -> /api/webhooks/connect-api/{token-da-instancia}
 -> connect_api_webhook_events
 -> ProcessConnectApiWebhookEvent
 -> ConnectApiAutomationDispatcher
 -> PontoWhatsAppController (ponte de compatibilidade atual)
 -> WhatsAppUtil
 -> ConnectApiMessageService
 -> Connect|API
 -> Funcionário
```

## Compatibilidade atual

A regra de negócio existente do `PontoWhatsAppController` foi preservada. O dispatcher entrega o payload recebido à implementação atual para evitar refatoração abrupta da jornada, PIN, localização, portaria, holerite e regras de movimentação.

A saída do Ponto não chama mais endpoints ou tabelas da tecnologia anterior. Texto e mídia são enviados por `WhatsAppUtil`, que agora é uma fachada exclusiva da Connect|API.

## Evolução recomendada

Em uma etapa futura, a lógica de negócio poderá ser extraída para um `PontoAutomationHandler` que receba somente o DTO normalizado. Essa extração não é necessária para a substituição do transporte e pode ser feita sem alterar o webhook público.
