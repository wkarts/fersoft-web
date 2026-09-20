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
