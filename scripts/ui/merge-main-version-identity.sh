#!/usr/bin/env bash
# Merge normal; a single historical backport may use its reviewed functional base.
# Usage: bash merge-main-version-identity.sh TARGET MESSAGE [--provenance FIXTURE.json]
set -euo pipefail

if [[ "$#" != 2 && "$#" != 4 ]]; then
  echo 'Usage: merge-main-version-identity.sh TARGET MESSAGE [--provenance FIXTURE.json]' >&2
  exit 2
fi
target_ref="$1"
merge_message="$2"
script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
provenance_file="$script_directory/version-identity-backport.json"
fixture_provenance=false
if [[ "$#" == 4 ]]; then
  if [[ "$3" != '--provenance' ]]; then
    echo 'Unknown option. Only --provenance is available for isolated fixtures.' >&2
    exit 2
  fi
  provenance_file="$4"
  fixture_provenance=true
fi

merge_temporary=''
merge_started=false
cleanup() {
  local result="$1"
  trap - EXIT
  if [[ "$result" != 0 && "$merge_started" == true ]] && git rev-parse -q --verify MERGE_HEAD >/dev/null 2>&1; then
    if ! git merge --abort; then
      echo 'Failed to abort the candidate merge. No push was attempted; inspect the local worktree.' >&2
    fi
  fi
  if [[ -n "$merge_temporary" ]]; then
    rm -rf -- "$merge_temporary"
  fi
  exit "$result"
}
trap 'cleanup "$?"' EXIT

git rev-parse --show-toplevel >/dev/null
if ! git diff --quiet || ! git diff --cached --quiet; then
  echo 'Refusing to start with tracked local changes or unresolved conflicts.' >&2
  exit 1
fi
for state_ref in MERGE_HEAD CHERRY_PICK_HEAD REVERT_HEAD; do
  if git rev-parse -q --verify "$state_ref" >/dev/null 2>&1; then
    echo 'Refusing to interrupt an existing Git operation.' >&2
    exit 1
  fi
done
for state_directory in rebase-merge rebase-apply; do
  if [[ -d "$(git rev-parse --git-path "$state_directory")" ]]; then
    echo 'Refusing to interrupt an existing rebase.' >&2
    exit 1
  fi
done

original_head="$(git rev-parse --verify 'HEAD^{commit}')"
target_commit="$(git rev-parse --verify --end-of-options "${target_ref}^{commit}")"
merge_started=true
if git merge --no-ff --no-edit -m "$merge_message" "$target_commit"; then
  merge_started=false
  exit 0
fi
if ! git rev-parse -q --verify MERGE_HEAD >/dev/null 2>&1; then
  echo 'Normal merge failed without a resolvable merge state.' >&2
  exit 1
fi

merge_temporary="$(mktemp -d "${TMPDIR:-/tmp}/main-version-identity.XXXXXXXX")"
python3 - "$provenance_file" "$fixture_provenance" > "$merge_temporary/provenance" <<'PY'
import json
import re
import sys

expected_paths = [
    'app/Services/AppVersionService.php',
    'app/Services/InstalledReleaseIdentity.php',
    'tests/Unit/AppVersionServiceIdentityTest.php',
    'tests/Unit/InstalledReleaseIdentityTest.php',
]
try:
    with open(sys.argv[1], encoding='utf-8') as stream:
        data = json.load(stream)
    if not isinstance(data, dict) or type(data.get('schema_version')) is not int or data['schema_version'] != 1:
        raise ValueError('unknown provenance schema')
    for key in ['merge_base', 'shared_fix_commit']:
        if not isinstance(data.get(key), str) or not re.fullmatch(r'[0-9a-f]{40}', data[key]):
            raise ValueError('provenance requires complete commit identities')
    if data.get('paths') != expected_paths:
        raise ValueError('the historical backport allowlist must not be widened or reordered')
    if sys.argv[2] == 'false' and (
        data['merge_base'] != '35856404d648c5085b1fc8716dc34865bf5471c7'
        or data['shared_fix_commit'] != 'f69e545206d960f0595fd9544dce3a96d422447b'
    ):
        raise ValueError('default provenance does not match the reviewed historical backport')
    print(data['merge_base'])
    print(data['shared_fix_commit'])
    print('\n'.join(expected_paths))
except (OSError, ValueError, TypeError) as error:
    raise SystemExit('Invalid version-identity provenance: ' + str(error))
PY
mapfile -t provenance < "$merge_temporary/provenance"
expected_base="${provenance[0]}"
shared_fix="${provenance[1]}"
actual_base="$(git merge-base --all "$original_head" "$target_commit")"
if [[ "$actual_base" != "$expected_base" ]]; then
  echo 'Merge base differs from the single reviewed historical backport; refusing automatic resolution.' >&2
  exit 1
fi
if ! git merge-base --is-ancestor "$shared_fix" "$original_head"; then
  echo 'The reviewed shared identity fix is not an ancestor of the development candidate.' >&2
  exit 1
fi

mapfile -d '' -t conflicts < <(git diff --name-only --diff-filter=U -z)
if [[ "${#conflicts[@]}" == 0 ]]; then
  echo 'The candidate merge failed without allowlisted content conflicts.' >&2
  exit 1
fi

# Validate every conflict before preparing any working-tree replacement.
for path in "${conflicts[@]}"; do
  allowed=false
  for known_path in "${provenance[@]:2}"; do
    if [[ "$path" == "$known_path" ]]; then allowed=true; break; fi
  done
  if [[ "$allowed" != true ]]; then
    echo "Conflict outside the historical identity allowlist: $path" >&2
    exit 1
  fi
  if [[ ! -f "$path" || -L "$path" ]]; then
    echo "Refusing a deleted, symbolic or non-regular identity path: $path" >&2
    exit 1
  fi
done

for index in "${!conflicts[@]}"; do
  path="${conflicts[$index]}"
  # Both branch blobs must exist as regular source files. No delete/type conflict
  # is resolved by this narrowly scoped content strategy.
  git ls-files -u -- "$path" > "$merge_temporary/stages-$index"
  if ! awk '
    $3 == 2 { ours++; if ($1 != "100644") bad = 1 }
    $3 == 3 { theirs++; if ($1 != "100644") bad = 1 }
    END { exit !(ours == 1 && theirs == 1 && !bad) }
  ' "$merge_temporary/stages-$index"; then
    echo "Refusing a structural or mode conflict: $path" >&2
    exit 1
  fi
  git show ":2:$path" > "$merge_temporary/ours-$index"
  git show "$shared_fix:$path" > "$merge_temporary/base-$index"
  git show ":3:$path" > "$merge_temporary/theirs-$index"
  if ! git merge-file -p -L "develop:$path" -L "shared-fix:$path" -L "main:$path" \
    "$merge_temporary/ours-$index" "$merge_temporary/base-$index" "$merge_temporary/theirs-$index" \
    > "$merge_temporary/merged-$index"; then
    echo "Functional three-way merge still conflicts: $path" >&2
    exit 1
  fi
done

for index in "${!conflicts[@]}"; do
  path="${conflicts[$index]}"
  cat "$merge_temporary/merged-$index" > "$path"
  git add -- "$path"
done
if [[ -n "$(git diff --name-only --diff-filter=U)" ]]; then
  echo 'Unresolved conflicts remain; refusing to commit.' >&2
  exit 1
fi
git commit --no-edit
merge_started=false
echo 'Reviewed historical identity backport merged using its shared functional base. No push was performed.'
