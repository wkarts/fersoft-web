#!/usr/bin/env bash
set -euo pipefail

PREVIOUS_TAG="${1:-}"
CURRENT_SHA="${2:-HEAD}"
OUTPUT_FILE="${3:-release-notes.md}"

if [[ -z "$PREVIOUS_TAG" ]]; then
  PREVIOUS_TAG=$(git tag --list 'v[0-9]*.[0-9]*.[0-9]*' --sort=-version:refname | head -n1 || true)
fi

{
  echo "## Resumo da release"
  echo
  echo "- Commit: \`$(git rev-parse --short "$CURRENT_SHA")\`"
  if [[ -n "$PREVIOUS_TAG" ]]; then
    echo "- Base: \`$PREVIOUS_TAG\`"
  else
    echo "- Base: primeira release"
  fi
  echo
  echo "## Commits"

  if [[ -n "$PREVIOUS_TAG" ]]; then
    git log --pretty=format:'- %s (%h)' "$PREVIOUS_TAG".."$CURRENT_SHA"
  else
    git log --pretty=format:'- %s (%h)' -n 30 "$CURRENT_SHA"
  fi
} > "$OUTPUT_FILE"

echo "$OUTPUT_FILE"
