"""Build-only neutral style adaptation of authenticated Blade pages.
Every token preserves the ORIGINAL literal fallback. No forms, IDs, scripts,
business expressions, documents, print/email/public templates are rewritten.
"""
from pathlib import Path
import hashlib
import json
import re

ROOT = Path(__file__).resolve().parents[2]
SURFACES = {'#fff', '#ffffff', '#fefefe', '#fafafa', '#f9f9f9', '#f8f9fa', '#f5f5f5', '#f3f6f9', '#f5f8fa', '#f8fafc', '#f9fafb', 'white'}
INK = {'#000', '#000000', '#111', '#111111', '#222', '#222222', '#333', '#333333', '#444', '#444444', '#181c32', '#212529', '#212121', '#3f4254', '#343a40', '#495057', 'black'}
BORDERS = {'#ccc', '#cccccc', '#ddd', '#dddddd', '#eee', '#eeeeee', '#e0e0e0', '#dee2e6', '#ebedf3', '#e4e6ef', '#e5e7eb', '#dddfe1'}
DECLARATION = re.compile(r'(?P<prefix>(?<![\w-])(?P<prop>background(?:-color)?|color|border-color)\s*:\s*)(?P<value>\#[A-Fa-f0-9]{3,6}\b|white\b|black\b)(?P<suffix>\s*(?:!important\s*)?)(?=[;}\n]|$)', re.I)
STRINGS_COMMENTS = re.compile(r'/\*.*?\*/|"(?:\\.|[^"\\])*"|\'(?:\\.|[^\'\\])*\'', re.S)
STYLE = re.compile(r'(<style\b[^>]*>)(.*?)(</style\s*>)|(?<![\w-])(style\s*=\s*)(["\'])(.*?)\5', re.I | re.S)
FALLBACK = re.compile(r'var\(--ui-(?:surface|surface-alt|ink|border),\s*(#[a-fA-F0-9]{3,6}\b|white\b|black\b)\s*\)', re.I)


def classic_source(source: str) -> str:
    return FALLBACK.sub(lambda match: match[1], source)


def css_tokens(css: str) -> tuple[str, int]:
    count = 0
    def declaration(match):
        nonlocal count
        prop, value = match['prop'].lower(), match['value'].lower()
        token = None
        if prop in ('background', 'background-color') and value in SURFACES:
            token = 'surface' if value in ('#fff', '#ffffff', '#fefefe', 'white') else 'surface-alt'
        elif prop == 'color' and value in INK:
            token = 'ink'
        elif prop == 'border-color' and value in BORDERS:
            token = 'border'
        if token is None:
            return match[0]
        count += 1
        return match['prefix'] + 'var(--ui-' + token + ', ' + match['value'] + ')' + match['suffix']
    pieces = []
    cursor = 0
    for quoted in STRINGS_COMMENTS.finditer(css):
        pieces.append(DECLARATION.sub(declaration, css[cursor:quoted.start()]))
        pieces.append(quoted[0])
        cursor = quoted.end()
    pieces.append(DECLARATION.sub(declaration, css[cursor:]))
    return ''.join(pieces), count


def migrate(source: str) -> tuple[str, int]:
    count = 0
    def block(match):
        nonlocal count
        if match[1] is not None:
            value, replacements = css_tokens(match[2])
            result = match[1] + value + match[3]
        else:
            value, replacements = css_tokens(match[6])
            result = match[4] + match[5] + value + match[5]
        count += replacements
        return result
    return STYLE.sub(block, source), count


def main():
    manifest_path = ROOT / 'tests/fixtures/ui/neutral-style-contracts.json'
    manifest = {'files': {}}
    changed = 0
    for path in sorted((ROOT / 'resources/views').rglob('*.blade.php')):
        relative = path.relative_to(ROOT).as_posix()
        if any(relative.startswith('resources/views/' + prefix + '/') for prefix in ('default', 'themes', 'layouts', 'vendor', 'emails', 'mail', 'pdf')):
            continue
        if re.search(r'(?:^|[/_.-])(?:pdf|impressao|imprimir|print|danfe|danfce|emails?)(?:[/_.-]|$)', relative, re.I):
            continue
        source = path.read_text()
        if not re.search(r"@extends\(\s*['\"]default(?:[./]layout|[./]menu_)", source):
            continue
        updated, replacements = migrate(source)
        assert classic_source(updated) == classic_source(source), relative
        total_tokens = len(FALLBACK.findall(updated))
        if not total_tokens:
            continue
        if replacements:
            path.write_text(updated)
            changed += 1
        manifest['files'][relative] = {
            'classic_equivalent_sha256': hashlib.sha256(classic_source(updated).encode()).hexdigest(),
            'neutral_declarations': total_tokens,
        }
    manifest['description'] = 'Neutral style adaptation with original fallback. Not functional or business flow homologation.'
    manifest_path.parent.mkdir(parents=True, exist_ok=True)
    manifest_path.write_text(json.dumps(manifest, ensure_ascii=False, indent=2) + '\n')
    for relative, record in manifest['files'].items():
        assert hashlib.sha256(classic_source((ROOT / relative).read_text()).encode()).hexdigest() == record['classic_equivalent_sha256'], 'Unexpected non-style change: ' + relative
    print(f'Neutral styles adapted: {changed} files this run; {len(manifest["files"])} controlled pages total.')


if __name__ == '__main__':
    main()
