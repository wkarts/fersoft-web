"""Both ZIP channels replace one canonical identity without changing reviewed UI files."""
from __future__ import annotations

import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile
import unittest
import zipfile

ROOT = Path(__file__).resolve().parents[2]
BETA_PRODUCER = ROOT / "scripts/ui/build-preview.py"
STABLE_PRODUCER = ROOT / "scripts/release/identity.py"
CANONICAL = "release-build-info.json"


class ChannelOverlayTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="fersoft-channel-overlay-")
        self.directory = Path(self.temporary.name)
        self.repository = self.directory / "repository"
        self.repository.mkdir()
        self.git("init", "--initial-branch=develop")
        self.git("config", "user.name", "Channel Overlay Test")
        self.git("config", "user.email", "overlay@example.test")
        producer = self.repository / "scripts/ui/build-preview.py"
        producer.parent.mkdir(parents=True)
        shutil.copyfile(BETA_PRODUCER, producer)
        view = self.repository / "resources/views/reviewed.blade.php"
        view.parent.mkdir(parents=True)
        view.write_text("KEEP_REVIEWED_UI", encoding="utf-8")
        asset = self.repository / "public/build/assets/reviewed.css"
        asset.parent.mkdir(parents=True)
        asset.write_text("/* KEEP_CURRENT_ASSETS */", encoding="utf-8")
        # Real checkouts can contain tracked metadata from earlier packages.
        stale = json.dumps({"schema_version": 1, "version": "5.0.237", "tag": "v5.0.237",
                            "git_commit_sha": "a" * 40, "release_channel": "stable"})
        for name in (CANONICAL, "public/" + CANONICAL, "ui-build-info.json", "public/ui-build-info.json"):
            (self.repository / name).write_text(stale, encoding="utf-8")
        self.git("add", ".")
        self.git("commit", "-m", "reviewed beta fixture")
        self.head = self.git("rev-parse", "HEAD")
        self.git("tag", "v5.0.237")
        result = subprocess.run([sys.executable, str(producer)], cwd=self.repository, check=False,
                                text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=15,
                                env={**os.environ, "GITHUB_REF_NAME": "develop", "UI_BETA_VERSION": "v5.0.238-beta.57",
                                     "UI_BETA_BASE": "v5.0.237", "GITHUB_RUN_NUMBER": "57"})
        self.assertEqual(0, result.returncode, result.stderr)
        self.beta_zip = self.repository / "dist/fersoft-web-ui-continuous.zip"
        self.installation = self.directory / "installation"
        self.installation.mkdir()

    def tearDown(self):
        self.temporary.cleanup()

    def git(self, *arguments):
        return subprocess.check_output(["git", *arguments], cwd=self.repository, text=True,
                                       stderr=subprocess.PIPE, timeout=10).strip()

    def test_beta_zip_has_one_current_canonical_identity_and_preserves_ui_inputs(self):
        with zipfile.ZipFile(self.beta_zip) as package:
            canonical = json.loads(package.read(CANONICAL))
            legacy = json.loads(package.read("ui-build-info.json"))
            self.assertEqual(legacy, {key: value for key, value in canonical.items() if key not in {"schema_version", "tag"}})
            self.assertEqual(1, canonical["schema_version"])
            self.assertEqual("ui-continuous", canonical["tag"])
            self.assertEqual("v5.0.238-beta.57", canonical["version"])
            self.assertEqual("v5.0.237", canonical["base_stable_tag"])
            self.assertEqual(self.head, canonical["git_commit_sha"])
            for name in (CANONICAL, "public/" + CANONICAL, "ui-build-info.json", "public/ui-build-info.json"):
                self.assertEqual(1, package.namelist().count(name), name)
            self.assertEqual(package.read(CANONICAL), package.read("public/" + CANONICAL))
            self.assertEqual(b"KEEP_REVIEWED_UI", package.read("resources/views/reviewed.blade.php"))
            self.assertEqual(b"/* KEEP_CURRENT_ASSETS */", package.read("public/build/assets/reviewed.css"))
            self.assertFalse(any(".git" in Path(name).parts for name in package.namelist()))

    def test_beta_zip_overwrites_both_old_stable_markers_without_git(self):
        (self.installation / "public").mkdir()
        for name in (CANONICAL, "public/" + CANONICAL):
            (self.installation / name).write_text('{"version":"5.0.237","release_channel":"stable"}', encoding="utf-8")
        with zipfile.ZipFile(self.beta_zip) as package:
            package.extractall(self.installation)

        canonical = json.loads((self.installation / CANONICAL).read_text(encoding="utf-8"))
        self.assertEqual("v5.0.238-beta.57", canonical["version"])
        self.assertEqual("beta-continuous", canonical["release_channel"])
        self.assertEqual(canonical, json.loads((self.installation / "public" / CANONICAL).read_text(encoding="utf-8")))
        self.assertFalse((self.installation / ".git").exists())

    def test_stable_zip_replaces_beta_canonical_marker_even_with_leftover_legacy_ui_files(self):
        with zipfile.ZipFile(self.beta_zip) as package:
            package.extractall(self.installation)
        self.git("branch", "main", self.head)
        self.git("tag", "v5.0.238")
        result = subprocess.run([sys.executable, str(STABLE_PRODUCER), "write", "--repository", str(self.repository),
                                 "--version", "v5.0.238", "--commit", self.head], check=False, text=True,
                                stdout=subprocess.PIPE, stderr=subprocess.PIPE, timeout=15)
        self.assertEqual(0, result.returncode, result.stderr)
        stable_zip = self.directory / "stable.zip"
        with zipfile.ZipFile(stable_zip, "w") as package:
            package.write(self.repository / CANONICAL, CANONICAL)
        with zipfile.ZipFile(stable_zip) as package:
            package.extractall(self.installation)

        canonical = json.loads((self.installation / CANONICAL).read_text(encoding="utf-8"))
        self.assertEqual("5.0.238", canonical["version"])
        self.assertEqual("v5.0.238", canonical["tag"])
        self.assertEqual("stable", canonical["release_channel"])
        self.assertEqual(self.head, canonical["git_commit_sha"])
        leftover_ui = json.loads((self.installation / "ui-build-info.json").read_text(encoding="utf-8"))
        self.assertEqual("beta-continuous", leftover_ui["release_channel"])
        self.assertFalse((self.installation / ".git").exists())


if __name__ == "__main__":
    unittest.main()
