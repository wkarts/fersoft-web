"""Identidade de beta chega ao checkout Git sem depender do ZIP ou de tags locais."""
from __future__ import annotations

import json
import os
from pathlib import Path
import runpy
import subprocess
import sys
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "scripts/ui/release-identity.py"
identity = runpy.run_path(str(SCRIPT))["identity"]


class ReleaseIdentityTest(unittest.TestCase):
    def test_identity_distinguishes_beta_from_stable_base_and_commit(self):
        self.assertEqual({
            "schema_version": 1,
            "version": "v5.0.236-beta.51",
            "base_stable_tag": "v5.0.235",
            "git_commit_sha": "b1d10e6edf1e8abd19057680bf2a4107ba263485",
            "release_channel": "beta-continuous",
            "build_number": 51,
        }, identity("v5.0.236-beta.51", "v5.0.235", "b1d10e6edf1e8abd19057680bf2a4107ba263485", "51"))

    def test_invalid_or_inconsistent_inputs_are_rejected(self):
        valid = ["v5.0.236-beta.51", "v5.0.235", "a" * 40, "51"]
        for index, value in [
            (0, "v5.0.236-beta.52"), (0, "v5.1.236-beta.51"),
            (0, "v5.0.235-beta.51"), (0, "v05.0.236-beta.51"),
            (0, "v5.0.236-beta.51\nmalformed"),
            (1, "v5.0.235-rc.1"), (1, "$(touch injected)"),
            (2, "a" * 7), (2, "HEAD"), (2, "a" * 40 + "\n"),
            (3, "0"), (3, "051"), (3, "51; touch injected"),
        ]:
            with self.subTest(index=index, value=value):
                args = valid.copy()
                args[index] = value
                with self.assertRaises(ValueError):
                    identity(*args)


class ReleaseIdentityGitTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix="release-identity-")
        self.addCleanup(self.temporary.cleanup)
        self.directory = Path(self.temporary.name)
        self.source = self.directory / "source"
        self.git(self.directory, "init", "--initial-branch=develop", str(self.source))
        self.git(self.source, "config", "user.name", "Identity Test")
        self.git(self.source, "config", "user.email", "identity@example.test")
        (self.source / "version.txt").write_text("stable\n")
        self.git(self.source, "add", ".")
        self.git(self.source, "commit", "-m", "stable")
        self.git(self.source, "tag", "v5.0.235")
        (self.source / "version.txt").write_text("beta\n")
        self.git(self.source, "commit", "-am", "beta")
        self.commit = self.git(self.source, "rev-parse", "HEAD")

    def git(self, directory, *args):
        return subprocess.check_output(["git", *args], cwd=directory, text=True, stderr=subprocess.PIPE).strip()

    def run_script(self, **changes):
        env = os.environ.copy()
        env.update({"UI_BETA_VERSION": "v5.0.236-beta.51", "UI_BETA_BASE": "v5.0.235",
                    "GITHUB_SHA": self.commit, "GITHUB_RUN_NUMBER": "51"})
        env.update(changes)
        return subprocess.run([sys.executable, str(SCRIPT), "--repository", str(self.source)],
                              env=env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)

    def test_cli_validates_checkout_and_stable_tag_before_emitting_json(self):
        result = self.run_script()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(self.commit, json.loads(result.stdout)["git_commit_sha"])
        for changes in [{"GITHUB_SHA": "f" * 40}, {"UI_BETA_VERSION": "v5.0.237-beta.51", "UI_BETA_BASE": "v5.0.236"}]:
            with self.subTest(changes=changes):
                result = self.run_script(**changes)
                self.assertNotEqual(0, result.returncode)
                self.assertEqual("", result.stdout)

    def test_cli_arguments_are_supported_and_invalid_environment_is_not_evaluated(self):
        env = os.environ.copy()
        env.update({"UI_BETA_VERSION": "$(touch injected)", "UI_BETA_BASE": "invalid",
                    "GITHUB_SHA": "HEAD", "GITHUB_RUN_NUMBER": "0"})
        result = subprocess.run([
            sys.executable, str(SCRIPT), "--repository", str(self.source),
            "--beta-version", "v5.0.236-beta.51", "--base-stable-tag", "v5.0.235",
            "--commit", self.commit, "--build-number", "51",
        ], cwd=self.directory, env=env, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual("v5.0.236-beta.51", json.loads(result.stdout)["version"])
        self.assertFalse((self.directory / "injected").exists())

    def test_annotated_identity_survives_shallow_fetch_into_updater_private_ref(self):
        generated = self.run_script()
        self.assertEqual(0, generated.returncode, generated.stderr)
        message = self.directory / "identity.json"
        message.write_text(generated.stdout)
        self.git(self.source, "tag", "-a", "-f", "--cleanup=verbatim", "-F", str(message), "ui-continuous", self.commit)
        client = self.directory / "client"
        self.git(self.directory, "init", str(client))
        self.git(client, "remote", "add", "origin", self.source.as_uri())
        ref = "refs/laravel-updater/targets/ui-continuous"
        self.git(client, "fetch", "--no-tags", "--depth=1", "origin", "+refs/tags/ui-continuous:" + ref)
        self.git(client, "checkout", "--detach", ref + "^{commit}")
        self.assertEqual("", self.git(client, "tag", "--list"))
        self.assertEqual(self.commit, self.git(client, "rev-parse", "HEAD"))
        metadata = json.loads(self.git(client, "for-each-ref", "--format=%(contents)", ref))
        self.assertEqual("v5.0.236-beta.51", metadata["version"])
        self.assertEqual("v5.0.235", metadata["base_stable_tag"])
        self.assertEqual(self.commit, metadata["git_commit_sha"])
        self.assertEqual(self.commit, self.git(client, "rev-parse", ref + "^{commit}"))


if __name__ == "__main__":
    unittest.main()
