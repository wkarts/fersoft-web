"""Explicit offline snapshot generator for the supplied develop ZIP.

Usage: python tests/ui/generate-develop-contracts.py --zip /path/to/develop.zip
Never run as an automatic CI step: that would hide contract regressions.
"""
from pathlib import Path
from zipfile import ZipFile
import argparse
import hashlib
import importlib.util
import json


ROOT = Path(__file__).resolve().parents[2]
MODULE = Path(__file__).with_name('test-view-contracts.py')
spec = importlib.util.spec_from_file_location('view_contracts', MODULE)
contracts = importlib.util.module_from_spec(spec)
spec.loader.exec_module(contracts)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--zip', type=Path, required=True, help='Original develop ZIP supplied for review')
    args = parser.parse_args()
    source = args.zip.read_bytes()
    result = {
        'source': 'supplied develop ZIP',
        'source_sha256': hashlib.sha256(source).hexdigest(),
        'views': {},
        'duplicate_occurrences': {},
    }
    with ZipFile(args.zip) as archive:
        originals = set(archive.namelist())
        for file in sorted((ROOT / 'resources/views').rglob('*.blade.php')):
            relative = file.relative_to(ROOT).as_posix()
            key = 'fersoft-web-develop/' + relative
            if relative.startswith('resources/views/themes/') or key not in originals:
                continue
            before = archive.read(key)
            if file.read_bytes() == before:
                continue
            old = before.decode('utf-8')
            result['views'][relative] = contracts.extract_contracts(old)
            repeated = sorted([key, value, count]
                              for (key, value), count in contracts.extract_critical_counts(old).items()
                              if count > 1)
            if repeated:
                result['duplicate_occurrences'][relative] = repeated
    output = MODULE.with_name('develop-view-contracts.json')
    output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + '\n', encoding='utf-8')
    print(f'Snapshot: {len(result["views"])} changed operational views; {output}')


if __name__ == '__main__':
    main()
