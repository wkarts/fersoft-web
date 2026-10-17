"""Release invariants exercised against isolated, real Git repositories and ZIPs."""
from __future__ import annotations

import importlib.util
import json
from pathlib import Path
import stat
import subprocess
import sys
import tempfile
import unittest
import warnings
import zipfile

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "scripts/release/identity.py"
VERSION_SCRIPT = ROOT / "scripts/release/version.sh"
spec = importlib.util.spec_from_file_location("stable_release_identity", SCRIPT)
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)


class StableReleaseIdentityTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="fersoft-stable-release-")
        self.repository = Path(self.temporary.name)
        self.git("init", "--initial-branch=main")
        self.git("config", "user.name", "Release Identity Test")
        self.git("config", "user.email", "identity@example.test")
        self.head = self.commit("initial")
        self.data = release.identity("v3.4.5", self.head)

    def tearDown(self):
        self.temporary.cleanup()

    def git(self, *arguments):
        return subprocess.check_output(["git", *arguments], cwd=self.repository,
                                       text=True, stderr=subprocess.PIPE).strip()

    def commit(self, content):
        (self.repository / "app.txt").write_text(content, encoding="utf-8")
        self.git("add", "app.txt")
        self.git("commit", "-m", content)
        return self.git("rev-parse", "HEAD")

    def archive(self, data=None):
        target = self.repository / "package.zip"
        with zipfile.ZipFile(target, "w") as archive:
            archive.writestr(release.FILENAME, json.dumps(self.data if data is None else data))
        return target

    def version(self, *arguments, success=True):
        result = subprocess.run(["bash", str(VERSION_SCRIPT), *arguments], cwd=self.repository,
                                text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        if success:
            self.assertEqual(0, result.returncode, result.stderr)
        else:
            self.assertNotEqual(0, result.returncode, result.stdout)
        return result.stdout.strip()

    def test_identity_normalizes_only_the_stable_version_and_has_one_commit(self):
        self.assertEqual({"schema_version": 1, "version": "3.4.5", "tag": "v3.4.5",
                          "git_commit_sha": self.head, "release_channel": "stable"}, self.data)
        self.assertEqual(self.data, release.identity("3.4.5", self.head))
        for version in ["3.4.5-rc.1", "3.4.5-beta.2", "3.4.5+build.1", "ui-continuous",
                        "03.4.5", "3.04.5", "3.4.05", "3.4.5.6", "../3.4.5", "3.4.5\n",
                        "1" * 27 + ".0.0"]:
            with self.subTest(version=version), self.assertRaises(ValueError):
                release.identity(version, self.head)
        for sha in ["abc1234", "g" * 40, "A" * 40, self.head + "\n"]:
            with self.subTest(sha=sha), self.assertRaises(ValueError):
                release.identity("3.4.5", sha)

    def test_repository_rejects_sha_that_is_not_the_checkout_head(self):
        stale = self.head
        self.commit("new code")
        with self.assertRaisesRegex(ValueError, "checkout HEAD"):
            release.verify_repository(release.identity("3.4.5", stale), self.repository)

    def test_repository_rejects_tag_pointing_at_another_commit(self):
        self.git("tag", "v3.4.5")
        head = self.commit("new code")
        with self.assertRaisesRegex(ValueError, "different commit"):
            release.verify_repository(release.identity("3.4.5", head), self.repository)

    def test_repository_accepts_an_annotated_tag_at_the_validated_commit(self):
        self.git("tag", "-a", "v3.4.5", "-m", "Stable release")
        release.verify_repository(self.data, self.repository, require_tag=True)

    def test_repository_requires_the_published_tag_when_requested(self):
        with self.assertRaisesRegex(ValueError, "tag is missing"):
            release.verify_repository(self.data, self.repository, require_tag=True)
        release.verify_repository(self.data, self.repository)

    def test_release_source_on_an_unmerged_branch_is_rejected(self):
        self.git("checkout", "-b", "develop")
        sha = self.commit("development only")
        with self.assertRaises(ValueError):
            release.verify_repository(release.identity("3.4.5", sha), self.repository)
        self.version("auto", success=False)

    def test_archive_must_contain_the_exact_validated_version_tag_and_sha(self):
        release.verify_archive(self.archive(), self.data)
        for mutation in [{"version": "3.4.4"}, {"tag": "v3.4.4"},
                         {"git_commit_sha": "a" * 40}, {"release_channel": "beta"},
                         {"schema_version": True}, {"schema_version": 2},
                         {"unexpected_metadata": "must not be silently accepted"}]:
            with self.subTest(mutation=mutation), self.assertRaises(ValueError):
                release.verify_archive(self.archive({**self.data, **mutation}), self.data)

    def test_archive_rejects_missing_duplicate_oversized_or_symlink_metadata(self):
        target = self.repository / "invalid.zip"
        for mode in ["missing", "duplicate", "oversized", "symlink", "bad-json", "duplicate-key"]:
            with self.subTest(mode=mode):
                with zipfile.ZipFile(target, "w") as archive, warnings.catch_warnings():
                    warnings.simplefilter("ignore", UserWarning)
                    content = json.dumps(self.data)
                    if mode == "missing":
                        archive.writestr("app.txt", "code")
                    elif mode == "duplicate":
                        archive.writestr(release.FILENAME, content)
                        archive.writestr("./" + release.FILENAME, content)
                    elif mode == "oversized":
                        archive.writestr(release.FILENAME, "x" * (release.LIMIT + 1))
                    elif mode == "symlink":
                        entry = zipfile.ZipInfo(release.FILENAME)
                        entry.external_attr = (stat.S_IFLNK | 0o777) << 16
                        archive.writestr(entry, "outside.json")
                    elif mode == "bad-json":
                        archive.writestr(release.FILENAME, "{broken")
                    else:
                        archive.writestr(release.FILENAME, content[:-1] + ',"version":"3.4.4"}')
                with self.assertRaises(ValueError):
                    release.verify_archive(target, self.data)

    def test_cli_writes_then_verifies_the_same_identity_in_the_packaged_zip(self):
        arguments = [sys.executable, str(SCRIPT)]
        shared = ["--version", "v3.4.5", "--commit", self.head, "--repository", str(self.repository)]
        subprocess.run([*arguments, "write", *shared], check=True, capture_output=True, text=True)
        metadata = json.loads((self.repository / release.FILENAME).read_text())
        self.assertEqual(self.data, metadata)
        self.assertEqual(0o644, stat.S_IMODE((self.repository / release.FILENAME).stat().st_mode))
        archive = self.archive(metadata)
        subprocess.run([*arguments, "verify", *shared, "--archive", str(archive)],
                       check=True, capture_output=True, text=True)
        self.git("tag", "v3.4.5")
        subprocess.run([*arguments, "verify", *shared, "--archive", str(archive), "--require-tag"],
                       check=True, capture_output=True, text=True)

    def test_next_patch_ignores_rc_beta_and_stable_tags_outside_main(self):
        self.git("tag", "v3.4.5")
        self.git("tag", "v900.0.0-rc.1")
        self.git("tag", "v901.0.0-beta.5")
        self.git("tag", "v999.0.0.8")
        self.git("tag", "v999.00.0")
        self.git("checkout", "-b", "unmerged")
        self.commit("another branch")
        self.git("tag", "v999.0.0")
        self.git("checkout", "main")
        self.assertEqual("v3.4.5", self.version("latest"))
        self.assertEqual("v3.4.6", self.version("auto", "patch"))
        self.assertEqual("v3.5.0", self.version("auto", "minor"))
        self.assertEqual("v4.0.0", self.version("auto", "major"))

    def test_version_collision_never_reuses_or_moves_an_existing_tag(self):
        self.git("tag", "v3.4.5")
        self.git("checkout", "-b", "unmerged")
        self.commit("occupied version")
        self.git("tag", "v3.4.6")
        before = self.git("rev-parse", "v3.4.6")
        self.git("checkout", "main")
        self.assertEqual("v3.4.7", self.version("auto", "patch"))
        self.assertEqual(before, self.git("rev-parse", "v3.4.6"))

    def test_rc_inputs_stay_prereleases_and_never_become_stable_metadata(self):
        self.git("tag", "v3.4.5")
        self.assertEqual("v3.4.6-rc.2", self.version("auto", "patch", "", "true", "2"))
        self.assertEqual("v3.4.8-rc.2", self.version("manual", "patch", "3.4.8", "true", "2"))
        self.assertEqual("v3.4.8-rc.7", self.version("manual", "patch", "3.4.8-rc.7", "true", "2"))
        self.version("manual", "patch", "3.4.8-beta.1", "false", success=False)
        self.version("auto", "patch", "", "true", "01", success=False)
        with self.assertRaises(ValueError):
            release.identity("v3.4.6-rc.2", self.head)


class StableReleaseWorkflowContractTest(unittest.TestCase):
    def test_stable_publication_requires_the_identity_gate_on_main(self):
        workflow = (ROOT / ".github/workflows/release.yml").read_text()
        self.assertRegex(workflow, r"release-identity:\n\s+if: github.ref == 'refs/heads/main'\n\s+uses: ./\.github/workflows/release-identity.yml")
        self.assertRegex(workflow, r"full-validation:\n\s+if: github.ref == 'refs/heads/main'\n\s+needs: release-identity")
        self.assertIn("needs.release-identity.result == 'failure'", workflow)
        self.assertIn("target_commitish: ${{ github.sha }}", workflow)

    def test_metadata_and_archive_are_verified_before_publishing_the_release(self):
        workflow = (ROOT / ".github/workflows/release.yml").read_text()
        stages = ["identity.py write", 'zip -r "$artifact_name"', "identity.py verify",
                  "uses: actions/upload-artifact@v4", 'git tag "$RELEASE_VERSION" "$GITHUB_SHA"',
                  'git push origin "refs/tags/$RELEASE_VERSION"', "--require-tag", "uses: softprops/action-gh-release@v2"]
        positions = [workflow.index(stage) for stage in stages]
        self.assertEqual(sorted(positions), positions)
        self.assertNotIn('git push --force origin "refs/tags/$RELEASE_VERSION"', workflow)


if __name__ == "__main__":
    unittest.main()
