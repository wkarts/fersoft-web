# Temas da interface

## Referência e ativação

O HTML aprovado `FERSOFT-PREVIA-FIEL-ANTES-DEPOIS (1)(2).html` define o visual da interface autenticada. O projeto usa Laravel/Blade; `APP_UI_THEME=refined` é o padrão em `config/ui_theme.php`. `APP_UI_THEME=classic` mantém o layout anterior como opção de reversão. Depois de alterar o `.env` em uma instalação com cache, atualize o cache de configuração e de views.

O tema refinado usa CSS e JavaScript próprios em `resources/views/themes/refined/layout.blade.php`. O layout refinado não importa os bundles `/metronic/css/*` nem `/metronic/js/*`. As APIs de widgets ainda consumidas por views operacionais são atendidas por `public/js/fer-runtime.js` e bibliotecas locais, sem reescrever os contratos de formulários, rotas ou permissões. Alguns layouts especializados de impressão e conteúdo público mantêm sua apresentação apropriada; o modo clássico segue disponível para reversão.

## Catálogo e variantes da proposta

| Escolha | Implementação | Resultado visual |
|---|---|---|
| Claro (`fer-light`) | Preset em `config/ui_theme.php` | Canvas `#F5F7F7`, sidebar branca, acento verde `#1F9866` |
| Claro com menu escuro (`fer-slate`) | Preset em `config/ui_theme.php` | Página clara, sidebar `#1D2639` |
| Noturno (`fer-night`) | Preset em `config/ui_theme.php` | Canvas `#11252C`, superfície `#1C333B`, sidebar `#172D35`, acento `#62D3AB` |
| Verde suave (`fer-mist`) | Preset em `config/ui_theme.php` | Canvas `#F2F7F4`, menu verde claro, acento verde `#167D5B` |
| Grafite (`fer-graphite`) | Preset em `config/ui_theme.php` | Canvas `#171C25`, menu grafite, acento verde `#75D9B6` |
| Claro/Escuro | `color_mode` da preferência | Adapta a paleta completa do preset oficial; no modo escuro, a proposta usa a mesma sidebar noturna para ambas as escolhas do controle de sidebar |
| Sidebar Clara/Escura | Campo `sidebar_mode` nos presets claros, convertido em `fer-light` ou `fer-slate` | Escolha independente para a página clara, sem coluna nova |
| Confortável/Compacta | `density` | Espaçamento dos componentes e menu |
| Lateral/Superior | `menu_mode` | Árvore real de menus e permissões na lateral ou na faixa horizontal de 47 px abaixo do cabeçalho |

Essas escolhas resultam em 16 combinações persistíveis dos quatro controles binários da proposta. O menu lateral recolhido ocupa 80 px e abre o submenu real ao lado do trilho; o modo superior usa a mesma árvore autorizada, com rolagem horizontal e flyouts. A preferência de recolhimento é local ao navegador e identificada por usuário. Não foi acrescentado suporte no rodapé da sidebar.

Temas personalizados dependem de registros publicados em `ui_theme_presets` e de liberação à empresa em `ui_theme_company_settings`. O superadministrador pode promovê-los a padrão geral, disponível a todas as empresas, ou voltar a restringi-los às liberações existentes. A edição de um preset oficial usa a mesma tabela com o slug original e status `standard`; o original em `config/ui_theme.php` permanece intacto e pode ser restaurado pela interface. Usuários escolhem entre os temas autorizados; a preferência fica em `ui_theme_user_preferences` por usuário e empresa.

O controle **Tema** do cabeçalho refinado abre o Studio `/ui/temas`, que oferece os cinco presets de fábrica e temas personalizados publicados e liberados à empresa. Os três slugs originais seguem intactos, e `fer-light` continua como padrão. O antigo modal `#modal-tema`, com as opções Indigo/Teal/Amber/Light, foi retirado somente do layout refinado porque não tinha gatilho nesse layout. A escolha legada continua no modo clássico para manter a reversão; modais operacionais, como `#modal-pesquisa`, permanecem disponíveis.

### Tema independente do PDV

As quatro telas operacionais independentes de `/frenteCaixa` (`main`, `main2`, `main3`, `pdv_assincrono`) usam `UiPosThemeService` e `themes.refined.pos-assets`. A paleta `pos-default` em `config/ui_pos_theme.php` reproduz a aparência clara atual do caixa, inclusive canvas `#F5F7F7`, superfície branca e acento `#1F9866`; ela permanece disponível sem as migrations adicionais. A escolha pessoal de tema, densidade e empresa do ERP não altera essa paleta. As telas auxiliares de listagem/configuração continuam no layout normal do ERP.

O superusuário administra `/config/temas/pdv`: escolhe um padrão único para os caixas, duplica os três modelos de fábrica (`Padrão do PDV`, `Verde suave`, `Noturno`), cria ou edita rascunhos com sete cores validadas por contraste, publica modelos, duplica publicados e arquiva modelos que não estão em uso. Modelos publicados e o padrão escolhido são gravados somente em `ui_pos_theme_presets` e `ui_pos_theme_settings`. A fábrica não é alterada no código ao editar uma cópia; o menu `Tema` do ERP não oferece preferências individuais do PDV. O modo `APP_UI_THEME=classic` continua usando o PDV clássico sem carregar a personalização.

`ui-themes:install` inclui as duas migrations aditivas `2026_10_16_090000` e `2026_10_16_090001` após as migrations da interface. O diagnóstico é somente leitura; `--apply` instala as tabelas próprias do PDV. Se elas ainda não estiverem presentes, o caixa renderiza `pos-default` e a área SUPER mostra os modelos de fábrica com gravação desabilitada. O rollback de código conserva os registros do catálogo. `tests/Feature/UiPosThemeTest.php` cobre o fallback, a independência da preferência do ERP, o acesso SUPER, contraste das paletas e a integração das quatro views.

## Operação e validação

As tabelas do Studio (`ui_theme_presets`, `ui_theme_company_settings`, `ui_theme_user_preferences`) e a coluna `menu_mode` são pré-requisitos das escolhas pessoais e do catálogo. A nova tabela aditiva `ui_theme_system_settings` guarda somente o padrão global; sua ausência desabilita apenas esse controle no SUPER e mantém os temas e preferências já instalados funcionando. Se `/ui/temas` indicar indisponibilidade do catálogo básico, confira o estado das migrations anteriores antes de atribuir o problema ao CSS.

### Instalação do catálogo em uma instalação existente

O botão **Tema** abre `/ui/temas` para a escolha pessoal. O grupo **SUPER** possui a área separada **Temas da Interface** em `/config/temas`: editar/restaurar modelos oficiais, criar e publicar temas, promover a padrão geral, liberar por empresa, definir o padrão global e padrões específicos de empresas. O controle do servidor exige a sessão de superadministrador em todas as gravações. O padrão global vale apenas se não houver padrão da empresa nem preferência do usuário. As cinco opções oficiais estão em `config/ui_theme.php`; ajustes oficiais, temas personalizados e preferências persistem nas tabelas acima. A apresentação dos presets sem banco não significa que a escolha esteja gravável.

No servidor da instalação, usando o mesmo código e `.env` do ERP, faça primeiro uma inspeção somente leitura:

```bash
php artisan ui-themes:install
```

O comando distingue as sete migrations do catálogo, incluindo `2026_10_16_050000` para o padrão global do ERP e as duas `2026_10_16_090000`/`090001` do PDV, seus registros na tabela `migrations`, as tabelas/coluna físicas e falhas de conexão. O registro `pending:[]` de `updater:migrate` **não comprova** que as migrations da aplicação foram executadas. Confirme banco/conexão e backup antes de aplicar mudanças em homologação ou produção.

Se houver migrations pendentes, execute o instalador restrito aos arquivos do catálogo:

```bash
php artisan ui-themes:install --apply
```

Se o diagnóstico mostrar uma migration registrada como executada, mas tabela ou coluna correspondente ausente, use `--repair-recorded` somente após conferir essa divergência e o backup. O reparo executa os `up()` idempotentes e aditivos desses arquivos; não apaga tema, preferência ou tabela legada:

```bash
php artisan ui-themes:install --apply --repair-recorded
```

Um esquema preexistente parcial ou sem os índices únicos esperados exige correção específica após inspeção dos dados; o instalador não cria índices em tabelas divergentes automaticamente. Repita o diagnóstico sem opções, limpe os caches de configuração/views se a implantação exigir e abra `/ui/temas` e `/config/temas` com perfis autorizados. Se o diagnóstico mostrar falha de conexão, corrija a conexão do ERP antes de tentar instalação; o comando não deve mascarar esse erro. O rollback do código preserva as tabelas e preferências do catálogo, pois os `down()` existentes são conservadores.

`tests/Feature/UiPreferencesIntegrationTest.php` cobre persistência, isolamento por empresa/usuário, permissões do catálogo e as 16 combinações da proposta. `tests/ui/native-browser.py` verifica o menu autorizado em modos e resoluções diferentes, incluindo flyouts, foco e retorno do layout móvel. `tests/ui/test-view-contracts.py` confere os atributos funcionais das views modificadas contra a versão original de `develop`. A CI compila Blade e executa esses testes; o resultado de cada execução deve ser confirmado na PR.

As fixtures de CI não substituem a navegação autenticada com dados, empresas e permissões de homologação. Antes do merge, confira na instância de homologação os menus acessíveis no perfil real, alternância do tema, formulários, modais, filtros e integrações relevantes. Para reversão visual, use `APP_UI_THEME=classic`, limpe os caches de configuração/views e recarregue a aplicação.
