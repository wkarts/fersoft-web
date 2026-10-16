"""Contract regression for operational Blade pages in the approved redesign.

The checked-in snapshot is extracted from the supplied develop ZIP. It records
functional HTML attributes only; classes, wrappers and layout can be rebuilt.
The ZIP is deliberately not needed in CI, which has no access to chat uploads.
"""
from html.parser import HTMLParser
from pathlib import Path
from collections import Counter
import json
import unittest


ROOT = Path(__file__).resolve().parents[2]
BASELINE = Path(__file__).with_name('develop-view-contracts.json')


class ContractParser(HTMLParser):
    def __init__(self):
        super().__init__()
        self.contracts = set()
        self.critical_counts = Counter()

    def add(self, key, value):
        self.contracts.add((key, value))
        if key in ('name', 'onclick', 'onchange', 'form:action', 'href'):
            self.critical_counts[(key, value)] += 1

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        for key in ('id', 'name', 'onclick', 'onchange', 'data-toggle',
                    'data-target', 'data-dismiss'):
            value = attrs.get(key)
            # kt_content was a duplicated Metronic wrapper, not a page hook.
            if value and not (key == 'id' and value == 'kt_content'):
                # Dropdown and tooltip decorators may become visible native
                # buttons without changing their underlying actions/routes.
                if key == 'data-toggle' and value in ('dropdown', 'tooltip'):
                    continue
                self.add(key, value)
        if tag == 'form':
            for key in ('action', 'method', 'enctype'):
                if attrs.get(key):
                    self.add('form:' + key, attrs[key])
        if tag == 'a' and attrs.get('href'):
            href = attrs['href']
            if href.startswith('/') or href.startswith('#modal'):
                self.add('href', href)


def extract_contracts(source):
    parser = ContractParser()
    parser.feed(source)
    return sorted([key, value] for key, value in parser.contracts)


def extract_critical_counts(source):
    parser = ContractParser()
    parser.feed(source)
    return parser.critical_counts


class DevelopViewContract(unittest.TestCase):
    def test_fields_actions_modal_hooks_and_routes_survive_visual_rebuild(self):
        manifest = json.loads(BASELINE.read_text(encoding='utf-8'))
        self.assertEqual('supplied develop ZIP', manifest['source'])
        self.assertGreaterEqual(len(manifest['views']), 200)
        for path, contract in manifest['views'].items():
            with self.subTest(view=path):
                current = ROOT / path
                self.assertTrue(current.is_file())
                actual = set(tuple(pair) for pair in extract_contracts(current.read_text(encoding='utf-8')))
                expected = set(tuple(pair) for pair in contract)
                missing = expected - actual
                self.assertFalse(missing, f'{path}: removed functional HTML contracts: {sorted(missing)}')
                counts = extract_critical_counts(current.read_text(encoding='utf-8'))
                for key, value, minimum in manifest['duplicate_occurrences'].get(path, []):
                    self.assertGreaterEqual(counts[(key, value)], minimum,
                        f'{path}: lost one of the original {key}="{value}" controls')


if __name__ == '__main__':
    unittest.main()
