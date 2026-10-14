# Tema refinado do ERP — levantamento e implementação opt-in

## Objetivo

Introduzir um tema visual profissional, coerente com o login `access_4`, sem alterar regras de negócio, rotas, controllers, permissões, estrutura Blade ou comportamento de componentes JavaScript.

A alteração no `.env` é única:

```dotenv
APP_UI_THEME=classic  # padrão legado, inalterado
APP_UI_THEME=refined  # novo tema do ERP autenticado
```

O seletor é independente de `PAG_LOGIN`. Valores ausentes ou desconhecidos voltam para `classic`; `config:cache` é suportado. No deploy com cache de configuração, execute `php artisan config:cache` após modificar o `.env`.

## Levantamento efetivo no repositório (main, 14/10/2026)

| Superfície | Arquivos/padrões observados | Risco e decisão |
|---|---|---|
| Layout de negócio | `resources/views/default/layout.blade.php` (612 linhas) | Mantido integralmente; contém alertas e monitoramento de pedidos, inclusive modais prioritários |
| Menu lateral | `resources/views/default/menu_lateral.blade.php` (~2.800 linhas) | Preservados `#kt_aside`, `#kt_aside_toggle`, árvore de menu e permissões; apenas classe no body, link condicional de CSS e regra de dark legado |
| Menu superior | `resources/views/default/menu_superior.blade.php` (~2.144 linhas) | Preservados `#kt_header`, menus dinâmicos, notificações, filtros e ações |
| Tipografia do login | `LoginPresentationService::fontFiles()`, `access_4.blade.php` | Reaproveitados os arquivos locais Poppins/Montserrat e a seleção publicada; fallback Poppins |
| CSS global legado | `public/metronic/css/*`, `public/css/extend.css`, `public/css/style.css`, `public/css/escuro.css` | Tema novo restrito a `body.ui-theme-refined`; prioridade sobre cores `!important` somente nesse modo |
| Listas e grids | `produtos/list.blade.php`, `clientes/list.blade.php`, `contaPagar/list.blade.php` | Metronic Datatable, células com tamanhos e alturas fixos; não alterar altura, display, posicionamento ou scrolling |
| Cadastros | `produtos/register.blade.php` e formulários de clientes/financeiro | Wizards, validações, Select2 e formulários aninhados mantidos; alterar só pele |
| Modal de fluxos fiscais | `mtr/emissao/index.blade.php` | Preservados IDs, `data-toggle`, `data-target`, ordem de eventos, select e z-index |
| Novas funcionalidades | `contratos/list.blade.php`, `dashboard_novo/index.blade.php` | Cards e tabelas coexistem com classes e estilos inline específicos; não forçar tema sobre cores semânticas |
| Login público e aplicativos públicos | `login/*`, `delivery/*`, `ecommerce/*` | Fora do escopo do seletor do ERP; não devem receber `ui-theme-refined` |
| Modo escuro histórico | variável `$tema`, `/css/escuro.css` | `classic`: idêntico ao atual. `refined`: variante `ui-theme-refined--dark` sem importar `escuro.css` global |

## Arquitetura

1. `config/ui_theme.php`: normaliza o valor do `.env` e expõe `active`.
2. Layouts `default/menu_lateral` e `default/menu_superior`: adicionam classe `ui-theme-refined` **somente** com `active=true` e carregam `themes.refined.assets` ao final do `head`.
3. `resources/views/themes/refined/assets.blade.php`: seleciona a mesma família tipográfica publicada no login `access_4`, com fallback local e nenhuma dependência de front-end nova.
4. `public/css/theme-refined.css`: tokens (claro e escuro), sidebar, topbar, cards, filtros, formulários, botões, select, tabelas, modais, toast, dropdown, wizard, breakpoints e foco.
5. Não foi adicionado JavaScript: controles existentes continuam sob responsabilidade do Metronic, Bootstrap, jQuery e código de cada rotina.

## Regras de compatibilidade

- **Nenhuma alteração** nos nomes e nos parâmetros de rotas, controllers, models, queries, migrations, sessão, RBAC ou permissões de menu.
- O CSS legado continua sendo carregado; no modo `classic` não é injetado nenhum CSS do novo tema.
- O refinamento **não** altera `display`, `overflow`, `position`, `z-index` ou largura/altura de datatables e modais. Essas propriedades são controladas pelo código em produção.
- Recursos do WhatsApp, alertas críticos, diálogos bloqueantes, impressão, exportação e PWA continuam independentes do tema.
- O tema novo tem modo claro como padrão e acompanha `$tema=2` com uma variante escura própria, sem importar `escuro.css`, cujas regras genéricas vazam para todos os componentes.

## Matriz de homologação necessária antes de habilitar globalmente

| Área | Cenários |
|---|---|
| Shell desktop | menu lateral expandido/compacto, menu superior, SUPER, empresa/filial, relógio, notificações, dropdowns |
| Shell mobile | 320, 375, 768, 1024 px; ambos os layouts, teclado aberto, abrir/fechar menus, sem rolagem horizontal indevida |
| Produtos | cadastro e edição, grid após salvar, wizard, Select2, filtros, grade, modais e atualização Ajax |
| Clientes | listagem, cadastro, filtros, ações e paginação |
| Financeiro | contas a pagar/receber, conciliação, validações, datas, campos monetários e modais |
| MTR | emissão, importar NF-e/pesagem, cancelar, WhatsApp, status, modais e notificações |
| Engenharia | contratos, medições, botões e dropdowns em modais |
| Dashboard | KPIs, gráficos, cores de status e cartões responsivos |
| Sistema | tarefas obrigatórias, pedidos delivery, SweetAlert, Toastr, carregamento, sobreposição e backdrop |
| Regressão | tema clássico claro/escuro, tema refinado claro/escuro, `PAG_LOGIN` antigo e `access_4` em combinações |

### Critério de conclusão

O tema pode ser habilitado globalmente apenas após testes reais de navegação, acessibilidade, screenshot e fluxos funcionais nos principais módulos em ambiente de homologação. Uma revisão estática de CSS/Blade **não equivale** à homologação visual/funcional integral de todas as views.

### Rollback

1. Alterar `APP_UI_THEME=classic` no `.env`.
2. Atualizar o cache de configuração, se estiver em uso: `php artisan config:cache`.
3. Recarregar as páginas. Nenhuma migração de banco, modificação de sessão ou reversão de código é necessária.
