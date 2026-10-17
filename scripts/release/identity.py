#!/usr/bin/env python3
"""Generate and verify stable release identity before publishing a package."""
from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
import re
import stat
import subprocess
import tempfile
import zipfile

ROOT = Path(__file__).resolve().parents[2]
FILENAME = "release-build-info.json"
LIMIT = 8192
STABLE = re.compile(r"v?(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)")
SHA = re.compile(r"[a-f0-9]{40}")


def identity(version: str, commit: str) -> dict:
    if len(version.removeprefix("v")) > 30 or STABLE.fullmatch(version) is None:
        raise ValueError("stable version must be MAJOR.MINOR.PATCH, optionally prefixed with v")
    if SHA.fullmatch(commit) is None:
        raise ValueError("commit must be a complete lowercase 40-character Git SHA")
    normalized = version.removeprefix("v")
    return {"schema_version": 1, "version": normalized, "tag": "v" + normalized,
            "git_commit_sha": commit, "release_channel": "stable"}


def git(repository: Path, *arguments: str, required: bool = True) -> str | None:
    result = subprocess.run(["git", *arguments], cwd=repository, text=True,
                            stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                            timeout=10, check=False,
                            env={**os.environ, "GIT_NO_REPLACE_OBJECTS": "1", "GIT_TERMINAL_PROMPT": "0"})
    if result.returncode != 0:
        if required:
            raise ValueError("Git could not verify the release checkout or reference")
        return None
    return result.stdout.strip()


def verify_repository(data: dict, repository: Path, require_tag: bool = False) -> None:
    if git(repository, "rev-parse", "--verify", "HEAD^{commit}") != data["git_commit_sha"]:
        raise ValueError("release commit does not match the checkout HEAD")
    main = next((reference for reference in ("refs/remotes/origin/main", "refs/heads/main")
                 if git(repository, "rev-parse", "--verify", reference + "^{commit}", required=False)), None)
    if main is None:
        raise ValueError("main is unavailable; stable release source cannot be verified")
    git(repository, "merge-base", "--is-ancestor", data["git_commit_sha"], main)
    reference = "refs/tags/" + data["tag"]
    exists = git(repository, "show-ref", "--verify", reference, required=False) is not None
    if require_tag and not exists:
        raise ValueError("the stable release tag is missing")
    if exists and git(repository, "rev-parse", "--verify", reference + "^{commit}") != data["git_commit_sha"]:
        raise ValueError("stable tag points to a different commit")


def write_identity(data: dict, target: Path) -> None:
    target.parent.mkdir(parents=True, exist_ok=True)
    temporary = None
    try:
        with tempfile.NamedTemporaryFile("w", encoding="utf-8", dir=target.parent,
                                         prefix=".release-identity-", delete=False) as stream:
            temporary = Path(stream.name)
            json.dump(data, stream, ensure_ascii=False, indent=2)
            stream.write("\n")
        # ZIPs installed by another OS user must leave public identity readable
        # by the PHP worker; NamedTemporaryFile otherwise defaults to 0600.
        temporary.chmod(0o644)
        os.replace(temporary, target)
    finally:
        if temporary is not None:
            temporary.unlink(missing_ok=True)


def unique_object(items: list[tuple[str, object]]) -> dict:
    result = {}
    for key, value in items:
        if key in result:
            raise ValueError("release metadata contains duplicate fields")
        result[key] = value
    return result


def verify_archive(archive: Path, expected: dict) -> None:
    with zipfile.ZipFile(archive) as package:
        entries = [entry for entry in package.infolist() if entry.filename in (FILENAME, "./" + FILENAME)]
        if len(entries) != 1:
            raise ValueError("package must contain exactly one root release-build-info.json")
        entry = entries[0]
        if entry.file_size > LIMIT or stat.S_ISLNK(entry.external_attr >> 16):
            raise ValueError("packaged release metadata is invalid or oversized")
        with package.open(entry) as stream:
            content = stream.read(LIMIT + 1)
        if len(content) > LIMIT:
            raise ValueError("packaged release metadata is oversized")
        metadata = json.loads(content.decode("utf-8"), object_pairs_hook=unique_object)
        if metadata != expected or type(metadata.get("schema_version")) is not int:
            raise ValueError("package version, tag, channel or commit differs from the validated release")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("action", choices=("write", "verify"))
    parser.add_argument("--version", required=True)
    parser.add_argument("--commit", default=os.environ.get("GITHUB_SHA", ""))
    parser.add_argument("--repository", type=Path, default=ROOT)
    parser.add_argument("--output", type=Path, default=Path(FILENAME))
    parser.add_argument("--archive", type=Path)
    parser.add_argument("--require-tag", action="store_true")
    args = parser.parse_args()
    try:
        data = identity(args.version, args.commit)
        verify_repository(data, args.repository, args.require_tag)
        if args.action == "write":
            target = args.output if args.output.is_absolute() else args.repository / args.output
            write_identity(data, target)
        elif args.archive is None:
            raise ValueError("verify requires --archive")
        else:
            verify_archive(args.archive, data)
    except (ValueError, OSError, UnicodeError, subprocess.SubprocessError, zipfile.BadZipFile) as error:
        parser.error(str(error))
    print(json.dumps(data, ensure_ascii=False, separators=(",", ":")))


if __name__ == "__main__":
    main()
