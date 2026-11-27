"""Build-only tests of actual neutral CSS transformation; no customer action."""
from pathlib import Path
import hashlib
import json
import runpy
import unittest

ROOT = Path(__file__).resolve().parents[2]
MODULE = runpy.run_path(str(ROOT / 'scripts/ci/tokenize-ui-neutral-styles.py'))
migrate = MODULE['migrate']
classic_source = MODULE['classic_source']


class NeutralStyleContract(unittest.TestCase):
    def test_classic_style_values_remain_exact_fallbacks(self):
        source = '<div id="customer" style="background: #FFF; color: #333 !important; border-color:#ddd">{{ $customer->name }}</div>'
        updated, count = migrate(source)
        self.assertEqual(3, count)
        self.assertEqual(source, classic_source(updated))
        self.assertIn('var(--ui-surface, #FFF)', updated)
        self.assertIn('var(--ui-ink, #333) !important', updated)
        self.assertIn('{{ $customer->name }}', updated)

    def test_semantic_colors_and_css_content_strings_are_not_rewritten(self):
        source = '<style>/* color:#000 */ .success {color:#12aa22;background:#ffeedd;} .danger {background:#ff0000;color:#fff;} .x:before {content:"color:#000;";}</style>'
        self.assertEqual((source, 0), migrate(source))

    def test_transformation_is_idempotent(self):
        original = '<style>.form{background:white;color:black;} @media(max-width:800px){.form{background-color:#f8f9fa;}}</style>'
        modified, count = migrate(original)
        self.assertGreater(count, 0)
        self.assertEqual((modified, 0), migrate(modified))
        self.assertEqual(original, classic_source(modified))

    def test_dynamic_blade_expressions_are_not_evaluated_or_modified(self):
        source = '<div style="color:{{ $condition ? \'#000\' : \'#333\' }}; background:{{ $row->color }}"></div>'
        self.assertEqual((source, 0), migrate(source))

    def test_all_generated_pages_keep_their_classic_markup_contract(self):
        manifest = json.loads((ROOT / 'tests/fixtures/ui/neutral-style-contracts.json').read_text())
        self.assertGreater(len(manifest['files']), 0)
        for name, record in manifest['files'].items():
            with self.subTest(page=name):
                content = (ROOT / name).read_text()
                self.assertEqual(record['classic_equivalent_sha256'], hashlib.sha256(classic_source(content).encode()).hexdigest())
                self.assertNotIn('/themes/', name)
                self.assertNotIn('relatorio_pdf', name)


if __name__ == '__main__':
    unittest.main()
