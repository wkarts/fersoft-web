# FERSOFT WEB — Marco 1: fundação funcional da nova interface

**Estado:** implementação em desenvolvimento e homologação pela PR #275, dirigida somente à `develop`.

> Esta etapa não aprova o redesenho de todas as telas do ERP. A PR #274
> permanece draft como caminho de promoção futura da `develop` para `main`.

## Ponto de partida obrigatório: a experiência real

O usuário aprovou um redesenho progressivo a partir dos prints reais do ERP instalado.
Não reimaginar a aplicação como um dashboard genérico nem inventar indicadores,
resultados financeiros ou gráficos.

A nova interface precisa conservar:

- Navegação lateral densa, hierarquia dos módulos, permissões e submenus reais.
- Menu superior operacional com empresa, ambiente, contrato, WhatsApp, filial,
  PDV, usuário, logoff, alertas, versão instalada e ações já funcionais.
- Último acesso, IP, preferências existentes e indicadores operacionais.
- Grids de edição, modais, Select2, DataTables, formulários, tooltips, wizards,
  rotinas fiscais, emissão, PDV, relatórios, impressões e scripts já publicados.
- Barra de aplicativo/PWA como contexto de janela; não simular que é HTML do ERP.
- Suporte acessível sem ocupar ou encobrir a lista de módulos.

## Implementação deste marco

1. `APP_UI_THEME=classic` permanece intocada, sem CSS/JS adicional.
2. `APP_UI_THEME=refined` habilita tokens e shell compartilhado nos dois layouts
   existentes. Menus, links, eventos, plugins e IDs do legado continuam.
3. Busca de módulos por **Ctrl+K** usando os mesmos dados já autorizados do
   `App\Helpers\Menu::preparaMenu()`. Não duplica regras de acesso.
4. Três apresentações disponíveis imediatamente: `fer-light`, `fer-slate`
   (sidebar escura) e `fer-night` (noturno completo).
5. Catálogo exclusivo do SUPERADMIN: criar/editar rascunhos, publicar, duplicar
   e arquivar. Personalizações são declarativas (apenas tokens RGB hex válidos);
   contraste mínimo para texto e superfícies principais.
6. Liberação por empresa, padrão por empresa e preferência por
   **(company_id, user_id)**, preservando escolhas ao mudar de empresa.
7. Busca de empresas em servidor com limite de resultados e autorização no
   controller, sem carregar toda a base.
8. Três migrations **individuais e aditivas**, sem DROP nem rollback destrutivo.

## Contrato de isolamento

### Em produção (padrão atual)

```dotenv
APP_UI_THEME=classic
```

Tudo opera com a apresentação antiga. Nenhuma migration é exigida
para continuar no modo clássico.

### Em homologação visual (somente develop)

```dotenv
APP_UI_THEME=refined
```

Depois da integração da PR à develop, executar migrations aditivas
em banco de homologação, confirmar integridade dos dados e limpar
o cache de configuração quando necessário:

```bash
php artisan migrate --force
php artisan config:cache
php artisan view:clear
```

A troca por ambiente controla apenas a arquitetura do tema. O usuário
escolhe sua preferência no painel `/ui/temas` (somente quando o modo
refinado estiver habilitado). O SUPERADMIN administra os modelos nessa
mesma área; os demais usuários veem apenas seleção.

Para retorno imediato ao visual original:

```dotenv
APP_UI_THEME=classic
```

Aplicar `php artisan config:cache` no ambiente com configurações cacheadas.
Esse rollback de apresentação **não apaga dados nem reverte tabelas**.

## Arquitetura e código

| Elemento | Arquivos principais |
|---|---|
| Seleção e presets | `config/ui_theme.php` |
| Resolução por empresa/usuário | `app/Services/UiThemeService.php` |
| Publicação e escolha | `app/Http/Controllers/UiThemeController.php` |
| Persistência | `database/migrations/2026_10_14_23010*.php` |
| Shell progressivo | `resources/views/themes/refined/workbench.blade.php` |
| CSS shell e componentes | `public/css/ui-operational.css`, `public/css/fer-design-system.css` |
| Busca de módulos | `public/js/theme-refined-workbench.js` |
| Administração | `resources/views/themes/refined/studio/index.blade.php` |
| JS busca de empresa | `public/js/theme-refined-studio.js` |
| Testes | `tests/Feature/UiThemeCatalogTest.php` |

A utilização ainda transitória do Metronic é deliberada: a integração não
pode desativar componentes operacionais antes da migração e validação
funcional de cada módulo. O novo shell já cria um ponto de evolução
independente para a navegação e o catálogo de temas.

## Critérios antes de integrar PR #275 à develop

- Validar PHP lint, JS `node --check`, Blade compile, PHPUnit, build Vite,
  auditoria visual e testes de isolamento do tema clássico.
- Confirmar as rotas de catálogos e os controllers correspondentes, sem
  remover/mover grupos do `routes/web.php` histórico.
- Verificar que não existem comandos de `DROP` nas migrations.
- Na homologação autenticada: testar temas claro, sidebar escura e noturno;
  menu lateral e superior; permissões comuns e SUPERADMIN; busca; empresa
  selecionada; edição do rascunho; publicação; liberação à empresa.
- Reabrir dashboard e validar os valores REAIS, sem criar dados mockados.
- Registrar qualquer diferença de scroll, topbar, sticky header, modal
  empilhado, menu minimizado, mobile e PWA.

## Etapas seguintes (na mesma linha develop)

- Extrair shell verdadeiramente independente dos componentes de layout
  Metronic, mantendo adaptadores para módulos antigos não migrados.
- Migrar sistematicamente cards/grids/formulários/modais e componentes
  de filtros, com testes reais e sem modificar regras de negócio.
- Realizar QA de PDV, estoque, fiscal, financeiro, contratos, MTR, relatórios,
  impressões, alertas e fluxos AJAX de salvar/editar.
- Só após 100% da homologação e aprovação explícita, promover
  `develop → main` via PR #274, jamais automaticamente.

**Releases:** todo commit na `develop` desencadeia o fluxo de pré-release
beta contínua; a `main` continua com releases estáveis.
