# Proposta arquitetural — Catálogo de temas administrados pelo SUPERADMIN

> Status: **especificação funcional/arquitetural**. Ainda não é implementação ativa nem altera banco. A PR #268 permanece rascunho e não será mesclada na main.

## Princípios

- Um único ERP Laravel/Blade, um único roteamento e o mesmo código de negócio.
- Tema `classic` imutável como alternativa de segurança. O `refined` inaugura o novo sistema visual e seus derivados.
- **Somente SUPERADMIN** pode criar, editar, publicar, arquivar ou distribuir temas. A empresa e seus usuários podem **escolher somente** entre os temas publicados e autorizados.
- Cada versão de tema é imutável após publicação: novas alterações criam uma nova versão (draft → revisão → published).
- Nada de CSS ou JavaScript arbitrário enviado por clientes. A personalização é **declarativa** (tokens, densidade, tipografia, sidebar, topbar, opções de componentes e variantes aprovadas).
- Não transformar o login `access_4` em requisito de autenticação ou seleção de tema; aproveitar seus tokens, fontes e apresentação como referência.

## Controle por ambiente

```dotenv
APP_UI_THEME=classic   # padrão: mantém as views e os assets anteriores
APP_UI_THEME=refined   # habilita resolução de tema publicada; sem tema autorizado, fallback refinado padrão
```

`APP_UI_THEME` controla a arquitetura habilitada globalmente. A preferência de cada empresa/usuário é um **registro no banco**, não uma nova variável de ambiente. Alternar para `classic` não apaga preferências, dados nem templates e tem rollback simples.

## Resolução do tema por requisição (proposta)

1. Se `config('ui_theme.name') === 'classic'`: retornar tema histórico sem carregar assets novos ou consultar o catálogo.
2. Identificar usuário autenticado e **empresa corrente** no contexto validado. Nunca confiar somente em IDs enviados na URL ou no navegador.
3. Se houver escolha individual para **(user_id, empresa_id)** e a versão continuar publicada/autorizada, aplicá-la.
4. Senão, se houver tema padrão publicado/autorizado atribuído à empresa, usá-lo.
5. Senão, usar o tema global padrão `refined`.
6. Para cada escolha, resolver modo de menu `lateral/superior`, densidade `compacta/padrão` e esquema `light/dark`, somente quando suportados pela versão; aplicar fallback seguro.
7. Cachear por usuário, empresa, versão e contexto de permissões; invalidar cache quando publicação/permissão/seleção mudar.

### Exemplo

A empresa A disponibiliza `refined-standard` e `refined-midnight`. Usuário 1 escolhe `refined-midnight` e sidebar compacta; usuário 2 usa o padrão `refined-standard`. Ambos acessam os mesmos módulos do ERP, sujeitos às **mesmas permissões de negócio**. A empresa B só vê o que o superadmin autorizou à empresa B. Um tema jamais concede acesso à rota ou dado.

## Modelagem de persistência sugerida

| Entidade | Campos mínimos | Invariantes |
|---|---|---|
| `ui_themes` | uuid, slug único, nome, descrição, owner_superadmin_id, status, created_at | Slug não reutilizado entre produtos |
| `ui_theme_versions` | uuid, theme_id, version, definition_json, checksum, published_at, status | Imutável depois de `published`; unique(theme_id, version) |
| `ui_theme_company_access` | company_id, theme_id, allowed, approved_by | Controle de catálogo por empresa |
| `ui_theme_company_defaults` | company_id, theme_version_id | Um padrão por empresa; versão deve ser autorizada |
| `ui_user_theme_preferences` | company_id, user_id, theme_version_id, menu_mode, density, color_mode | UNIQUE(company_id,user_id); somente versões permitidas |
| `ui_theme_audit` | actor_user_id, company_id?, action, object_type, object_id, old/new metadata, timestamp | Auditoria de publicação e distribuição |

Esses nomes são **proposta**, não migrations implantadas. Conferir FKs reais `Usuario`/`Empresa`, tenancy e regras existentes antes de gerar migrations. Nenhum `DROP` necessário na implementação.

## Contrato visual e de runtime

O pacote de uma versão do tema deve fornecer `manifest.json` validado em schema, design tokens CSS, variantes de componentes e assets imutáveis. A mesma versão deve atender menu superior/lateral e claro/escuro quando suportado. O frontend não deve executar scripts carregados de um catálogo de temas.

- Shell: identidade da empresa, contexto de sessão/filial/PDV, usuário, suporte, notificações, sidebar/topbar e área de conteúdo.
- Componentes: inputs, validação, selects, data grid, filtros, ações, dialogs, alertas, menus, cards, formulários, assistentes, tabelas, arquivos, calendário e impressões.
- Adaptadores legados temporários: `KT*`, jQuery, Metronic/Bootstrap 4, Select2 e DataTable somente onde necessário, com remoção gradual mediante teste.
- Fontes compartilhadas com o `access_4`, sem sobrescrever bibliotecas de ícones ou textos de PDF/impressão.
- Gerenciador de temas exclusivo SUPERADMIN: pré-visualizar (sem publicar), duplicar versão, editar tokens e componentes, validar, publicar, revogar, liberar por empresa e definir padrão global.
- Seletor para empresas/usuários: listar somente temas autorizados; trocar e persistir preferência; voltar ao padrão da empresa; não permitir criação pelo cliente.

## Testes de aceite

- Uma mudança na main não pode sobrescrever as definições publicadas ou alterar `classic`.
- Tema default pode ser revogado sem bloquear login ou telas: fallback coerente.
- Mudança de empresa durante a sessão invalida seleção/tema de outra empresa.
- Usuário com duas empresas pode ter tema diferente em cada uma.
- Mesmas permissões, rotas e registros nos temas antigo e novo.
- Seleção de temas/versões publicados não permite CSS remoto arbitrário, XSS, path traversal, SSRF ou upload executável.
- Testes browser desktop/mobile das telas reais do ERP; PDF/impressão conservam seus próprios estilos.
- Rollback para `APP_UI_THEME=classic` não exige migrações reversas nem apagar dados.

## Dependência dos flows de atualização

A sincronização `main → feature` copia a evolução funcional; a compatibilidade valida contratos e identifica módulos impactados; uma pré-release única `ui-continuous` disponibiliza um build de homologação validado. **Nenhum desses workflows publica ou mescla a PR visual na main.**
