# Fluxos exclusivos de evolução da interface — FERSOFT WEB

> A PR #268 e o tema visual continuam fora da `main`. Estes arquivos de workflow são infraestrutura independente; sua incorporação à `main` não transporta o código do tema.

## Três fluxos

1. **Sincronização (`ui-main-sync.yml`):** executado ao receber commits na `main` e uma vez ao dia como recuperação. Tenta *git merge* unilateral **main → develop**, nunca a operação contrária. Se houver conflito, NÃO faz push e abre/atualiza issue de resolução, mantendo a branch visual intacta. Realiza `workflow_dispatch` explícito, pois pushes com `GITHUB_TOKEN` não disparam novos workflows `push`.
2. **Compatibilidade (`ui-compatibility.yml`):** workflow reutilizável e manual que gera relatório de impacto por arquivo, compila todas as views, executa suíte PHPUnit, build Vite e inventário de CSS/JS/Blade. Qualquer teste falho **bloqueia** a pré-release. *Não cria patches semânticos automaticamente* quando mudanças de negócio/UI exigem adaptação; nesses casos o relatório é para revisão.
3. **Pré-release contínua (`ui-prerelease.yml`):** gatilhada por push na branch visual ou por dispatch do fluxo 1. Chama fluxo 2 obrigatoriamente, verifica que a branch está atualizada com a `main`, compila Vite, empacota sem credenciais e mantém **uma única pré-release** `ui-continuous` e asset `fersoft-web-ui-continuous.zip`. Versões estáveis `vX.Y.Z` permanecem intactas.

## Proteção e ativação

- A `main` tem os fluxos estáveis originais de release e deploy. O workflow de notas de release recebe guarda para **ignorar pré-releases**; nenhuma pré-release visual deve disparar deploy estável.
- Enquanto o PR de infraestrutura (separado do #268) não for incorporado à `main`, o fluxo da branch visual pode validar e produzir **artefatos de Actions**, mas não publica Github Release. Isso evita eventos `release.published` não protegidos.
- A identidade da branch pode ser migrada futuramente: atualizar de forma coordenada `UI_BRANCH`, os filtros YAML e o nome esperado pelo empacotador. Nesta etapa a referência é `develop`.
- A pré-release **não** altera `composer.json`, `APP_VERSION`, domínio de produção, banco de dados nem versões oficiais.
- A configuração do tema (uma variável de ambiente): `APP_UI_THEME=classic` ou `APP_UI_THEME=refined`. Login permanece independente com `PAG_LOGIN`.
- O novo design system e os temas administráveis pelo SUPERADMIN (catálogo de temas, concessões por empresa, preferência por usuário, publicação/versionamento) são **escopo futuro**, não implementados por estes workflows.

## Matriz de aceitação obrigatória

- `main` continua referência exclusiva de stable release.
- Nunca dar *merge* de feature → main a partir destes fluxos.
- Mudanças no fluxo de contas, fiscal, vendas e API são importadas na branch visual e exigem validação.
- Conflitos de merge e erros de PHPUnit/Vite interrompem atualização da única pré-release.
- Proteção contra vazamento de `.env*`, `storage`, `public/files`, SQL, segredos e chaves nos pacotes.
- QA de navegador e regressão visual das telas reais ainda precisa de implementação dedicada; teste PHP/asset não garante fidelidade visual.

## Operação manual

```bash
# Alinhamento e diagnóstico (quando os workflows estiverem na main):
gh workflow run ui-main-sync.yml --ref main
gh workflow run ui-prerelease.yml --ref develop

# PR #268 histórica não será mesclada; a promoção final será uma PR draft develop → main. Não efetuar merge para main.
```

## Fase do fluxo

O modo inicial é transition: PRs funcionais permanecem direcionadas à main e o fluxo main → develop alinha a versão experimental. Depois da homologação do novo visual, a variável Actions UI_FLOW_PHASE=standard ativa a exigência de PR → develop → main. Os workflows, a branch develop e a tag rolling ui-continuous são permanentes; não é preciso mudar YAML para a nova fase. Os checks devem ser exigidos por regras de proteção para bloquear merges indevidos.
