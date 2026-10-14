#!/usr/bin/env bash
# Análise de impacto NÃO destrutiva; identifica alterações para revisão humana.
set -euo pipefail
out="ui-impact-report.md"
base=""
if git rev-parse HEAD^2 >/dev/null 2>&1 && git log -1 --format=%s | grep -q '^chore(ui-sync): import main '; then
  base="HEAD^1"
else
  base="$(git merge-base HEAD origin/main)"
fi
mapfile -t changed < <(git diff --name-only "$base" HEAD)
declare -A classes
for file in "${changed[@]}"; do
  case "$file" in
    resources/views/default/*|app/Providers/AppServiceProvider.php|app/Helpers/Menu.php)
      key="CRITICO shell, menu, sessão e layouts" ;;
    public/metronic/*|public/js/*|resources/js/*|package.json|package-lock.json)
      key="ALTO JS, plugins, build e contratos DOM" ;;
    resources/views/*|public/css/*|resources/css/*)
      key="ALTO views e CSS de módulos" ;;
    routes/*|app/Http/Controllers/*)
      key="MEDIO rotas/controllers (contexto de tela)" ;;
    *)
      key="OUTROS atualizações gerais da aplicação" ;;
  esac
  classes["$key"]=$(( ${classes["$key"]:-0} + 1 ))
done
{
  echo "# Relatório de impacto da UI"
  echo ""
  echo "SHA: $(git rev-parse HEAD)"
  echo "Base comparada: $base"
  echo "Arquivos alterados: ${#changed[@]}"
  echo ""
  echo "| Categoria | Quantidade |"
  echo "|---|---:|"
  for key in "${!classes[@]}"; do
    printf '| %s | %d |\n' "$key" "${classes[$key]}"
  done
  echo ""
  echo "## Arquivos"
  for file in "${changed[@]}"; do printf -- '- `%s`\n' "$file"; done
  echo ""
  echo "**IMPORTANTE:** análises estáticas e testes automatizados não provam compatibilidade visual/UX; validar com navegação real em ambiente isolado."
} > "$out"
cat "$out" >> "$GITHUB_STEP_SUMMARY"
