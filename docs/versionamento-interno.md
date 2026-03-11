# Versionamento Interno da Aplicação

Este projeto usa um controle **interno** de versão baseado na tabela `app_versions`.

## O que o módulo faz

1. Registra versão instalada.
2. Salva nota **individual** da release (`release_notes_current_html`).
3. Gera nota **cumulativa** automaticamente (`release_notes_cumulative_html`) com a versão atual + histórico anterior.
4. Gera e persiste arquivos físicos no `storage/app/releases/{versao}/`.
5. Tenta gerar PDF (atual e cumulativo) usando Dompdf; se indisponível, não quebra o deploy.

## Estrutura de artefatos

Após registrar `1.3.0`:

- `storage/app/releases/1.3.0/current.html`
- `storage/app/releases/1.3.0/cumulative.html`
- `storage/app/releases/1.3.0/current.pdf` (quando disponível)
- `storage/app/releases/1.3.0/cumulative.pdf` (quando disponível)

## Comando de deploy (exemplo)

```bash
php artisan app-version:register 1.3.0 \
  --title="Release 1.3.0" \
  --notes-current-html-file="storage/app/deploy/release-1.3.0.html" \
  --released-at="2026-03-10 22:00:00" \
  --installed-at="2026-03-10 22:10:00" \
  --build-number="build-20260310.2" \
  --commit-hash="abc123def456" \
  --release-channel="stable" \
  --author="Wallace Kleiton <wkarts@gmail.com>" \
  --observations="Release de fechamento fiscal" \
  --metadata='{"ticket":"REL-130"}'
```

Opcionalmente, para usar automaticamente a versão definida no `composer.json`/ENV:

```bash
php artisan app-version:register --use-composer-version --notes-current-html="Melhorias gerais"
```

### Compatibilidade com flags antigas

As opções antigas ainda funcionam:

- `--notes-html`
- `--notes-html-file`

Internamente, elas alimentam a nota **current**.

## Idempotência

- O registro usa `updateOrCreate` por `version`.
- Reexecutar o comando para a mesma versão atualiza dados e regenera artefatos.
- Quando `is_current=true`, versões anteriores são desmarcadas.

## Rotas de consulta

- Página completa: `/app-versions`
- PDF atual: `/app-versions/{id}/pdf/current`
- PDF cumulativo: `/app-versions/{id}/pdf/cumulative`
- HTML atual: `/app-versions/{id}/html/current`
- HTML cumulativo: `/app-versions/{id}/html/cumulative`

## UI

- Mobile: versão atual no menu superior.
- Desktop: versão atual na base da sidebar.
- Histórico: agrupado por `version_major` com expand/collapse na página e no modal do topo.


## Visibilidade da versão atual

- Se não houver registro `is_current` no banco, o sistema usa fallback da versão em `composer.json` (`version`) e, em seguida, `VERSION`/`APP_VERSION` para evitar exibição `N/A`.
- A versão atual permanece visível no menu superior (mobile) e na base da sidebar (desktop), com ajuste de espaçamento para não ser coberta pelo balão flutuante de suporte.


## Fluxo recomendado no deploy

1. Atualizar `composer.json` com a versão da release (ex.: `"version": "5.0.21"`).
2. Publicar artefatos de release em `storage/app/releases/{versao}/`.
3. Executar sincronização:

```bash
php artisan app-version:sync --version=5.0.21
```

4. (Opcional) registrar release atual e regenerar cumulativo/artefatos:

```bash
php artisan app-version:register --use-composer-version --notes-current-html-file="storage/app/releases/5.0.21/current.html"
```


## Estratégia para a primeira release consolidada (ex.: 5.0.22)

Para a próxima release (5.0.22), use o comando padrão `app-version:register`.
Ele já faz bootstrap automático dos artifacts antigos antes de gerar a cumulativa.

Exemplo:

```bash
php artisan app-version:register 5.0.22 \
  --title="Release 5.0.22" \
  --notes-current-html-file="storage/app/releases/5.0.22/current.html" \
  --use-composer-version
```

Resultado esperado para `5.0.22`:
- nota **atual**: conteúdo da 5.0.22;
- nota **cumulativa**: 5.0.22 + todas as versões anteriores encontradas em `storage/app/releases`.

Para desativar esse bootstrap em um caso específico, use `--no-bootstrap-artifacts`.
