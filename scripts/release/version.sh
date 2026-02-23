#!/usr/bin/env bash
set -euo pipefail

log() {
  echo "[version] $*"
}

normalize_version() {
  local raw="$1"
  local clean="${raw#v}"

  if [[ ! "$clean" =~ ^[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.-]+)?$ ]]; then
    echo ""
    return 1
  fi

  echo "v$clean"
}

latest_stable_tag() {
  git fetch --tags --force >/dev/null 2>&1 || true
  local latest
  latest=$(git tag --list 'v[0-9]*.[0-9]*.[0-9]*' --sort=-version:refname | head -n1 || true)
  if [[ -z "$latest" ]]; then
    echo "v0.0.0"
  else
    echo "$latest"
  fi
}

bump_semver() {
  local base="$1"
  local bump="$2"
  local clean="${base#v}"
  IFS='.' read -r major minor patch <<< "$clean"

  case "$bump" in
    major)
      major=$((major + 1)); minor=0; patch=0 ;;
    minor)
      minor=$((minor + 1)); patch=0 ;;
    patch|*)
      patch=$((patch + 1)) ;;
  esac

  echo "v${major}.${minor}.${patch}"
}

make_prerelease() {
  local version="$1"
  local number="$2"
  echo "${version}-rc.${number}"
}

main() {
  local mode="${1:-auto}"
  local bump="${2:-patch}"
  local manual_version="${3:-}"
  local prerelease="${4:-false}"
  local prerelease_number="${5:-1}"

  if [[ "$mode" == "manual" ]]; then
    if [[ -z "$manual_version" ]]; then
      echo "manual mode requires version input" >&2
      exit 1
    fi
    local validated
    validated=$(normalize_version "$manual_version")
    echo "$validated"
    exit 0
  fi

  local latest
  latest=$(latest_stable_tag)
  log "Última tag estável encontrada: $latest"

  local next
  next=$(bump_semver "$latest" "$bump")

  if [[ "$prerelease" == "true" ]]; then
    next=$(make_prerelease "$next" "$prerelease_number")
  fi

  echo "$next"
}

main "$@"
