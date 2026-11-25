"""Preview a stable commit on the visual branch in an isolated git worktree."""
import argparse
import difflib
import os
from pathlib import Path
import re
import subprocess
import tempfile


# Restrict automatic suggestions to literal CSS classes on a single HTML tag.
TAG = re.compile(r'(?P<prefix>[ \t]*<[a-z][a-z0-9-]*(?:[ \t]+[\w:-]+="[^"<>]*")*[ \t]+class=")(?P<css>[\w -]+)(?P<suffix>"(?:[ \t]+[\w:-]+="[^"<>]*")*[ \t]*/?>[ \t]*)')
VISUAL_PREFIX = ('fer-', 'ui-', 'document-fiscal-')
HUNK = re.compile(r'^<<<<<<< DEV\n(.*?)^\|\|\|\|\|\|\| BASE\n(.*?)^=======\n(.*?)^>>>>>>> MAIN\n', re.MULTILINE | re.DOTALL)


def git(root, *args, check=True):
    return subprocess.run(['git', '-C', root, *args], check=check, capture_output=True)


def resolve_hunk(develop, base, incoming):
    old, visual, new = [text.splitlines(keepends=True) for text in (base, develop, incoming)]
    changed = False
    for op, a, b, c, d in difflib.SequenceMatcher(None, old, visual, autojunk=False).get_opcodes():
        if op == 'equal':
            continue
        if op != 'replace' or b - a != 1 or d - c != 1:
            return None
        before, after = [TAG.fullmatch(line.rstrip('\r\n')) for line in (old[a], visual[c])]
        if not before or not after:
            return None
        if any(before[k] != after[k] for k in ('prefix', 'suffix')):
            return None
        original, styled = before['css'].split(), after['css'].split()
        added = [name for name in styled if name not in original]
        if not added or not all(x.startswith(VISUAL_PREFIX) for x in added):
            return None
        if original != [name for name in styled if name not in added]:
            return None
        matches = [i for i, line in enumerate(new) if line.strip() == old[a].strip()]
        if len(matches) != 1 or sum(line.strip() == old[a].strip() for line in old) != 1:
            return None
        i = matches[0]
        new[i] = new[i].replace('class="' + before['css'] + '"', 'class="' + after['css'] + '"', 1)
        changed = True
    return ''.join(new) if changed else None


def suggest(root, path):
    if not path.startswith('resources/views/') or not path.endswith('.blade.php'):
        return False
    stages = git(root, 'ls-files', '--stage', '--', path).stdout.splitlines()
    if len(stages) != 3 or any(not x.startswith(b'100644 ') for x in stages):
        return False
    with tempfile.TemporaryDirectory() as tmp:
        inputs = []
        for stage in (2, 1, 3):
            target = Path(tmp, str(stage))
            target.write_bytes(git(root, 'show', f':{stage}:{path}').stdout)
            inputs.append(str(target))
        merged = git(root, 'merge-file', '-p', '--diff3', '-L', 'DEV', '-L', 'BASE', '-L', 'MAIN', *inputs, check=False)
    if not 0 < merged.returncode < 128:
        return False
    try:
        text = merged.stdout.decode()
    except UnicodeDecodeError:
        return False
    hunks = list(HUNK.finditer(text))
    if not hunks or len(hunks) != text.count('<<<<<<< DEV\n'):
        return False
    for match in reversed(hunks):
        result = resolve_hunk(*match.groups())
        if result is None:
            return False
        text = text[:match.start()] + result + text[match.end():]
    if re.search(r'^(?:<{7}|>{7}|={7}|\|{7})(?: |$)', text, re.MULTILINE):
        return False
    Path(root, path).write_bytes(text.encode())
    git(root, 'add', '--', path)
    return True


def preview(repo, visual, incoming, output):
    dev, stable = [git(repo, 'rev-parse', '--verify', ref + '^{commit}').stdout.decode().strip()
                   for ref in (visual, incoming)]
    output.mkdir(parents=True, exist_ok=True)
    with tempfile.TemporaryDirectory() as temp:
        root = Path(temp, 'preview')
        git(repo, 'worktree', 'add', '--quiet', '--detach', str(root), dev)
        try:
            merge = git(root, '-c', 'user.name=UI Preview', '-c', 'user.email=preview@localhost',
                        'merge', '--no-commit', '--no-ff', stable, check=False)
            paths = [os.fsdecode(x) for x in git(root, 'diff', '--name-only', '--diff-filter=U', '-z').stdout.split(b'\0') if x]
            if merge.returncode and not paths:
                raise RuntimeError(merge.stderr.decode(errors='replace'))
            suggested = [path for path in paths if suggest(root, path)]
            pending = sorted(set(paths) - set(suggested))
            patch = output / 'classes-visuais.patch'
            if suggested:
                patch.write_bytes(git(root, 'diff', '--cached', '--binary', dev, '--', *suggested).stdout)
            else:
                patch.unlink(missing_ok=True)
            report = ['## Compatibilidade com a homologação', '', f'- Develop: `{dev}`', f'- Entrada: `{stable}`',
                      f'- Arquivos em conflito: {len(paths)}', f'- Propostas de portabilidade: {len(suggested)}',
                      f'- Conflitos para revisão manual: {len(pending)}']
            if suggested:
                report += ['', '### Patch para revisão', '', 'Diff dos arquivos resolvidos, relativo ao commit de develop acima.', '']
                report += [f'- `{p}`' for p in suggested]
            if pending:
                report += ['', '### Revisar funcionalidade e apresentação', ''] + [f'- `{p}`' for p in pending]
            (output / 'resumo.md').write_text('\n'.join(report)+'\n', encoding='utf-8')
            print('\n'.join(report))
        finally:
            git(repo, 'worktree', 'remove', '--force', str(root))


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('develop')
    parser.add_argument('incoming')
    parser.add_argument('output', type=Path)
    args = parser.parse_args()
    preview(Path.cwd(), args.develop, args.incoming, args.output.resolve())
