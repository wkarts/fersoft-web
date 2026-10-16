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

Nos 493 arquivos com formulários do painel, 424 contêm estilos inline; 98 views posicionam algum botão por `margin-top` em pixels. O CSS compartilhado alinha os componentes repetidos sem alterar campos, IDs, submissões e regras de visibilidade. Formulários com desenho ou JavaScript próprios continuam exigindo verificação individual com dados reais. Páginas com maior densidade de estilos inline e modais incluem `remessa_nfe/register`, `frontBox/register_nfe`, `vendas/list`, `cte/list` e `pesagens/list`.

A revisão estática percorreu todos os arquivos. Ela não comprova que cada rota, permissão ou modal tenha sido aberto e validado visualmente. `tests/ui/test-view-contracts.py` verifica contratos conservados e o formulário de três views de produtos; os cenários de navegador existentes cobrem uma amostra operacional, não os 598 arquivos com tags `<form>`.
