#!/usr/bin/env python3
"""Pacote de pré-release da branch visual, sem segredos ou dados de cliente.

Inclui arquivos do checkout Git e assets Vite compilados; não segue symlinks.
Não usa .env e não empacota pastas de armazenamento, logs ou backups.
"""
from __future__ import annotations

import json
import os
import pathlib
import re
import subprocess
import zipfile
from datetime import datetime, timezone

ROOT = pathlib.Path(__file__).resolve().parents[2]
DIST = ROOT / "dist"
ARCHIVE = DIST / "fersoft-web-ui-continuous.zip"
ROOTS = {"app", "bootstrap", "config", "database", "public", "resources", "routes", "scripts"}
TOP_FILES = {"artisan", "composer.json", "composer.lock", "package.json", "package-lock.json", "vite.config.js"}
BLOCK_PREFIXES = (
    "public/files/", "public/relatorios/", "public/storage/",
    "storage/", "vendor/", "node_modules/", "bootstrap/cache/",
    "public/imagens_produtos/", "public/foto_usuario/", "public/retorno.ret",
)
BLOCK_EXT = {".bak", ".backup", ".key", ".pem", ".pfx", ".p12", ".sqlite", ".db", ".sql", ".log", ".ret"}


def git(*args: str) -> str:
    return subprocess.check_output(["git", *args], cwd=ROOT, text=True).strip()


def permitted(name: str) -> bool:
    p = pathlib.PurePosixPath(name)
    if p.is_absolute() or ".." in p.parts or not p.parts:
        return False
    if p.parts[0] not in ROOTS and name not in TOP_FILES:
        return False
    if name.startswith(BLOCK_PREFIXES):
        return False
    if any(seg == ".git" or seg.startswith(".env") for seg in p.parts):
        return False
    if p.suffix.lower() in BLOCK_EXT:
        return False
    if p.name.lower() in {"auth.json", "credentials.json", "id_rsa", "id_ed25519"}:
        return False
    return True


def main() -> None:
    sha = git("rev-parse", "HEAD")
    branch = os.environ.get("GITHUB_REF_NAME", git("branch", "--show-current"))
    if branch != "develop":
        raise SystemExit("ERRO: pacote exclusivo da develop; nenhuma release da main permitida.")

    beta_version = os.environ.get("UI_BETA_VERSION", "").strip()
    stable_base = os.environ.get("UI_BETA_BASE", "").strip()
    if not re.fullmatch(r"v\d+\.\d+\.\d+-beta\.[1-9]\d*", beta_version):
        raise SystemExit("ERRO: UI_BETA_VERSION ausente ou formato beta inválido.")
    if not re.fullmatch(r"v\d+\.\d+\.\d+", stable_base):
        raise SystemExit("ERRO: UI_BETA_BASE precisa identificar uma versão estável.")

    tracked = subprocess.check_output(["git", "ls-files", "-z"], cwd=ROOT).decode("utf-8").split("\0")
    entries = {name for name in tracked if name and permitted(name)}
    built = ROOT / "public/build"
    if built.is_dir():
        for item in built.rglob("*"):
            if item.is_file() and not item.is_symlink():
                rel = item.relative_to(ROOT).as_posix()
                if permitted(rel):
                    entries.add(rel)
    DIST.mkdir(parents=True, exist_ok=True)
    if not entries:
        raise SystemExit("ERRO: conjunto de arquivos do pacote está vazio.")

    metadata = {
        "release_channel": "beta-continuous",
        "release_type": "prerelease",
        "version": beta_version,
        "beta_version": beta_version,
        "branch": branch,
        "git_commit_sha": sha,
        "base_stable_tag": stable_base,
        "build_number": os.environ.get("GITHUB_RUN_NUMBER", "local"),
        "generated_utc": datetime.now(timezone.utc).isoformat(),
        "requires_manual_ui_validation": True,
        "stable_release": False,
    }

    with zipfile.ZipFile(ARCHIVE, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6) as archive:
        build_info = json.dumps(metadata, ensure_ascii=False, indent=2) + "\n"
        # Both channels replace the same marker when installed over an older ZIP.
        canonical_metadata = {**metadata, "schema_version": 1, "tag": "ui-continuous"}
        canonical_info = json.dumps(canonical_metadata, ensure_ascii=False, indent=2) + "\n"
        archive.writestr("release-build-info.json", canonical_info)
        archive.writestr("public/release-build-info.json", canonical_info)
        # Metadados verificáveis tanto no pacote quanto no endpoint estático do preview.
        archive.writestr("ui-build-info.json", build_info)
        archive.writestr("public/ui-build-info.json", build_info)
        for name in sorted(entries):
            if name in {"release-build-info.json", "public/release-build-info.json", "ui-build-info.json", "public/ui-build-info.json"}:
                continue
            file = ROOT / name
            if file.is_file() and not file.is_symlink():
                archive.write(file, arcname=name)
    print(json.dumps({**metadata, "path": str(ARCHIVE), "files": len(entries), "bytes": ARCHIVE.stat().st_size}, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()
