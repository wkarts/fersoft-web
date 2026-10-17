#!/usr/bin/env bash
set -euo pipefail

log() { echo "[version] $*" >&2; }
stable_pattern='^v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)$'

assert_main_source() {
  local reference
  for reference in refs/remotes/origin/main refs/heads/main; do
    if git rev-parse --verify "${reference}^{commit}" >/dev/null 2>&1; then
      if ! git merge-base --is-ancestor HEAD "$reference"; then
        log 'O commit da release não pertence à main.'
        return 1
      fi
      return 0
    fi
  done
  log 'A main não está disponível para validar a origem da release.'
  return 1
}

normalize_version() {
  local clean="${1#v}"
  if [[ ! "$clean" =~ ^(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)(-[0-9A-Za-z-]+(\.[0-9A-Za-z-]+)*)?$ ]]; then
    log 'Versão semântica inválida.'
    return 1
  fi
  echo "v$clean"
}

latest_stable_tag() {
  local candidate
  # The caller fetches refs explicitly. Only exact stable tags in this main
  # checkout's ancestry can determine the next stable version.
  while IFS= read -r candidate; do
    if [[ "$candidate" =~ $stable_pattern ]]; then
      echo "$candidate"
      return 0
    fi
  done < <(git tag --merged HEAD --list 'v[0-9]*.[0-9]*.[0-9]*' --sort=-version:refname)
}

tag_exists() { git show-ref --verify --quiet "refs/tags/$1"; }

bump_semver() {
  local clean="${1#v}" bump="$2" major minor patch
  IFS='.' read -r major minor patch <<< "$clean"
  case "$bump" in
    major) major=$((major + 1)); minor=0; patch=0 ;;
    minor) minor=$((minor + 1)); patch=0 ;;
    patch) patch=$((patch + 1)) ;;
    *) log 'Incremento inválido: use major, minor ou patch.'; return 1 ;;
  esac
  echo "v${major}.${minor}.${patch}"
}

next_available_version() {
  local candidate="$1" bump="$2"
  while tag_exists "$candidate"; do
    if [[ "$candidate" =~ ^(v[0-9]+\.[0-9]+\.[0-9]+)-rc\.([0-9]+)$ ]]; then
      candidate="${BASH_REMATCH[1]}-rc.$((BASH_REMATCH[2] + 1))"
    elif [[ "$candidate" =~ $stable_pattern ]]; then
      candidate=$(bump_semver "$candidate" "$bump")
    else
      log 'A pré-release informada já existe; informe uma referência nova.'
      return 1
    fi
  done
  echo "$candidate"
}

main() {
  local mode="${1:-auto}" bump="${2:-patch}" manual_version="${3:-}"
  local prerelease="${4:-false}" prerelease_number="${5:-1}" latest next
  assert_main_source
  if [[ "$mode" == 'latest' ]]; then
    latest_stable_tag
    return
  fi
  if [[ "$mode" == 'manual' ]]; then
    next=$(normalize_version "$manual_version")
    if [[ "$prerelease" != 'true' && ! "$next" =~ $stable_pattern ]]; then
      log 'Uma release estável exige versão MAJOR.MINOR.PATCH sem sufixo.'
      return 1
    fi
  elif [[ "$mode" == 'auto' ]]; then
    latest=$(latest_stable_tag)
    latest="${latest:-v0.0.0}"
    log "Última tag estável encontrada: $latest"
    next=$(bump_semver "$latest" "$bump")
  else
    log 'Modo inválido: use auto, manual ou latest.'
    return 1
  fi
  if [[ "$prerelease" == 'true' && "$next" =~ $stable_pattern ]]; then
    if [[ ! "$prerelease_number" =~ ^[1-9][0-9]*$ ]]; then
      log 'O número do RC deve ser um inteiro positivo.'
      return 1
    fi
    next="${next}-rc.${prerelease_number}"
  fi
  next_available_version "$next" "$bump"
}

main "$@"
