# Versionamento Interno da Aplicação

Este projeto possui um controle **interno** de versão (independente do Laravel Updater), baseado na tabela `app_versions`.

## Objetivo

- Registrar a versão instalada no sistema.
- Manter histórico completo de release notes.
- Suportar release notes em HTML, PDF ou ambos.

## Registro de nova release

Use o comando abaixo no deploy:

```bash
php artisan app-version:register 1.2.3 \
  --title="Release 1.2.3" \
  --notes-html-file="storage/app/releases/1.2.3.html" \
  --notes-pdf="storage/releases/1.2.3.pdf" \
  --released-at="2026-03-10 10:30:00" \
  --installed-at="2026-03-10 10:45:00" \
  --build-number="build-20260310.1" \
  --commit-hash="abc123def456" \
  --release-channel="stable" \
  --author="Wallace Kleiton <wkarts@gmail.com>" \
  --observations="Ajustes fiscais e melhorias de performance" \
  --metadata='{"ticket":"REL-123"}'
```

## Idempotência

- O comando usa `updateOrCreate` por `version`.
- Reexecutar para a mesma versão atualiza metadados sem duplicar registro.
- Por padrão marca a versão como atual (`is_current=true`) e desmarca versões anteriores.

## Consulta na interface

- Histórico completo: `/app-versions`
- Exibição rápida da versão atual:
  - menu superior (mobile)
  - base da sidebar (desktop)
