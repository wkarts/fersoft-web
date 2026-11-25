import importlib.util
from pathlib import Path
import subprocess
import tempfile
import unittest


SCRIPT = Path(__file__).resolve().parents[1] / 'preview-port.py'
spec = importlib.util.spec_from_file_location('preview_port', SCRIPT)
port = importlib.util.module_from_spec(spec)
spec.loader.exec_module(port)


class PortTest(unittest.TestCase):
    def test_class_can_follow_an_unchanged_tag_next_to_new_business_fields(self):
        old = '<div class="card">\n<input name="old">\n'
        visual = old.replace('card', 'card fer-form')
        incoming = old.replace('old', 'new')
        self.assertEqual(port.resolve_hunk(visual, old, incoming), incoming.replace('card', 'card fer-form'))

    def test_conflicting_anchors_or_behavior_require_review(self):
        old = '<div class="card">\n'
        styled = old.replace('card', 'card fer-page')
        for dev, incoming in [
            (styled, old * 2), (styled, old.replace('div', 'section')),
            (styled, ''), (styled.replace('fer-page', 'hidden'), old),
            (styled.replace('class=', 'onclick='), old),
            (styled, old.replace('card', 'card shadow')),
        ]:
            with self.subTest(develop=dev, incoming=incoming):
                self.assertIsNone(port.resolve_hunk(dev, old, incoming))

    def test_preview_writes_only_artifacts_and_keeps_conflicting_controller_unresolved(self):
        with tempfile.TemporaryDirectory() as td:
            root = Path(td, 'repo')
            root.mkdir()
            def git(*args):
                return subprocess.check_output(['git', '-C', root, *args]).strip()
            git('init', '-q', '-b', 'main')
            git('config', 'user.name', 'Test')
            git('config', 'user.email', 'test@localhost')
            view = root / 'resources/views/form.blade.php'
            view.parent.mkdir(parents=True)
            view.write_text('<div class="card">\n<input name="old">\n</div>\n')
            controller = root / 'Controller.php'
            controller.write_text('old\n')
            git('add', '.')
            git('commit', '-qm', 'base')
            git('switch', '-qc', 'develop')
            view.write_text(view.read_text().replace('card','card fer-form'))
            controller.write_text('develop\n')
            git('commit', '-qam', 'visual')
            git('switch', '-q', 'main')
            view.write_text(view.read_text().replace('old','new'))
            controller.write_text('main\n')
            git('commit', '-qam', 'business')
            refs = git('show-ref')
            output = Path(td, 'artifacts')
            port.preview(root, 'develop', 'main', output)
            patch = (output / 'classes-visuais.patch').read_text()
            self.assertIn('name="new"', patch)
            self.assertIn('card fer-form', patch)
            self.assertNotIn('Controller.php', patch)
            self.assertIn('Conflitos para revisão manual: 1', (output / 'resumo.md').read_text())
            self.assertEqual(refs, git('show-ref'))
            self.assertEqual(b'', git('status', '--porcelain'))
            port.preview(root, 'main', 'main', output)
            self.assertFalse((output / 'classes-visuais.patch').exists())


if __name__ == '__main__':
    unittest.main()
