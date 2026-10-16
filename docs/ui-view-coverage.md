# Cobertura das views e formulários

Inventário estático da árvore `resources/views`, em 16/10/2026. As contagens de formulários indicam arquivos que contêm uma tag textual `<form>`, incluindo layouts e parciais; não representam telas ou rotas independentes.

| Superfície | Views | Arquivos com `<form>` | Tratamento |
| --- | ---: | ---: | --- |
| `default.layout` (painel ERP) | 671 | 493 | `body.ui-native-shell`, sistema visual e `ui-form-harmony.css` |
| `relatorios.default` | 50 | 0 | Dimensões de impressão preservadas |
| Updater (`layout` e `layout-auth`) | 27 | 24 | Layout e assets próprios do pacote |
| Delivery e comércio (cinco layouts) | 84 | 36 | Sites e CSS próprios, fora do painel |
| `layouts.app` | 6 | 4 | CSS próprio |
| Outros layouts explícitos | 5 | 0 | Login/relatórios |
| Sem `@extends` literal | 282 | 41 | 26 documentos com `<body>` e 15 parciais com formulário |
| **Total** | **1125** | **598** | Contagem por arquivo, sujeita a sobreposição de parciais |

Quatro views autônomas do PDV (`frontBox/main`, `main2`, `main3`, `pdv_assincrono`) usam `ui-pos-refined` e `ui-pos.css`, com tema independente. O login, o portal do cliente, o Updater, delivery/e-commerce, formulários públicos e relatórios também usam layouts separados. Por isso as regras do painel permanecem escopadas a `body.ui-native-shell`.

Nos 493 arquivos com formulários do painel, 424 contêm estilos inline; 98 views posicionam algum botão por `margin-top` em pixels. O CSS compartilhado alinha os componentes repetidos sem alterar campos, IDs, submissões e regras de visibilidade. Formulários com desenho ou JavaScript próprios continuam exigindo verificação individual com dados reais. Entre as páginas de maior densidade, `remessa_nfe/register`, `vendas/list`, `cte/list` e `pesagens/list` receberam correções específicas nesta branch; `frontBox/register_nfe` e outras telas com layout próprio seguem como pontos de validação funcional e visual em ambiente real.

A revisão estática percorreu todos os arquivos. Ela não comprova que cada rota, permissão ou modal tenha sido aberto e validado visualmente. `tests/ui/test-view-contracts.py` verifica contratos conservados e o formulário de três views de produtos; os cenários de navegador existentes cobrem uma amostra operacional, não os 598 arquivos com tags `<form>`.

## Revisão dirigida de telas fiscais densas

| View | Pontos corrigidos | Controles mantidos |
| --- | --- | --- |
| `cte/list.blade.php` | Filtro que somava mais de 12 colunas, alinhamento de ações, cartões da grade e espaçamento de seis modais; a classe inválida do campo inicial de inutilização foi reparada. | Formulários de filtro e relatório, tabela/grade, seleção, paginação, ações de transmissão, impressão, consulta, cancelamento, CC-e, XML e fatura; os seis IDs de modal e seus eventos. |
| `remessa_nfe/register.blade.php` | Formulário e ações finais contidos na superfície, dois botões de consulta alinhados sem `<br>` artificiais, busca de produto e sete modais; modal pequeno de código com largura adequada. | Formulário de gravação/atualização, campos fiscais, JS, modal de produto, referência, pagamento, código, cliente e transportadora, com IDs e ações preservados. |

As regras novas ficam restritas a `.cte-page--list` e `.fiscal-page--remessa_nfe-register` ou aos respectivos modais. A comparação de contratos da ZIP `develop` por `tests/ui/test-view-contracts.py` inclui ambas as views; verificação com dados reais em homologação ainda é necessária.
