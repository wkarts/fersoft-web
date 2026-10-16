"""Contract regression for operational Blade pages in the approved redesign.

The checked-in snapshot is extracted from the supplied develop ZIP. It records
functional HTML attributes only; classes, wrappers and layout can be rebuilt.
The ZIP is deliberately not needed in CI, which has no access to chat uploads.
"""
from html.parser import HTMLParser
from pathlib import Path
from collections import Counter
import hashlib
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
        if key in ('name', 'onclick', 'onchange', 'onsubmit', 'oninput',
                   'onkeyup', 'onkeydown', 'onkeypress', 'form:action', 'href'):
            self.critical_counts[(key, value)] += 1

    def handle_starttag(self, tag, attributes):
        attrs = dict(attributes)
        for key in ('id', 'name', 'onclick', 'onchange', 'onsubmit', 'oninput',
                    'onkeyup', 'onkeydown', 'onkeypress', 'data-toggle',
                    'data-target', 'data-dismiss', 'data-wizard-type',
                    'data-wizard-clickable'):
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
    def test_theme_management_keeps_separate_authorized_area_and_existing_actions(self):
        personal = (ROOT / 'resources/views/themes/refined/studio/index.blade.php').read_text(encoding='utf-8')
        admin = (ROOT / 'resources/views/themes/refined/studio/admin.blade.php').read_text(encoding='utf-8')
        route_actions = (
            'ui.themes.store', 'ui.themes.update', 'ui.themes.duplicate',
            'ui.themes.publish', 'ui.themes.archive', 'ui.themes.grant',
            'ui.themes.company.default', 'ui.themes.company.search',
        )
        self.assertIn("route('ui.themes.select')", personal)
        self.assertIn("route('ui.themes.reset')", personal)
        self.assertIn("route('config.themes.index')", personal)
        for action in route_actions:
            self.assertIn("route('" + action + "'", admin)
            self.assertNotIn("route('" + action + "'", personal)
        self.assertIn("route('config.themes.index') }}?edit_theme=", admin)
        self.assertIn('@disabled(!$catalogReady)', personal)
        self.assertIn('@disabled(!$catalogReady)', admin)
        for menu in ('themes/refined/partials/sidebar.blade.php',
                     'default/menu_lateral.blade.php', 'default/menu_superior.blade.php'):
            source = (ROOT / 'resources/views' / menu).read_text(encoding='utf-8')
            super_section = source.index('Aparência do Login')
            theme_section = source.index('Temas da Interface')
            self.assertGreater(theme_section, super_section, menu)
            self.assertIn("route('config.themes.index')", source)

    def test_refined_theme_uses_studio_without_legacy_palette_dialog(self):
        layout = (ROOT / 'resources/views/themes/refined/layout.blade.php').read_text(encoding='utf-8')
        masthead = (ROOT / 'resources/views/themes/refined/partials/masthead.blade.php').read_text(encoding='utf-8')

        self.assertNotIn('id="modal-tema"', layout)
        self.assertNotIn('name="tema_menu"', layout)
        self.assertNotIn('name="tipo_menu"', layout)
        for old_palette in ('>Indigo</option>', '>Teal</option>', '>Amber</option>', '>Light</option>'):
            self.assertNotIn(old_palette, layout)
        self.assertIn('route(\'ui.themes.index\')', masthead)
        # Operational dialogs remain part of the refined layout.
        self.assertIn('id="modal-pesquisa"', layout)
        self.assertIn('id="modal-dinamic"', layout)

    def test_fields_actions_modal_hooks_and_routes_survive_visual_rebuild(self):
        index = json.loads(BASELINE.read_text(encoding='utf-8'))
        self.assertEqual('supplied develop ZIP', index['source'])
        self.assertGreaterEqual(index['views_count'], 200)
        seen = set()
        totals = Counter()
        for entry in index['shards']:
            shard_path = BASELINE.parent / entry['file']
            self.assertEqual(shard_path.parent, BASELINE.parent / 'contracts')
            raw = shard_path.read_bytes()
            self.assertEqual(hashlib.sha256(raw).hexdigest(), entry['sha256'], entry['file'])
            shard = json.loads(raw)
            self.assertEqual(len(shard['views']), entry['views'])
            self.assertEqual(sum(map(len, shard['views'].values())), entry['contracts'])
            self.assertEqual(sum(map(len, shard['duplicate_occurrences'].values())),
                             entry['duplicate_occurrences'])
            totals['views'] += entry['views']
            totals['contracts'] += entry['contracts']
            totals['duplicates'] += entry['duplicate_occurrences']
            for path, contract in shard['views'].items():
                with self.subTest(view=path):
                    self.assertNotIn(path, seen, 'View duplicated across contract shards')
                    seen.add(path)
                    current = ROOT / path
                    self.assertTrue(current.is_file())
                    source = current.read_text(encoding='utf-8')
                    actual = set(tuple(pair) for pair in extract_contracts(source))
                    expected = set(tuple(pair) for pair in contract)
                    missing = expected - actual
                    self.assertFalse(missing, f'{path}: removed functional HTML contracts: {sorted(missing)}')
                    counts = extract_critical_counts(source)
                    for key, value, minimum in shard['duplicate_occurrences'].get(path, []):
                        self.assertGreaterEqual(counts[(key, value)], minimum,
                            f'{path}: lost one of the original {key}="{value}" controls')
        self.assertEqual(totals['views'], index['views_count'])
        self.assertEqual(totals['contracts'], index['contracts_count'])
        self.assertEqual(totals['duplicates'], index['duplicate_occurrences_count'])


if __name__ == '__main__':
    unittest.main()
