# Inventário estático da interface

Gerado por `python3 scripts/audit_ui_views.py` sobre a árvore da branch de redesenho. O catálogo detalhado está em [`auditoria-views-2026-10-16.json.gz`](auditoria-views-2026-10-16.json.gz), compactado com gzip: **cada arquivo Blade** tem caminho, expressão de layout, formulários (método, ação, ID), nomes de campos, modais (ID e classe), links locais, includes, contagem de estilos inline, linhas com dimensões inline e padrões de componentes. O JSON também lista as declarações literais de `routes/web.php` e entradas literais de `app/Helpers/Menu.php` com suas linhas. Para ler: `gzip -dc docs/auditoria-views-2026-10-16.json.gz`. Para regenerar o inventário bruto: `python3 scripts/audit_ui_views.py`.

| Cobertura do código-fonte | Contagem |
| --- | ---: |
| Views Blade encontradas | 1.125 |
| Views próprias excluindo `resources/views/vendor` | 1.039 |
| Views com `<form>` / elementos `<form>` | 598 / 929 |
| Views com marcador `.modal` / elementos `.modal` | 153 / 510 |
| Views com qualquer estilo inline | 834 |
| Views com largura/altura inline | 630 |
| Views que contêm `ui-admin` / `fer-page` | 152 / 185 |
| Declarações literais de rotas em `routes/web.php` | 2.668 |
| Links literais do helper de menu / grupos encontrados | 238 / 41 |

Entre as **1.039 views próprias**, 671 estendem diretamente `default.layout` e usam o shell refinado quando `ui_theme.active` está ativo (`resources/views/default/layout.blade.php:105`); 221 são parciais ou páginas independentes sem `@extends` detectável; 50 são relatórios; as demais estão em layouts específicos de delivery, ecommerce, login e outros módulos. Há **568 views próprias com formulários**, sendo 493 sob `default.layout`; 152 views próprias com modal, sendo 130 sob esse layout. Existem 8.510 ocorrências de `style=` nas views próprias e 397 views próprias com formulário e alguma dimensão inline. Essas contagens são alcance estático, não evidência de qualidade visual nem de teste interativo.

O JSON marca arquivos para revisão manual com flags heurísticas (incluindo views de `vendor`): 403 com formulário e dimensão inline, 104 com formulário/modal sob `.ui-admin` que pede revisão de variante de tema, 66 com formulário/modal em outro layout explícito, e 27 com cinco ou mais blocos de modal. Uma flag indica prioridade de inspeção, **não um defeito confirmado**.

## Pontos concretos encontrados no snapshot inicial

As linhas e sintomas abaixo registram a inspeção anterior às correções desta PR. A posição atual dos trechos pode diferir.

1. **Sub-topbar não acompanha rolagem.** `resources/views/themes/refined/layout.blade.php:107-153` põe `#ui-session` como irmão posterior de `.ui-masthead`. `public/css/ui-operational.css:42` torna sticky apenas o masthead; `.ui-session` em `:58` continua no fluxo. No modo de menu superior, `:156` muda o masthead para `position:relative`; em telas pequenas `:166` faz o mesmo. Para fixar as duas faixas em conjunto, cuidar da altura variável do cabeçalho e do deslocamento do conteúdo.
2. **Unidade sobe em relação aos demais controles.** O pseudo-elemento com a palavra `Unidade` em `public/css/ui-operational.css:278-280` ocupa uma linha antes do select, que fica limitado a 220px. `resources/views/themes/refined/partials/masthead.blade.php:126-133` acrescenta `mt-2` e `min-width:240px` inline; em `ui-operational.css:334-345` o select ainda é limitado a 185/175px em telas menores. A captura de 16/10 às 09:57 mostra o desnível.
3. **Controle da sidebar não está sobre a divisória.** `resources/views/themes/refined/partials/sidebar.blade.php:3-18` mantém `[data-ui-collapse]` dentro de `.ui-brand`; `public/css/ui-operational.css:368-372,410-415` define aparência e posição internas à área da marca. Reposicionar preservando o mesmo botão, atributos e acionamento em `public/js/ui-operational.js:151-154`.
4. **Seletores móveis do cabeçalho não correspondem ao HTML.** A lista é `<ul class="menu-nav">` contendo diretamente `<li>` (`masthead.blade.php:21-50`), mas `ui-operational.css:325-326` seleciona `.menu-nav > .menu-nav` e `.menu-nav > .menu-nav > .menu-item`. Tais regras não atingem a lista operacional; o fluxo móvel depende de outras regras e inline classes.
5. **Lista de usuários mostra distribuição desigual.** `resources/views/usuarios/list.blade.php:13-38` preserva wrappers de grid/animação acima do título e abas; a coluna `col-lg-4` em `:17` está fora de `.row`. A tabela usa larguras inline `:44-57`; os spans de linha repetem `id="id"` `:63-104`. Os IDs duplicados tornam seletores e interações ambíguos, embora a correção precise verificar qualquer JS que dependa deles. Há também referência a `$veiculos->links()` em `:204-209` quando `isset($links)`, variável de outro módulo e risco de falha se esse ramo for usado.
6. **Formulário de produtos continha forms aninhados no arquivo auditado.** Em `resources/views/produtos/register.blade.php:26` abria um `<form>` de persistência, e em `:88` outro `<form id="kt_form">` no interior. HTML não aceita formulários aninhados; o navegador pode reinterpretar fechamento/submissão. A correção desta branch conserva `kt_form`, ação, método, CSRF e campos nos três cadastros de produto; o teste de contrato verifica um só formulário por arquivo.
7. **CSS da administração fixa cores fora da paleta de temas.** `public/css/ui-admin.css:2-16,24-31,74-80,85-96,125-132` usa cores hexadecimais e fundos brancos, enquanto os temas do shell usam `var(--ui-*)`. Nos formulários/views com `.ui-admin`, a variante escura ou presets customizados podem ter superfícies destoantes. A regra `.ui-admin .row > [class*="col-"]` em `:52` força largura total até os breakpoints seletivos em `:169-203`; grid de coluna sem classe coberta pelo seletor específico pode cair em 100%.
8. **Muitas áreas não passam pelo shell comum.** PDV (`frontBox/main.blade.php`, `main2.blade.php`, `main3.blade.php`, `pdv_assincrono.blade.php`) tem entre 21 e 32 marcadores de modal por arquivo no inventário; relatórios, ecommerce e delivery têm layouts próprios. Ajustes em `.ui-page` não são validação desses diálogos e páginas. Exemplo pontual: `resources/views/contaReceber/list.blade.php:383` fixa título com `width:230px;height:10px`; `resources/views/vendas/register.blade.php:631` fixa select em `width:80%`.

Nesta PR, a sub-topbar e o controle da sidebar receberam posicionamento responsivo; o seletor de Unidade foi alinhado; a lista e o formulário de usuários, os cadastros de produtos, a pesagem, CT-e, Remessa NF-e e Vendas receberam correções pontuais. `ui-form-harmony.css` e `ui-dialog-navigation.css` alcançam os padrões compartilhados do shell. A contagem integral do catálogo continua sendo um inventário de código, sem comprovar que cada combinação de rota, permissão e dados tenha sido renderizada.

## Navegação, rotas e contratos

O menu funcional é produzido por `App\Helpers\Menu::preparaMenu()` (`app/Helpers/Menu.php:1869-1941`). O método distingue contador, superusuário, módulos habilitados e permissões da empresa/usuário por `Empresa::validaLink`. `resources/views/themes/refined/partials/sidebar.blade.php:459-504` usa o mesmo helper, inclusive `rota_ativa`, para renderizar cada submenu. O catálogo JSON traz os 41 grupos e os 238 caminhos literais encontrados no helper; o menu SUPER adicional está na própria sidebar (`:46-457`). Esses caminhos **não são um mapa completo de acesso**: há rotas dinâmicas, grupos com prefixo, controllers, links externos, Blade condicional e ações em JavaScript. Uma comparação textual de paths com declarações em `routes/web.php` produziria falsos positivos. A validação final de cada menu exige sessão e permissões reais, resolvendo a rota na aplicação e exercitando abertura/retorno sem gravar dados.

## Inspeção autenticada da versão publicada

O navegador autenticado de homologação abriu `/graficos`, `/usuarios`, `/usuarios/new`, `/produtos` e `/produtos/new`. Foram conferidas a árvore de grupos da sidebar, as ações de listagem, campos do cadastro de usuário e produto e a abertura e o fechamento de `#modalWhatsAppGlobal`, sem enviar formulários nem alterar dados. Em `/usuarios/new`, a foto com seletor nativo e o texto de ajuda da balança estavam comprimidos na grade de quatro colunas. Em `/usuarios`, o cartão excedia a altura necessária para duas linhas. Essas observações descrevem a versão publicada `b1d10e6e`, antes da implantação das correções desta PR.

## Limite da auditoria

O plugin do navegador local não estava disponível; a amostra acima foi aberta no navegador autenticado da sessão. O JSON inventaria **todos os arquivos**, mas as demais rotas e modais não foram renderizados individualmente. A revisão de responsividade da branch usa fixture controlada, e não dados reais de cada empresa/permissão. Após implantação, é necessário conferir visualmente os módulos com os perfis e dados correspondentes em desktop, notebook, tablet e celular.
