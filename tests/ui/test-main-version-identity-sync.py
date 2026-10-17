"""Exercise the temporary identity backport merge against isolated Git DAGs."""
from __future__ import annotations

import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "scripts/ui/merge-main-version-identity.sh"
PROVENANCE = ROOT / "scripts/ui/version-identity-backport.json"
ALLOWED_PATHS = [
    "app/Services/AppVersionService.php",
    "app/Services/InstalledReleaseIdentity.php",
    "tests/Unit/AppVersionServiceIdentityTest.php",
    "tests/Unit/InstalledReleaseIdentityTest.php",
]
UI_PATH = "resources/frontend/App.vue"
BETA_PATH = ".github/workflows/ui-prerelease.yml"
STABLE_PATH = ".github/workflows/release.yml"
FUNCTIONAL_PATH = "app/Services/InvoiceService.php"


class MainVersionIdentitySyncTest(unittest.TestCase):
    def setUp(self):
        self.assertTrue(SCRIPT.is_file(), f"Missing merge script: {SCRIPT}")
        self.temporary = tempfile.TemporaryDirectory(prefix="fersoft-identity-sync-")
        self.addCleanup(self.temporary.cleanup)
        self.directory = Path(self.temporary.name)
        self.repository = self.directory / "checkout"
        self.repository.mkdir()
        self.provenance_file = self.directory / "provenance.json"
        self.environment = {
            key: value for key, value in os.environ.items()
            if not key.startswith("GIT_")
        }
        self.environment.update({
            "GIT_CONFIG_NOSYSTEM": "1",
            "GIT_CONFIG_GLOBAL": os.devnull,
            "GIT_TERMINAL_PROMPT": "0",
            "GIT_MERGE_AUTOEDIT": "no",
            "LC_ALL": "C",
        })
        self.git("init", "--quiet", "--initial-branch=main")
        self.git("config", "user.name", "Identity Sync Fixture")
        self.git("config", "user.email", "identity-sync@example.test")
        self.git("config", "commit.gpgsign", "false")
        self.git("config", "core.autocrlf", "false")
        hooks = self.directory / "empty-hooks"
        hooks.mkdir()
        self.git("config", "core.hooksPath", str(hooks))

    def command(self, arguments, check=True):
        result = subprocess.run(
            arguments, cwd=self.repository, env=self.environment,
            text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
            timeout=30,
        )
        if check:
            self.assertEqual(0, result.returncode,
                             f"{arguments!r}\n{result.stdout}\n{result.stderr}")
        return result

    def git(self, *arguments):
        return self.command(["git", *arguments]).stdout.strip()

    def write(self, path, content):
        target = self.repository / path
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(content, encoding="utf-8")

    def read(self, path):
        return (self.repository / path).read_text(encoding="utf-8")

    def commit(self, message):
        self.git("add", "--all")
        self.git("commit", "--quiet", "-m", message)
        return self.git("rev-parse", "HEAD")

    @staticmethod
    def identity_content(path, identity, extension="unchanged"):
        # Keep the independently editable region outside Git's conflict context.
        lines = ["<?php", f"// fixture: {path}", f"// identity: {identity}"]
        lines.extend(f"// upper unchanged line {number}" for number in range(30))
        lines.append(f"// extension: {extension}")
        lines.extend(f"// lower unchanged line {number}" for number in range(30))
        return "\n".join(lines) + "\n"

    def fixture(self, independent=False, overlap=False, extra_conflict=False,
                conflict_paths=None):
        conflict_paths = ALLOWED_PATHS if conflict_paths is None else conflict_paths
        # Historically only the service existed on main: the helper and its two
        # tests were introduced independently on both sides (real add/add).
        path = ALLOWED_PATHS[0]
        self.write(path, self.identity_content(path, "legacy"))
        self.write(UI_PATH, "<template>Shared classic interface</template>\n")
        self.write(BETA_PATH, "name: Initial beta\nrelease_channel: disabled\n")
        self.write(STABLE_PATH, "name: Stable release\nidentity: legacy\n")
        self.write(FUNCTIONAL_PATH, "<?php\n// invoice rule: original\n")
        self.base = self.commit("Shared historical merge base")

        self.git("checkout", "--quiet", "-b", "develop")
        for path in ALLOWED_PATHS:
            self.write(path, self.identity_content(path, "shared-fix"))
        self.shared = self.commit("Shared identity fix on develop")
        self.write(UI_PATH, "<template>Develop Vue interface remains here</template>\n")
        self.write(BETA_PATH, "name: Develop beta\nbranch: develop\nrelease_channel: beta\n")
        if independent or overlap:
            path = ALLOWED_PATHS[-1]
            self.write(path, self.identity_content(path, "shared-fix", "develop-only"))
        if extra_conflict:
            self.write(FUNCTIONAL_PATH, "<?php\n// invoice rule: develop-only\n")
        self.develop = self.commit("Preserve develop interface and beta channel")
        self.develop_ui = self.read(UI_PATH)
        self.develop_beta = self.read(BETA_PATH)

        self.git("checkout", "--quiet", "main")
        self.expected_identity = {}
        for path in ALLOWED_PATHS:
            identity = "main-stable-contract" if path in conflict_paths else "shared-fix"
            extension = "stable-overlap" if overlap and path == ALLOWED_PATHS[-1] else "unchanged"
            content = self.identity_content(path, identity, extension)
            self.write(path, content)
            expected_extension = "develop-only" if independent and path == ALLOWED_PATHS[-1] else extension
            self.expected_identity[path] = self.identity_content(path, identity, expected_extension)
        self.stable_workflow = "name: Stable release\nbranch: main\nidentity: protected\n"
        self.write(STABLE_PATH, self.stable_workflow)
        if extra_conflict:
            self.write(FUNCTIONAL_PATH, "<?php\n// invoice rule: stable-only\n")
        self.main = self.commit("Evolve stable identity without promoting develop UI")
        self.git("checkout", "--quiet", "develop")
        self.assertEqual(self.base, self.git("merge-base", "HEAD", "main"))
        self.write_provenance()

    def write_provenance(self, **overrides):
        data = {
            "schema_version": 1,
            "merge_base": self.base,
            "shared_fix_commit": self.shared,
            "paths": list(ALLOWED_PATHS),
        }
        data.update(overrides)
        self.provenance_file.write_text(json.dumps(data, indent=2) + "\n", encoding="utf-8")
        self.assertNotIn(self.repository, self.provenance_file.parents)

    def assert_pristine(self):
        self.assertEqual("", self.git("status", "--porcelain=v1", "--untracked-files=all"))
        self.assertEqual("", self.git("ls-files", "--unmerged"))
        for state in ["MERGE_HEAD", "CHERRY_PICK_HEAD", "REVERT_HEAD"]:
            result = self.command(["git", "rev-parse", "--quiet", "--verify", state], check=False)
            self.assertNotEqual(0, result.returncode, f"Unexpected pending {state}")

    def assert_normal_merge_conflicts(self, expected):
        result = self.command(["git", "merge", "--no-ff", "--no-commit", "main"], check=False)
        try:
            self.assertEqual(1, result.returncode, result.stdout + result.stderr)
            actual = self.git("diff", "--name-only", "--diff-filter=U").splitlines()
            self.assertEqual(sorted(expected), sorted(actual))
        finally:
            self.git("merge", "--abort")
        self.assert_pristine()

    def invoke(self, message="Merge main identity into develop"):
        return self.command([
            "bash", str(SCRIPT), "main", message,
            "--provenance", str(self.provenance_file),
        ], check=False)

    def assert_merge(self, result, previous_head, target_head, message):
        self.assertEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertEqual([previous_head, target_head], self.git("show", "-s", "--format=%P", "HEAD").split())
        self.assertEqual(message, self.git("show", "-s", "--format=%s", "HEAD"))
        self.assert_pristine()

    def assert_channels_preserved(self):
        self.assertEqual(self.develop_ui, self.read(UI_PATH))
        self.assertEqual(self.develop_beta, self.read(BETA_PATH))
        self.assertEqual(self.stable_workflow, self.read(STABLE_PATH))
        for path, expected in self.expected_identity.items():
            self.assertEqual(expected, self.read(path), path)

    def assert_rejected_without_commit(self):
        head = self.git("rev-parse", "HEAD")
        tree = self.git("write-tree")
        reachable_commits = self.git("rev-list", "--all", "--count")
        result = self.invoke()
        self.assertNotEqual(0, result.returncode, result.stdout + result.stderr)
        self.assertEqual(head, self.git("rev-parse", "HEAD"))
        self.assertEqual(tree, self.git("write-tree"))
        self.assertEqual(reachable_commits, self.git("rev-list", "--all", "--count"))
        self.assert_pristine()
        return result

    def test_four_historical_conflicts_use_the_shared_fix_and_preserve_both_channels(self):
        self.fixture()
        self.assert_normal_merge_conflicts(ALLOWED_PATHS)
        message = "Merge main identity into develop"
        result = self.invoke(message)
        self.assert_merge(result, self.develop, self.main, message)
        self.assert_channels_preserved()
        self.assertEqual(sorted([*ALLOWED_PATHS, STABLE_PATH]),
                         sorted(self.git("diff", "--name-only", self.develop, "HEAD").splitlines()))

    def test_production_provenance_is_pinned_to_the_reviewed_history(self):
        self.assertEqual({
            "schema_version": 1,
            "merge_base": "35856404d648c5085b1fc8716dc34865bf5471c7",
            "shared_fix_commit": "f69e545206d960f0595fd9544dce3a96d422447b",
            "paths": ALLOWED_PATHS,
        }, json.loads(PROVENANCE.read_text(encoding="utf-8")))

    def test_independent_develop_edit_survives_the_functional_three_way_merge(self):
        self.fixture(independent=True)
        self.assert_normal_merge_conflicts(ALLOWED_PATHS)
        message = "Keep the independent develop extension"
        self.assert_merge(self.invoke(message), self.develop, self.main, message)
        self.assert_channels_preserved()

    def test_a_subset_of_the_four_paths_can_use_the_same_reviewed_base(self):
        self.fixture(conflict_paths=[ALLOWED_PATHS[0]])
        self.assert_normal_merge_conflicts([ALLOWED_PATHS[0]])
        message = "Merge the one remaining identity conflict"
        self.assert_merge(self.invoke(message), self.develop, self.main, message)
        self.assert_channels_preserved()

    def test_same_line_functional_conflict_aborts_the_entire_merge(self):
        self.fixture(overlap=True)
        self.assert_normal_merge_conflicts(ALLOWED_PATHS)
        result = self.assert_rejected_without_commit()
        self.assertIn("Functional three-way merge still conflicts", result.stderr)
        self.assertEqual(self.develop_ui, self.read(UI_PATH))
        self.assertEqual(self.develop_beta, self.read(BETA_PATH))

    def test_conflict_outside_the_allowlist_aborts_without_a_commit(self):
        self.fixture(extra_conflict=True)
        self.assert_normal_merge_conflicts([*ALLOWED_PATHS, FUNCTIONAL_PATH])
        result = self.assert_rejected_without_commit()
        self.assertIn("outside the historical identity allowlist", result.stderr)

    def test_provenance_cannot_widen_or_reorder_the_historical_allowlist(self):
        self.fixture(extra_conflict=True)
        for paths in [ALLOWED_PATHS + [FUNCTIONAL_PATH], list(reversed(ALLOWED_PATHS))]:
            with self.subTest(paths=paths):
                self.write_provenance(paths=paths)
                result = self.assert_rejected_without_commit()
                self.assertIn("allowlist must not be widened or reordered", result.stderr)

    def test_incorrect_historical_merge_base_aborts_without_a_commit(self):
        self.fixture()
        self.write_provenance(merge_base=self.shared)
        result = self.assert_rejected_without_commit()
        self.assertIn("Merge base differs", result.stderr)

    def test_shared_fix_must_be_an_ancestor_of_the_develop_head(self):
        self.fixture()
        ancestry = self.command(["git", "merge-base", "--is-ancestor", self.main, "HEAD"], check=False)
        self.assertEqual(1, ancestry.returncode)
        self.write_provenance(shared_fix_commit=self.main)
        result = self.assert_rejected_without_commit()
        self.assertIn("not an ancestor", result.stderr)

    def test_later_conflict_is_rejected_after_the_historical_merge_base_advances(self):
        self.fixture()
        self.assert_merge(self.invoke(), self.develop, self.main, "Merge main identity into develop")
        path = ALLOWED_PATHS[-1]
        self.write(path, self.identity_content(path, "develop-follow-up"))
        self.develop = self.commit("Develop edits identity after the historical sync")
        self.git("checkout", "--quiet", "main")
        self.write(path, self.identity_content(path, "stable-follow-up"))
        self.commit("Main edits the same identity after the historical sync")
        self.git("checkout", "--quiet", "develop")
        self.assertNotEqual(self.base, self.git("merge-base", "HEAD", "main"))
        self.assert_normal_merge_conflicts([path])
        result = self.assert_rejected_without_commit()
        self.assertIn("Merge base differs", result.stderr)

    def test_later_normal_merge_succeeds_even_with_the_old_provenance_base(self):
        self.fixture()
        self.assert_merge(self.invoke(), self.develop, self.main, "Merge main identity into develop")
        previous_head = self.git("rev-parse", "HEAD")
        self.git("checkout", "--quiet", "main")
        self.stable_workflow = "name: Stable release\nbranch: main\nidentity: protected-again\n"
        self.write(STABLE_PATH, self.stable_workflow)
        target = self.commit("Unrelated later stable workflow update")
        self.git("checkout", "--quiet", "develop")
        self.assertNotEqual(self.base, self.git("merge-base", "HEAD", "main"))
        message = "Normal main merge after the historical sync"
        self.assert_merge(self.invoke(message), previous_head, target, message)
        self.assert_channels_preserved()


if __name__ == "__main__":
    unittest.main()
