# FERSOFT WEB — fluxo permanente de branches, pré-release e release

## Referências permanentes

| Referência | Finalidade | Permanência |
|---|---|---|
| main | Versão estável e releases oficiais | Permanente |
| develop | Integração, desenvolvimento visual e pré-release contínua | Permanente |
| feature/*, fix/* | Desenvolvimento específico | Temporárias |
| ui-continuous (tag) | Uma única pré-release continuamente atualizada a partir de develop, com nome/versionamento vX.Y.Z-beta.N | Permanente |

A branch develop foi criada com o histórico do redesenho, sem trazer o código visual para a main. A PR visual antiga #268 é histórica e não deve entrar na main.

## Dois períodos de operação sem modificar os workflows

### Período 1 — UI ainda em desenvolvimento

- Continuar criando branches funcionais e PRs para main, como é feito atualmente.
- Após cada merge na main, ui-main-sync.yml importa os commits na develop por merge normal de Git, preservando os commits exclusivos do novo tema.
- Caso exista conflito, a sincronização deve abortar sem push e abrir uma Issue. É proibido reset hard, force push ou descartar arquivos de develop.
- A develop sempre dispara o pipeline beta após push/commit/merge, mesmo quando a main tenha uma atualização ainda não sincronizada. A compatibilidade continua obrigatória: só um build validado substitui a pré-release existente, e o fluxo main → develop segue importando os commits estáveis sem sobrescrever o tema.
- Nenhuma ferramenta de CI ou fluxo de release deve fazer merge de develop para main neste período.

### Período 2 — UI aprovada; ciclo oficial de desenvolvimento

Depois de aprovar e publicar a nova interface por PR develop para main, configurar no GitHub a variável Actions:

    UI_FLOW_PHASE=standard

Até então, sua ausência equivale a transition. Apenas a variável muda; os workflows não precisam de nova branch nem de alteração.

- Branches de implementação abrem PRs para develop.
- Develop integra, valida e **atualiza a versão beta a cada commit/merge**, preservando a mesma pré-release contínua e a tag ui-continuous.
- Quando aprovado, abrir PR de develop para main.
- O merge na main aciona a release oficial (versionamento estável que já existe).
- Main → develop continua habilitado para incorporar quaisquer hotfixes e alinhar o merge de produção à integração.
- O canal de pré-release continua exclusivo da develop mesmo depois da promoção.

## Workflows permanentes

| Workflow | Função |
|---|---|
| ui-main-sync.yml | main → develop, push/execução manual/checagem diária, sem alterar main |
| ui-compatibility.yml | validações completas do código, Blade, PHP, banco de testes, Node e auditoria |
| ui-prerelease.yml | desenvolve/valida develop e atualiza somente a tag ui-continuous como pré-release |
| ui-branch-policy.yml | política distinta para transition e standard, sem editar workflows |

Os fluxos oficiais release.yml e deploy-release-notes.yml continuam vinculados à main e ao canal estável. O segundo deve ignorar todas as pré-releases. A tag ui-continuous jamais usa o padrão oficial vX.Y.Z e a main não é atualizada por pre-release.

## Ativação de infraestrutura

A PR #273 contém somente infraestrutura. Sua aprovação e merge na main é necessária para que o fluxo main → develop seja executado automaticamente em cada merge de produção e para habilitar publicação segura de GitHub Release tipo pre-release.

A aprovação da PR #273 não mescla o novo tema nem promove develop para main. Ela deve ser integrada separadamente, após os checks. Até então, o workflow de pre-release da develop gera artifacts de Actions, mas se recusa a publicar uma nova GitHub Release que acionaria o deploy histórico.

A PR definitiva de promoção do visual deve ter origem develop e destino main, permanecer draft até a aprovação e não ser mesclada durante transition.

## Proteção de publicação estável em alterações de infraestrutura

O workflow de release oficial `release.yml` continua exclusivo da `main`, mas ignora pushes que alterem **somente** caminhos de suporte: `.github/workflows/**`, `docs/ui/**` e `scripts/ui/**`.

Isso permite integrar a PR de infraestrutura #273 sem publicar uma nova release estável por uma mudança que não alterou a aplicação. O filtro segue a semântica padrão `paths-ignore` do GitHub Actions: se um mesmo merge também alterar `app/**`, `resources/views/**`, `public/**` ou qualquer arquivo fora dos caminhos ignorados, a release estável **continua sendo gerada** normalmente.

O disparo manual `workflow_dispatch` também permanece disponível e não é afetado pelo filtro de paths. A publicação de pré-release da `develop` continua separada da publicação estável da `main`.

**Matriz de segurança (PR #273):** seus 12 arquivos alterados pertencem integralmente aos caminhos ignorados. Portanto, seu merge isolado não deve criar release estável. A futura promoção da PR #274, que modifica layouts e CSS da aplicação, continua elegível para release estável após aprovação.

## Controles e restrições

- No modo transition, o check de política falha se PR develop → main tentar ser promovida.
- No modo standard, o check falha se uma PR para main não vier de develop do mesmo repositório.
- Um check falho **não bloqueia sozinho** um merge sem branch protection/ruleset. Exigir checks e revisões nas regras de proteção de main e develop.
- Para sincronização direta main → develop, o bot github-actions necessita de permissão de push (ou exceção controlada nas regras). Se a política de segurança impedir, o job falhará sem sobrescrever a branch.
- Cada push/merge em develop dispara o pipeline de pré-release, independente de UI_FLOW_PHASE. Runs ultrapassados por commits mais recentes são cancelados e não substituem o pacote atual.
- A versão beta é calculada com o PATCH seguinte à última tag estável alcançável pela main e o número sequencial do workflow: se a estável é v5.0.225, o build 18 recebe v5.0.226-beta.18. O número pode apresentar saltos quando runs falham ou são cancelados, sem reutilização de builds.
- O GitHub mantém uma única Release marcada como pré-release na tag móvel ui-continuous. Seu título, ZIP e manifesto public/ui-build-info.json exibem a beta atual. Os runs de Actions preservam os builds históricos pelo período de retenção configurado.
- Em conflito, falha de PHPUnit/build ou commit desatualizado, manter o último pacote validado.
- O seletor global do tema é APP_UI_THEME=classic ou APP_UI_THEME=refined. O catálogo publicado por SUPERADMIN, e as preferências por empresa e usuário, serão desenvolvidos posteriormente na develop e não alteram esses workflows.

## Operação

    gh workflow run ui-main-sync.yml --ref main
    gh workflow run ui-prerelease.yml --ref develop

Para aprovação final: verificar compatibilidade e screenshots das telas reais do ERP, transformar a PR develop → main em pronta para revisão e, só então, integrar na main.
