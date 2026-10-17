# FERSOFT — auditoria autenticada da interface em homolog3

Data: 16 de outubro de 2026. Ambiente: `https://homolog3.fersofterp.com.br/`. Acesso feito em sessão autenticada fornecida pelo proprietário, com navegação de leitura no navegador. Este registro não contém credenciais, identificadores de cliente nem conteúdo operacional da empresa.

## Versão e alcance da comparação

A instância exibiu **V.5.0.230 (`1724ec42`)** durante a inspeção. A correção de navegação e temas da [PR #290](https://github.com/wkarts/fersoft-web/pull/290), no commit **`440694e3`** da branch `feat/ui-operational-redesign-20261015`, foi **mesclada em `develop`** durante esta análise. O merge **ainda não estava implantado** na instância observada. Os defeitos visuais vistos em homolog3 descrevem a versão instalada e não comprovam o comportamento do código já mesclado. As correções surgidas desta inspeção serão propostas em outra PR de acompanhamento, ainda sem deploy verificado.

O DOM indicou `ui-theme-refined ui-native-shell`, `data-ui-layout="lateral"` e `data-ui-theme="fer-light"`. Os assets `ui-operational.css`, `fer-design-system.css` e `ui-operational.js` estavam carregados; `ui-top-nav.css` e `ui-top-nav.js`, adicionados pela PR #290, não estavam. Isso confirma especificamente que a correção do menu superior ainda não chegara a esse deploy.

O menu renderizado para esta sessão tinha **36 grupos**, **234 entradas** e **225 destinos distintos**. Foram feitas tentativas de visita de leitura a **223 destinos**. Os outros dois, `/_updater` e `/compras/reprocessar-cfop`, foram deliberadamente excluídos por poderem executar alterações. A navegação confirma somente o resultado de carregamento ou redirecionamento observado nesse perfil e nesta empresa; não valida submissões, permissões de outros perfis, operações com dados nem integrações externas.

### Entradas visíveis por grupo no perfil inspecionado

| Grupo | Entradas | Grupo | Entradas |
|---|---:|---|---:|
| SUPER | 29 | Auditoria | 1 |
| Cadastros | 21 | ClassTrib | 1 |
| e-DOC | 2 | Atendimento | 1 |
| Documentos Eletrônicos | 3 | Entradas | 10 |
| Gestão Pessoal | 5 | Controle de Ponto | 10 |
| Estoque | 6 | Kardex (Beta) | 2 |
| Vendas | 17 | Contratos | 4 |
| MTR 3.0 | 4 | Emissão de NFSe | 3 |
| Vendas Balcão | 2 | Financeiro | 8 |
| Relatórios Financeiros | 3 | Tarefas | 2 |
| Traccar | 3 | Controle de Pesagens | 5 |
| Dispositivos A.D.P. | 5 | Gestão de Frota | 17 |
| Portaria | 2 | CTe | 5 |
| CTe Os | 2 | MDFe | 3 |
| Relatórios | 2 | Arquivos Contábil | 7 |
| Apurações | 5 | Eventos | 3 |
| Locação | 2 | Delivery | 17 |
| Pedidos | 8 | Configurações | 14 |

As contagens descrevem apenas as entradas autorizadas que apareceram nessa sessão, não o catálogo completo de permissões ou rotas da aplicação.

## Observações reproduzidas

| Área | Observação na versão V.5.0.230 | Encaminhamento de verificação |
|---|---|---|
| Sidebar recolhida | Ao clicar em um ícone, a barra lateral antiga se expande inteira. A interação não corresponde ao trilho contextual solicitado. | Implantar em homologação o código mesclado da PR #290 e repetir a inspeção: trilho de 80 px, submenu junto ao ícone, foco e fechamento por Escape, sem perder entradas autorizadas. |
| Menu superior | O menu renderizado não atende à organização e à interação visual solicitadas. | Após implantar o código mesclado da PR #290, conferir a mesma árvore de navegação autorizada na faixa superior, inclusive rolagem, abertura de submenus e comportamento em telas pequenas. |
| Modal de edição de cliente | A ação de editar abriu um SweetAlert clássico com apresentação azul e laranja, destoando do padrão visual aprovado. | Atualizar a aparência do diálogo preservando campos, ações e validação; verificar a ação novamente em homologação após publicação. |
| Estúdio de temas | O controle **Tema** do cabeçalho aponta para `/ui/temas`, mas o navegador usado na inspeção bloqueou a navegação. | Inspecionar a página após acesso viável e conferir migrations, presets publicados, liberação da empresa e preferência do usuário. Os temas personalizados existentes no banco **não puderam ser verificados** nesta sessão. |
| Chat de atendimento | `/atendimentoWeb` apresentou uma integração de chat com autenticação própria. | Preservar a integração e sua transição de autenticação; não substituir por novo botão ou painel inventado. |

## Destinos com resultado diferente de carregamento normal

| Destino | Resultado observado | Limite da conclusão |
|---|---|---|
| `/ponto/fechamentos` | `net::ERR_BLOCKED_BY_CLIENT` | Bloqueio pelo navegador usado; não comprova falha do servidor ou da rota. |
| `/estoque/ajustes` | `net::ERR_BLOCKED_BY_CLIENT` | Mesmo limite. |
| `/traccar/list` | `net::ERR_BLOCKED_BY_CLIENT` | Mesmo limite. |
| `/traccar/monitor` | `net::ERR_BLOCKED_BY_CLIENT` | Mesmo limite. |
| `/gestao-estoque` | `net::ERR_BLOCKED_BY_CLIENT` | Mesmo limite. |
| `/financeiro/conciliacao` | `502 Bad Gateway` | Erro recebido na visita; requer diagnóstico de infraestrutura/aplicação, fora da refatoração visual. |
| `/fiscal-classificacoes/list` | Redirecionou para `/graficos`. | Pode depender de autorização ou condição de sessão; não alterar a rota sem apuração. |
| `/traccar/mapa` | Redirecionou para `/graficos`. | Mesmo limite. |
| `/controleCozinha/selecionar` | Avançou para `/controleCozinha/controle`. | Fluxo observado; preservar a transição. |

## Pendências para aceitar a interface implantada

1. Implantar em uma instância de teste o merge da PR #290 e, quando estiver pronta, a PR de acompanhamento desta auditoria. Repetir a comparação visual com a proposta HTML aprovada, verificando a sidebar recolhida, o menu superior e a equivalência das entradas autorizadas no mesmo perfil.
2. Abrir `/ui/temas` em navegador que permita a navegação e comparar os três presets de fábrica, suas variantes e o catálogo personalizado realmente publicado e liberado à empresa. Não inferir ausência de tema personalizado apenas pela lista exibida.
3. Exercitar em contexto de teste os formulários e modais de amostra, inclusive a edição de cliente, sem enviar operações fiscais ou financeiras reais. Conferir campos, validações, ações e retorno à página anterior.
4. Investigar separadamente o `502` de conciliação, os bloqueios do cliente e os redirecionamentos, antes de atribuí-los à revisão de UI. Nenhuma mudança de backend ou regra de negócio foi feita nesta auditoria.

Os testes automatizados e a justificativa arquitetural estão em [refatoracao-visual-fersoft.md](refatoracao-visual-fersoft.md); o catálogo de presets do código está em [tema-refinado.md](tema-refinado.md). A inspeção autenticada complementa esses registros, sem comprovar que toda função de cada uma das 223 páginas foi executada.
