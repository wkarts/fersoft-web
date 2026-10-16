# Temas da interface FERSOFT

## Referência e ativação

O HTML aprovado `FERSOFT-PREVIA-FIEL-ANTES-DEPOIS (1)(2).html` define o visual da interface autenticada. O projeto usa Laravel/Blade; `APP_UI_THEME=refined` é o padrão em `config/ui_theme.php`. `APP_UI_THEME=classic` mantém o layout anterior como opção de reversão. Depois de alterar o `.env` em uma instalação com cache, atualize o cache de configuração e de views.

O tema refinado usa CSS e JavaScript próprios em `resources/views/themes/refined/layout.blade.php`. O layout refinado não importa os bundles `/metronic/css/*` nem `/metronic/js/*`. As APIs de widgets ainda consumidas por views operacionais são atendidas por `public/js/fer-runtime.js` e bibliotecas locais, sem reescrever os contratos de formulários, rotas ou permissões. Alguns layouts especializados de impressão e conteúdo público mantêm sua apresentação apropriada; o modo clássico segue disponível para reversão.

## Catálogo e variantes da proposta

| Escolha | Implementação | Resultado visual |
|---|---|---|
| FERSOFT Claro (`fer-light`) | Preset em `config/ui_theme.php` | Canvas `#F5F7F7`, sidebar branca, acento verde `#1F9866` |
| FERSOFT Slate (`fer-slate`) | Preset em `config/ui_theme.php` | Página clara, sidebar `#1D2639` |
| FERSOFT Noturno (`fer-night`) | Preset em `config/ui_theme.php` | Canvas `#11252C`, superfície `#1C333B`, sidebar `#172D35`, acento `#62D3AB` |
| Claro/Escuro | `color_mode` da preferência | Adapta a paleta completa do preset oficial; no modo escuro, a proposta usa a mesma sidebar noturna para ambas as escolhas do controle de sidebar |
| Sidebar Clara/Escura | Campo `sidebar_mode` nos presets claros, convertido em `fer-light` ou `fer-slate` | Escolha independente para a página clara, sem coluna nova |
| Confortável/Compacta | `density` | Espaçamento dos componentes e menu |
| Lateral/Superior | `menu_mode` | Árvore real de menus e permissões na lateral ou na faixa horizontal de 47 px abaixo do cabeçalho |

Essas escolhas resultam em 16 combinações persistíveis dos quatro controles binários da proposta. O menu lateral recolhido ocupa 80 px e abre o submenu real ao lado do trilho; o modo superior usa a mesma árvore autorizada, com rolagem horizontal e flyouts. A preferência de recolhimento é local ao navegador e identificada por usuário. Não foi acrescentado suporte no rodapé da sidebar.

Temas personalizados dependem de registros publicados em `ui_theme_presets` e de liberação à empresa em `ui_theme_company_settings`. Usuários escolhem entre os temas autorizados; a preferência fica em `ui_theme_user_preferences` por usuário e empresa. Portanto, a ausência de um tema personalizado na tela não prova que o preset visual de fábrica esteja ausente; pode significar que ele não foi publicado ou liberado naquele contexto.

## Operação e validação

As tabelas do Studio (`ui_theme_presets`, `ui_theme_company_settings`, `ui_theme_user_preferences`) e a coluna `menu_mode` são pré-requisitos de migrations já existentes no projeto. Esta correção de navegação e paletas **não cria migration**. Se `/ui/temas` indicar indisponibilidade do catálogo, confira o estado dessas migrations no ambiente antes de atribuir o problema ao CSS.

`tests/Feature/UiPreferencesIntegrationTest.php` cobre persistência, isolamento por empresa/usuário, permissões do catálogo e as 16 combinações da proposta. `tests/ui/native-browser.py` verifica o menu autorizado em modos e resoluções diferentes, incluindo flyouts, foco e retorno do layout móvel. `tests/ui/test-view-contracts.py` confere os atributos funcionais das views modificadas contra a versão original de `develop`. A CI compila Blade e executa esses testes; o resultado de cada execução deve ser confirmado na PR.

As fixtures de CI não substituem a navegação autenticada com dados, empresas e permissões de homologação. Antes do merge, confira na instância de homologação os menus acessíveis no perfil real, alternância do tema, formulários, modais, filtros e integrações relevantes. Para reversão visual, use `APP_UI_THEME=classic`, limpe os caches de configuração/views e recarregue a aplicação.
