#!/usr/bin/env bash
# Versionamento beta permanente do FERSOFT WEB (exclusivo da develop).
# Uso: bash scripts/ui/beta-version.sh v5.0.225 42
# Resultado: v5.0.226-beta.42
set -euo pipefail

stable="${1:-}"
ordinal="${2:-}"

if [[ ! "$stable" =~ ^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]; then
  echo "ERRO: a base deve ser uma tag estável vMAJOR.MINOR.PATCH." >&2
  exit 2
fi

if [[ ! "$ordinal" =~ ^[1-9][0-9]*$ ]]; then
  echo "ERRO: número beta deve ser inteiro positivo." >&2
  exit 2
fi

major="${BASH_REMATCH[1]:-}"
# Restaurar os grupos da primeira expressão regular, pois a segunda os altera.
if [[ "$stable" =~ ^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$ ]]; then
  major="${BASH_REMATCH[1]}"
  minor="${BASH_REMATCH[2]}"
  patch="${BASH_REMATCH[3]}"
fi

next_patch=$((patch + 1))
printf 'v%s.%s.%s-beta.%s\n' "$major" "$minor" "$next_patch" "$ordinal"
