#!/usr/bin/env python3
"""Identidade verificável da beta preservada na mensagem da tag ui-continuous."""
from __future__ import annotations

import argparse
import json
import os
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
STABLE = re.compile(r"v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)")
BETA = re.compile(r"v(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)-beta\.([1-9][0-9]*)")


def identity(version: str, base: str, commit: str, build: str) -> dict:
    """Validar antes de produzir qualquer conteúdo destinado à anotação Git."""
    stable = STABLE.fullmatch(base)
    beta = BETA.fullmatch(version)
    if stable is None or len(base) > 80:
        raise ValueError("a base deve ser uma tag estável vMAJOR.MINOR.PATCH")
    if beta is None or len(version.removeprefix('v')) > 30:
        raise ValueError("a versão deve ter o formato vMAJOR.MINOR.PATCH-beta.N")
    if not re.fullmatch(r"[0-9a-f]{40}", commit):
        raise ValueError("o commit deve ser um SHA Git completo de 40 caracteres")
    if not re.fullmatch(r"[1-9][0-9]{0,18}", build):
        raise ValueError("o número do build deve ser um inteiro positivo")
    major, minor, patch = (int(part) for part in stable.groups())
    if tuple(int(part) for part in beta.groups()[:3]) != (major, minor, patch + 1):
        raise ValueError("a beta deve incrementar somente o patch da base estável")
    if beta.group(4) != build:
        raise ValueError("o número da beta deve corresponder ao número do build")

    return {
        "schema_version": 1,
        "version": version,
        "base_stable_tag": base,
        "git_commit_sha": commit,
        "release_channel": "beta-continuous",
        "build_number": int(build),
    }


def verify_repository(data: dict, repository: Path) -> None:
    def resolve(reference: str) -> str:
        result = subprocess.run(
            ["git", "rev-parse", "--verify", reference],
            cwd=repository, text=True, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
            check=False,
        )
        if result.returncode != 0:
            raise ValueError("o commit ou a tag estável não está disponível no checkout")
        return result.stdout.strip()

    if resolve("HEAD^{commit}") != data["git_commit_sha"]:
        raise ValueError("o commit informado não corresponde ao HEAD do checkout")
    # A base pode estar adiante da develop; não exigir ancestralidade no HEAD.
    resolve("refs/tags/" + data["base_stable_tag"] + "^{commit}")


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--beta-version", default=os.environ.get("UI_BETA_VERSION", ""))
    parser.add_argument("--base-stable-tag", default=os.environ.get("UI_BETA_BASE", ""))
    parser.add_argument("--commit", default=os.environ.get("GITHUB_SHA", ""))
    parser.add_argument("--build-number", default=os.environ.get("GITHUB_RUN_NUMBER", ""))
    parser.add_argument("--repository", type=Path, default=ROOT)
    args = parser.parse_args()
    try:
        data = identity(args.beta_version, args.base_stable_tag, args.commit, args.build_number)
        verify_repository(data, args.repository)
    except (ValueError, OSError) as error:
        parser.error(str(error))
    sys.stdout.write(json.dumps(data, ensure_ascii=False, separators=(",", ":")) + "\n")


if __name__ == "__main__":
    main()
