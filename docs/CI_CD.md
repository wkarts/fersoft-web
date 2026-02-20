# CI/CD Completo (PR + Release + Notificações)

Este documento descreve o fluxo implementado para garantir validação de PR, release automatizada/manual, geração de artifact, e notificações seguras via e-mail e webhook.

## Autor de referência
- Nome: Wallace Kleiton
- GitHub: @wkarts
- E-mail: wkarts@gmail.com
- WhatsApp: +55 75 98844-9231

## Workflows implementados

### 1) `.github/workflows/pr.yml`
Executa em `pull_request` (`opened`, `synchronize`, `reopened`, `ready_for_review`).

**Jobs**
- `setup`: detecta presença de `composer.json`, `package.json`, runner de testes.
- `php`: valida Composer, instala dependências, `php -v`, lint (`php -l`) e testes (`phpunit`/`artisan test`).
- `node`: `npm ci`, e roda `npm test`/`npm run build` apenas se scripts existirem.
- `security`: `composer audit` e `npm audit` em modo warning-only (`continue-on-error`).
- `summary`: publica resumo com status dos jobs.

**Comportamento importante**
- Não falha por ausência de stack (PHP/Node); apenas pula com log claro.
- Para bloqueio de merge, configure **branch protection** exigindo os checks desse workflow.

---

### 2) `.github/workflows/release.yml`
Executa em:
- `push` na `main` (merge concluído)
- `workflow_dispatch` (manual)

**Etapas**
1. `full-validation`: roda pipeline full novamente (reprodutibilidade).
2. `prepare-release`:
   - Descobre PR associado ao commit mergeado.
   - Calcula bump via labels:
     - `release:major`
     - `release:minor`
     - `release:patch`
   - Fallback automático: `patch`.
   - Em manual: aceita `input.version`.
   - Suporta pre-release `-rc.N` via `workflow_dispatch`.
3. `create-release`:
   - Garante idempotência (tag/release pré-existente aborta com mensagem clara).
   - Gera changelog auxiliar (`scripts/release/changelog.sh`).
   - Gera artifact `.zip` excluindo `.git`, `node_modules`, caches e logs.
   - Publica artifact no run e anexa na GitHub Release.
4. Notificação:
   - `notify-success`: quando release publicada.
   - `notify-failure`: quando pipeline de release falha.

---

### 3) `.github/workflows/notify.yml` (reutilizável)
Recebe status e metadados da release via `workflow_call`.

- Monta payload JSON com: versão, tag, URL, commit, data, run-id.
- Envia webhook com retry/backoff.
- Envia e-mail SMTP.
- Pode ser obrigatório (`notify_required=true`) ou best effort (`false`, padrão).

## Scripts auxiliares
- `scripts/release/version.sh`
  - Calcula próxima versão a partir da última tag estável (`vX.Y.Z`).
  - Modos: automático (bump) ou manual (versão informada).
  - Suporta `pre-release` (`-rc.N`).
- `scripts/release/changelog.sh`
  - Gera resumo de commits para anexar à release.
- `scripts/notify/webhook.sh`
  - POST com retry e backoff.
  - Não imprime token em log.
- `scripts/notify/send_email.sh`
  - Envia e-mail via SMTP com `starttls` usando Python padrão.
- `scripts/notify/email.md`
  - Template base do corpo de e-mail.

## Labels de PR para versionamento
Use no PR antes do merge:
- `release:major`
- `release:minor`
- `release:patch`

Sem label, o fluxo usa `patch`.

## Secrets obrigatórios
Sem valores hardcoded; configure no GitHub (Repository/Organization Secrets):

- `WEBHOOK_URL`
- `WEBHOOK_TOKEN` (opcional)
- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_FROM`
- `SMTP_TO`

Observações:
- `GITHUB_TOKEN` é fornecido automaticamente pelo Actions.
- Não exponha secrets em logs.

## Inputs de release manual (`workflow_dispatch`)
- `version` (ex.: `1.4.2` ou `v1.4.2`)
- `prerelease` (`true/false`)
- `prerelease_number` (ex.: `1` => `-rc.1`)
- `include_vendor` (`true/false`)
- `notify_required` (`true/false`)

## Idempotência e segurança operacional
- Se tag já existe: fluxo aborta com mensagem explícita.
- Se release já existe para a tag: fluxo aborta com instrução.
- Notificações falham sem quebrar release por padrão (best effort).
- Se precisar enforcement, use `notify_required=true` no dispatch.

## Como testar localmente
```bash
# Verificar scripts
bash scripts/release/version.sh auto patch
bash scripts/release/changelog.sh "" HEAD /tmp/release-notes.md

# Simular envio webhook (sem URL real => skip)
bash scripts/notify/webhook.sh /tmp/payload.json

# Simular e-mail (sem SMTP completo => skip)
bash scripts/notify/send_email.sh scripts/notify/email.md
```

## Como validar no GitHub
1. Abrir PR com alteração simples.
2. Confirmar execução de `PR - Validação e Qualidade`.
3. Aplicar label de bump (`release:minor`, por exemplo).
4. Fazer merge em `main`.
5. Confirmar:
   - Nova tag `vX.Y.Z`
   - GitHub Release criada com notes
   - Artifact zip anexado
   - Jobs de notificação executados
6. Rodar `workflow_dispatch` para release manual informando `version`.

## Rollback sugerido
Se release foi criada indevidamente:
1. Excluir release no GitHub.
2. Excluir tag local/remota correspondente.
3. Corrigir o PR/label/versão.
4. Reexecutar release manual com versão correta.

> Dica: manter branch protection na `main` com checks obrigatórios reduz risco de release incorreta.
