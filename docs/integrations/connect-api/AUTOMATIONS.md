# Automações

O subsistema de automações é genérico e não pertence exclusivamente ao WhatsApp.

## Estruturas

- `automation_rules`: regra configurável;
- `automation_executions`: auditoria de cada execução;
- `connect_api_webhook_events`: origem persistente para eventos da Connect|API;
- `connect_api_template_bindings`: vínculo semântico entre evento ERP e template.

## Regra

Campos principais:

```text
empresa_id        null = regra global
name
trigger
enabled
priority
stop_on_success
conditions JSON
actions JSON
```

## Triggers

Eventos externos são publicados no formato:

```text
connect-api.messages-upsert
connect-api.connection-update
...
```

O catálogo poderá receber eventos internos do ERP, por exemplo:

```text
ponto.registrado
ponto.pendente
portaria.visitante_chegou
frota.viagem_iniciada
coleta.confirmada
pesagem.concluida
financeiro.titulo_vencido
os.finalizada
```

## Condições suportadas inicialmente

```json
[
  {"field":"text","operator":"equals","value":"1"},
  {"field":"from","operator":"not_empty"},
  {"field":"text","operator":"contains","value":"HOLERITE"}
]
```

Operadores:

- equals / =
- not_equals / !=
- contains
- starts_with
- empty
- not_empty

## Ações suportadas

### Texto

```json
{
  "type":"connect_api.send_text",
  "to":"{from}",
  "message":"Recebemos sua mensagem: {text}"
}
```

### Template

```json
{
  "type":"connect_api.send_template",
  "to":"{from}",
  "event_key":"ponto.registro.confirmado",
  "parameters":["{push_name}","ENTRADA","{timestamp}","{message_id}"]
}
```

Valores entre chaves são resolvidos a partir do payload normalizado.

## Evolução

A mesma infraestrutura aceita novas ações como e-mail, notificação interna, criação/alteração de registros e webhook externo sem alterar o contrato da Connect|API.
