# FERSOFT — refatoração visual baseada na proposta aprovada

## Referência e decisão de arquitetura

A referência de aceite é `FERSOFT-PREVIA-FIEL-ANTES-DEPOIS (1)(2).html`, fornecida para esta intervenção. O lado “depois” define o canvas `#F5F7F7`, a sidebar branca de 264 px, o cabeçalho de 74 px, a faixa de sessão de 42 px, as superfícies brancas, a tipografia Poppins, os detalhes verdes `#1F9866` e a hierarquia de cartões e painéis. A captura ilustrativa mostra um rodapé de suporte; a instrução posterior do proprietário o exclui expressamente. A integração Chatwoot que já existia continua acessível pelo launcher original.

O projeto é Laravel 11, PHP 8.2, Blade e Vite 4. São 1.119 arquivos Blade; aproximadamente 668 views operacionais usam `default.layout`, que seleciona `themes.refined.layout` quando `APP_UI_THEME=refined`. As rotas em `routes/web.php` e `routes/api.php`, autenticação e permissões da sessão, campos, eventos e submissões são server side. Reescrever tudo em Vue 3 + TypeScript exigiria reconstruir esses contratos, sem necessidade para atingir a referência HTML. Esta entrega permanece em Blade, com estilos de componentes próprios e scripts independentes para os widgets necessários.

## Sistema visual

- `public/css/ui-operational.css` mantém as proporções e a disposição do shell, dashboard, sidebar, menus, cabeçalho e responsividade.
- `public/css/fer-design-system.css` contém os padrões comuns de cartões, tipografia, formulários, botões, filtros, tabelas, estados, abas, wizard e modais, escopados em `body.ui-native-shell`.
- `public/css/fer-modules.css`, `ui-cte.css`, `ui-fiscal.css`, `ui-operations.css`, `ui-people.css`, `ui-pos.css`, `ui-admin.css` e demais CSS por módulo tratam as estruturas específicas das views. As views mantêm a estrutura funcional, rotas e campos e acrescentam classes de apresentação.
- `themes.refined.layout` carrega Bootstrap 4 como grade/modal, CSS local dos widgets e o sistema visual FERSOFT. Não carrega `/metronic/css/*`, `/metronic/js/*` nem `ui-legacy-widgets.js` no modo refinado. `public/js/fer-runtime.js` implementa a interação da interface e as pequenas APIs de compatibilidade `KTWizard`, `KTImageInput`, `KTSelect2` e `KTDatatable` exigidas pelas views existentes; as bibliotecas independentes têm versões e licenças em `public/js/vendor/README.md`.
- Os arquivos `scripts/ci/build-native-ui.py`, `public/css/theme-refined.css`, `public/css/theme-refined-shell.css` e `public/js/ui-legacy-widgets.js` eram um gerador/cópia da apresentação antiga. Foram removidos porque não são consumidos pelo tema refinado e o gerador reintroduzia o shell antigo durante o empacotamento.

O modo clássico permanece como rollback visual explícito para instâncias que usam `APP_UI_THEME=classic`; seus arquivos não são carregados no tema refinado. Os quatro layouts independentes do PDV selecionam os novos assets ao usar o tema refinado. Impressão, PDF, loja pública, recibos térmicos e e-commerce público preservam seus layouts e formatos específicos; a proposta não define uma aparência para eles.

## Inventário funcional e preservação

- O menu lateral contém 41 grupos/submenus e 58 links estáticos detectáveis; o menu superior contém 12 grupos/submenus e 36 links. As condições Blade de visibilidade e autorização permanecem no template.
- A aplicação tem 917 formulários em 594 templates e 1.056 tabelas em 524 templates. A busca textual encontrou 6.561 ocorrências de markup de modal em 182 templates; essas ocorrências não equivalem a 6.561 diálogos únicos.
- A sessão `user_logged`, o usuário, a empresa/unidade selecionada, permissões, lançamento fiscal e financeiro, integrações, ações do PDV, notificações e Chatwoot continuam usando os contratos existentes. Nenhuma rota, API, migration, tabela ou regra de negócio foi alterada nesta revisão visual.
- Cada view operacional editada é comparada ao ZIP original por `tests/ui/test-view-contracts.py`, com snapshot de IDs, nomes de campos, métodos, ações, atributos de abertura de modal, scripts e estrutura funcional. O teste cobre os arquivos realmente editados, além dos testes PHP e do navegador previstos no workflow.

## Validação

O workflow `.github/workflows/ui-native-browser.yml`, acionado por PR para `develop`, faz `php artisan view:cache`, lint do PHP compilado de todas as Blades, PHPUnit para a paleta/runtime, renderização de fixtures com o Blade real e testes de navegador em 1920×1032, 1366×768, 768×1024 e 390×844. Inclui duas views operacionais reais de Funcionários e verifica navegação, permissões, modais, Select2, campos e overflow. Os scripts de contratos e sintaxe JavaScript também são executados.

O ambiente de edição sem PHP/Composer não comprova compilação Blade nem funcionamento com sessão e dados reais. A rodada de CI e homologação autenticada são necessárias antes de aceitar a revisão; os resultados devem ser registrados na PR. Não se deve considerar o dashboard ou o teste de fixtures uma prova isolada de cobertura total das 1.119 views.

## Publicação e reversão

Base e destino: `develop`. Branch de trabalho: `feat/ui-operational-redesign-20261015`. Não há migração de banco. Para reverter a interface enquanto se investiga uma regressão, usar `APP_UI_THEME=classic`, limpar cache de configuração/views e repetir os testes operacionais; a reversão definitiva é a reversão do commit visual após avaliação da PR. Não alterar `main` como parte dessa entrega.
