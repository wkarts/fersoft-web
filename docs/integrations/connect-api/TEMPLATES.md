# Templates

A Connect|API mantém templates por instância e o ERP usa bindings semânticos para não espalhar nomes físicos de templates pelos controllers.

## Binding

Tabela:

```text
connect_api_template_bindings
```

Campos:

```text
empresa_id       null = global
event_key
template_name
language
template_version
enabled
```

Exemplo:

```text
event_key      ponto.registro.confirmado
template_name  ponto_registrado
language       pt_BR
```

O código deve solicitar o evento semântico:

```php
$messages->sendTemplate(
    $empresaId,
    $telefone,
    'ponto.registro.confirmado',
    [$nome, $tipo, $dataHora, $comprovante]
);
```

A resolução prioriza binding da empresa e depois binding global.

## Templates padrão

O provisionamento tenta cadastrar:

- ponto_boas_vindas
- ponto_registrado
- frota_checklist
- coleta_confirmada
- pesagem_concluida

Os bindings são criados localmente quando o cadastro remoto é aceito ou o template já existe.

## Variáveis

Templates locais Connect|API usam BODY com parâmetros sequenciais `{{1}}` até `{{20}}`.

O ERP envia somente os valores dos parâmetros. Ele não substitui o template por texto arbitrário quando o template está indisponível.

## Provider

O ERP não conhece se o envio usa WhatsApp Business, Zapo ou Baileys. A Connect|API decide o mecanismo aplicável para a instância.
