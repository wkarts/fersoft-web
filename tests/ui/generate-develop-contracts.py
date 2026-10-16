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
SHARD_BYTES = 75_000
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
    shard_dir = MODULE.with_name('contracts')
    shard_dir.mkdir(exist_ok=True)
    for stale in shard_dir.glob('part-*.json'):
        stale.unlink()

    def encoded(payload):
        return (json.dumps(payload, ensure_ascii=False, indent=2) + '\n').encode('utf-8')

    shards = []
    batch = {'views': {}, 'duplicate_occurrences': {}}

    def save_batch():
        if not batch['views']:
            return
        name = f'part-{len(shards) + 1:03d}.json'
        data = encoded(batch)
        (shard_dir / name).write_bytes(data)
        shards.append({
            'file': 'contracts/' + name,
            'sha256': hashlib.sha256(data).hexdigest(),
            'views': len(batch['views']),
            'contracts': sum(map(len, batch['views'].values())),
            'duplicate_occurrences': sum(map(len, batch['duplicate_occurrences'].values())),
        })
        batch['views'] = {}
        batch['duplicate_occurrences'] = {}

    for path, contract in result['views'].items():
        candidate = {
            'views': {**batch['views'], path: contract},
            'duplicate_occurrences': dict(batch['duplicate_occurrences']),
        }
        if path in result['duplicate_occurrences']:
            candidate['duplicate_occurrences'][path] = result['duplicate_occurrences'][path]
        if batch['views'] and len(encoded(candidate)) > SHARD_BYTES:
            save_batch()
            candidate = {
                'views': {path: contract},
                'duplicate_occurrences': ({path: result['duplicate_occurrences'][path]}
                                          if path in result['duplicate_occurrences'] else {}),
            }
        if len(encoded(candidate)) > SHARD_BYTES:
            raise ValueError(f'{path} alone exceeds the {SHARD_BYTES}-byte shard limit')
        batch = candidate
    save_batch()
    index = {
        'source': result['source'],
        'source_sha256': result['source_sha256'],
        'views_count': len(result['views']),
        'contracts_count': sum(map(len, result['views'].values())),
        'duplicate_occurrences_count': sum(map(len, result['duplicate_occurrences'].values())),
        'shards': shards,
    }
    output.write_bytes(encoded(index))
    print(f'Snapshot: {index["views_count"]} changed views, {len(shards)} shards; {output}')


if __name__ == '__main__':
    main()
