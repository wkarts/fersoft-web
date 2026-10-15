#!/usr/bin/env python3
"""Read-only-first analyzer and copy sanitizer for MySQL/MariaDB SQL dumps.

The tool never connects to a database. It scans INSERT rows as a stream, writes
reports without row contents, and only creates a separate ZIP when asked.
"""
from __future__ import annotations

import argparse
import collections
import dataclasses
import gzip
import hashlib
import hmac
import json
import os
import re
import sqlite3
import sys
import tempfile
import zipfile
from pathlib import Path
from typing import BinaryIO, Iterable, Iterator, Optional

VERSION = "1.0.0"
DEFAULT_MAX_UNCOMPRESSED = 20 * 1024**3
DEFAULT_MAX_ROW = 64 * 1024**2
HEADER_RE = re.compile(
    rb"^\s*INSERT(?:\s+IGNORE)?\s+INTO\s+(?P<table>\x60[^\x60]+\x60|[^\s(]+)\s*"
    rb"\((?P<columns>.*?)\)\s*VALUES\b",
    re.IGNORECASE,
)
DATA_URI_RE = re.compile(r"data:image/[a-z0-9.+-]+;base64,", re.IGNORECASE)
BASE64_RE = re.compile(r"^[A-Za-z0-9+/\r\n]+={0,2}$")
IMAGE_PREFIXES = ("/9j/", "iVBORw0KGgo", "R0lGOD", "UklGR")
BINARY_KEYS = {
    "base64", "image_base64", "imagem_base64", "photo_base64", "foto_base64",
    "snapshot_base64", "file_base64", "jpeg_base64", "jpg_base64", "png_base64",
    "image_data_url", "imagem_data_url", "data_url", "image_src", "imagem_src",
    "image", "imagem", "photo", "foto",
}
QR_CONTAINER_KEYS = {"qr", "qrcode", "qr_code", "qrcode_data", "pairing_qr"}
QR_SECRET_KEYS = {"code", "base64", "data", "image", "value", "qr", "qrcode", "qr_code"}


class ToolError(Exception):
    pass


@dataclasses.dataclass
class ColumnStats:
    value_bytes: int = 0
    non_null: int = 0
    max_bytes: int = 0
    base64_candidates: int = 0
    base64_candidate_bytes: int = 0
    data_uris: int = 0
    qr_secrets: int = 0


@dataclasses.dataclass
class TableStats:
    rows: int = 0
    sql_bytes: int = 0
    columns: dict[str, ColumnStats] = dataclasses.field(default_factory=dict)
    min_time: Optional[str] = None
    max_time: Optional[str] = None
    groups: dict[str, collections.Counter] = dataclasses.field(default_factory=dict)
    duplicate_rows: int = 0
    duplicate_groups: int = 0
    delete_candidates: int = 0
    deleted_rows: int = 0
    redacted_rows: int = 0
    redacted_fields: int = 0


@dataclasses.dataclass
class MemberStats:
    name: str
    input_bytes: int = 0
    output_bytes: int = 0


class DuplicateIndex:
    """Disk-backed duplicate index containing only ephemeral HMAC digests."""
    def __init__(self, temp_dir: str, salt: bytes):
        self.salt = salt
        self.db = sqlite3.connect(str(Path(temp_dir) / "fingerprints.sqlite3"))
        self.db.execute("PRAGMA journal_mode=OFF")
        self.db.execute("PRAGMA synchronous=OFF")
        self.db.execute("CREATE TABLE fingerprints (scope TEXT NOT NULL, digest BLOB NOT NULL, n INTEGER NOT NULL, PRIMARY KEY(scope,digest))")
        self.operations = 0

    def add(self, scope: str, parts: list[bytes]) -> bool:
        mac = hmac.new(self.salt, digestmod=hashlib.sha256)
        for part in parts:
            mac.update(len(part).to_bytes(8, "big"))
            mac.update(part)
        digest = mac.digest()
        cur = self.db.execute("INSERT OR IGNORE INTO fingerprints(scope,digest,n) VALUES(?,?,1)", (scope, digest))
        duplicate = cur.rowcount == 0
        if duplicate:
            self.db.execute("UPDATE fingerprints SET n=n+1 WHERE scope=? AND digest=?", (scope, digest))
        self.operations += 1
        if self.operations % 5000 == 0:
            self.db.commit()
        return duplicate

    def duplicate_groups(self, scope: str) -> int:
        return int(self.db.execute("SELECT COUNT(*) FROM fingerprints WHERE scope=? AND n>1", (scope,)).fetchone()[0])

    def close(self) -> None:
        self.db.commit()
        self.db.close()


class DatabaseAdapter:
    name = "abstract"

    def process(self, source: BinaryIO, table_handler, output: Optional[BinaryIO], rewrite_tables: set[str], max_row_bytes: int) -> int:
        raise NotImplementedError


class MySQLDumpAdapter(DatabaseAdapter):
    """Streaming parser for MySQL/MariaDB INSERT statements used by SQL dumps."""
    name = "mysql-dump"

    def process(self, source: BinaryIO, table_handler, output: Optional[BinaryIO], rewrite_tables: set[str], max_row_bytes: int) -> int:
        current = None
        pending = b""
        header = b""
        held_row: Optional[bytes] = None
        input_count = 0

        def emit(data: bytes) -> None:
            if output is not None and data:
                output.write(data)

        def process_insert_buffer(final_eof: bool = False) -> None:
            nonlocal pending, current, held_row
            spans, semicolon = scan_tuples(pending)
            consumed_end = spans[-1][1] if spans else 0
            for start, end in spans:
                raw_tuple = pending[start:end]
                if len(raw_tuple) > max_row_bytes:
                    raise ToolError(f"Linha SQL excedeu o limite configurado de {max_row_bytes} bytes.")
                fields = split_tuple(raw_tuple)
                if len(fields) != len(current["columns"]):
                    current["parse_warnings"] += 1
                    cleaned_fields = fields
                else:
                    cleaned_fields = table_handler(current["table"], current["columns"], fields, raw_tuple)
                if current["rewrite"]:
                    if cleaned_fields is None:
                        current["deleted_in_statement"] += 1
                    else:
                        rendered = render_tuple(cleaned_fields)
                        if held_row is not None:
                            emit(held_row + b",\n")
                        held_row = rendered
                else:
                    emit(pending[current["last_emit"]:end])
                current["last_emit"] = end

            if semicolon is not None:
                suffix = pending[semicolon:]
                if current["rewrite"]:
                    if held_row is None:
                        # All rows in this INSERT were explicitly deleted.
                        emit(b"")
                    else:
                        emit(header)
                        if not header.endswith((b"\n", b"\r")):
                            emit(b"\n")
                        emit(held_row)
                        emit(suffix)
                else:
                    emit(pending[current["last_emit"]:])
                pending = b""
                current = None
                held_row = None
                return

            if current is not None:
                if current["rewrite"]:
                    # The separator after the last complete tuple is discarded;
                    # canonical commas/newlines are emitted when the next row arrives.
                    pending = pending[consumed_end:] if spans else pending
                else:
                    emit(pending[current["last_emit"]:consumed_end])
                    pending = pending[consumed_end:] if spans else pending
                current["last_emit"] = 0
                if len(pending) > max_row_bytes:
                    raise ToolError(f"INSERT ficou sem fechamento dentro do limite de {max_row_bytes} bytes.")

        for raw_line in source:
            input_count += len(raw_line)
            if current is None:
                match = HEADER_RE.match(raw_line)
                if not match:
                    emit(raw_line)
                    continue
                raw_table = match.group("table")
                table = raw_table.strip(b"\x60").decode("utf-8", "replace")
                columns = [part.strip().strip(b"\x60").decode("utf-8", "replace") for part in match.group("columns").split(b",")]
                value_start = match.end()
                prefix = raw_line[:value_start]
                rewrite = table in rewrite_tables and output is not None
                current = {"table": table, "columns": columns, "rewrite": rewrite, "last_emit": 0,
                           "parse_warnings": 0, "deleted_in_statement": 0}
                header = prefix
                pending = raw_line[value_start:]
                if not rewrite:
                    emit(prefix)
                process_insert_buffer()
            else:
                pending += raw_line
                process_insert_buffer()

        if current is not None:
            raise ToolError("O dump terminou dentro de um INSERT sem ponto e vírgula; a cópia foi cancelada.")
        return input_count


def scan_tuples(data: bytes) -> tuple[list[tuple[int, int]], Optional[int]]:
    """Find top-level value tuples and the first statement terminator outside quotes."""
    spans: list[tuple[int, int]] = []
    quote = 0
    escaped = False
    depth = 0
    start = -1
    i = 0
    while i < len(data):
        c = data[i]
        if quote:
            if escaped:
                escaped = False
            elif c == 92:
                escaped = True
            elif c == quote:
                if i + 1 < len(data) and data[i + 1] == quote:
                    i += 1
                else:
                    quote = 0
        elif c in (39, 34):
            quote = c
        elif c == 40:
            if depth == 0:
                start = i
            depth += 1
        elif c == 41 and depth:
            depth -= 1
            if depth == 0 and start >= 0:
                spans.append((start, i + 1))
                start = -1
        elif c == 59 and depth == 0:
            return spans, i
        i += 1
    return spans, None


def split_tuple(raw: bytes) -> list[bytes]:
    if len(raw) < 2 or raw[0] != 40 or raw[-1] != 41:
        raise ToolError("Tupla SQL malformada; esperado parêntese externo.")
    fields: list[bytes] = []
    start = 1
    depth = 0
    quote = 0
    escaped = False
    i = 1
    while i < len(raw) - 1:
        c = raw[i]
        if quote:
            if escaped:
                escaped = False
            elif c == 92:
                escaped = True
            elif c == quote:
                if i + 1 < len(raw) and raw[i + 1] == quote:
                    i += 1
                else:
                    quote = 0
        elif c in (39, 34):
            quote = c
        elif c == 40:
            depth += 1
        elif c == 41 and depth:
            depth -= 1
        elif c == 44 and depth == 0:
            fields.append(raw[start:i].strip())
            start = i + 1
        i += 1
    fields.append(raw[start:-1].strip())
    return fields


def decode_sql_value(raw: bytes) -> Optional[str]:
    token = raw.strip()
    if token.upper() == b"NULL":
        return None
    quote_at = token.find(b"'")
    if quote_at >= 0 and token.endswith(b"'"):
        content = token[quote_at + 1:-1]
        out = bytearray()
        escapes = {48: 0, 98: 8, 110: 10, 114: 13, 116: 9, 90: 26}
        i = 0
        while i < len(content):
            if content[i] == 92 and i + 1 < len(content):
                out.append(escapes.get(content[i + 1], content[i + 1]))
                i += 2
            elif content[i] == 39 and i + 1 < len(content) and content[i + 1] == 39:
                out.append(39)
                i += 2
            else:
                out.append(content[i])
                i += 1
        return out.decode("utf-8", "replace")
    return token.decode("utf-8", "replace")


def encode_sql_string(value: str) -> bytes:
    raw = value.encode("utf-8")
    replacements = {0: b"\\0", 8: b"\\b", 9: b"\\t", 10: b"\\n", 13: b"\\r", 26: b"\\Z", 92: b"\\\\", 39: b"\\'"}
    out = bytearray(b"'")
    for byte in raw:
        out.extend(replacements.get(byte, bytes((byte,))))
    out.extend(b"'")
    return bytes(out)


def render_tuple(fields: list[bytes]) -> bytes:
    return b"(" + b", ".join(fields) + b")"


def normalize_key(value: object) -> str:
    return re.sub(r"[^a-z0-9]+", "_", str(value).casefold()).strip("_")


def looks_like_image(value: str) -> bool:
    trimmed = value.strip()
    lower = trimmed.lower()
    if lower.startswith("data:image/") and ";base64," in lower:
        return True
    return len(trimmed) >= 64 and trimmed.startswith(IMAGE_PREFIXES)


def base64_candidate(value: str, threshold: int) -> bool:
    candidate = value.strip()
    if len(candidate) < threshold:
        return False
    for part in candidate.split(","):
        sample = part.strip()
        if len(sample) >= threshold and BASE64_RE.fullmatch(sample) and len(sample) % 4 == 0:
            return True
    return False


def json_path_matches(pattern: list[str], path: tuple[str, ...]) -> bool:
    return len(pattern) == len(path) and all(want == "*" or want == got for want, got in zip(pattern, path))


def sanitize_json_value(value, cfg: dict, path: tuple[str, ...] = (), inside_qr: bool = False, depth: int = 0):
    if depth > 16:
        return value, 0
    replacement = cfg.get("replacement", "[REMOVIDO_APOS_PERSISTENCIA]")
    sensitive_paths = cfg.get("sensitive_json_paths", [])
    if isinstance(value, dict):
        result = {}
        changed = 0
        for key, child in value.items():
            norm = normalize_key(key)
            child_path = path + (norm,)
            qr_context = inside_qr or norm in QR_CONTAINER_KEYS
            path_sensitive = any(json_path_matches([normalize_key(x) if x != "*" else "*" for x in pattern], child_path) for pattern in sensitive_paths)
            binary_field = (
                norm in BINARY_KEYS or "base64" in norm or "data_url" in norm
            ) and isinstance(child, str) and (
                looks_like_image(child)
                or base64_candidate(child, int(cfg.get("base64_min_chars", 512)))
            )
            if path_sensitive or binary_field or (inside_qr and norm in QR_SECRET_KEYS and isinstance(child, str) and child.strip()):
                result[key] = replacement
                changed += 1
                continue
            new_child, hits = sanitize_json_value(child, cfg, child_path, qr_context, depth + 1)
            result[key] = new_child
            changed += hits
        return result, changed
    if isinstance(value, list):
        result = []
        changed = 0
        for child in value:
            new_child, hits = sanitize_json_value(child, cfg, path + ("*",), inside_qr, depth + 1)
            result.append(new_child)
            changed += hits
        return result, changed
    if isinstance(value, str):
        trimmed = value.strip()
        if not trimmed:
            return value, 0
        if DATA_URI_RE.search(trimmed) or looks_like_image(trimmed):
            return replacement, 1
        decoded = None
        if trimmed[:1] in ("{", "[", '"'):
            try:
                decoded = json.loads(trimmed)
            except (ValueError, TypeError):
                decoded = None
        if decoded is not None:
            clean, hits = sanitize_json_value(decoded, cfg, path, inside_qr, depth + 1)
            if hits:
                return json.dumps(clean, ensure_ascii=False, separators=(",", ":")), hits
        return value, 0
    return value, 0


def scan_json_markers(value: str, threshold: int) -> tuple[int, int, int, int]:
    """Return base64 candidates, candidate bytes, data-URI count and QR-secret count."""
    try:
        node = json.loads(value)
    except (ValueError, TypeError):
        node = value
    candidates = candidate_bytes = data_uris = qr_secrets = 0

    def walk(item, path: tuple[str, ...] = (), in_qr: bool = False):
        nonlocal candidates, candidate_bytes, data_uris, qr_secrets
        if isinstance(item, dict):
            for key, child in item.items():
                norm = normalize_key(key)
                qr = in_qr or norm in QR_CONTAINER_KEYS
                if in_qr and norm in QR_SECRET_KEYS and isinstance(child, str) and child.strip():
                    qr_secrets += 1
                walk(child, path + (norm,), qr)
        elif isinstance(item, list):
            for child in item:
                walk(child, path + ("*",), in_qr)
        elif isinstance(item, str):
            if DATA_URI_RE.search(item):
                data_uris += 1
            if base64_candidate(item, threshold):
                candidates += 1
                candidate_bytes += len(item.encode("utf-8"))

    walk(node)
    return candidates, candidate_bytes, data_uris, qr_secrets


def rule_matches(rule: dict, values: dict[str, Optional[str]]) -> bool:
    for condition in rule.get("where", []):
        column = condition["column"]
        actual = values.get(column)
        expected = str(condition.get("value", ""))
        op = condition.get("op", "equals")
        if op == "is_null":
            matched = actual is None
        elif op == "equals":
            matched = actual == expected
        elif op == "contains":
            matched = actual is not None and expected.casefold() in actual.casefold()
        elif op == "in":
            matched = actual in [str(x) for x in condition.get("values", [])]
        else:
            raise ToolError(f"Operador de regra de exclusão não suportado: {op}")
        if not matched:
            return False
    return bool(rule.get("where"))


class AnalysisEngine:
    def __init__(self, config: dict, mode: str, apply_deletes: bool = False, max_row_bytes: int = DEFAULT_MAX_ROW):
        self.config = config
        self.mode = mode
        self.apply_deletes = apply_deletes
        self.max_row_bytes = max_row_bytes
        self.tables: dict[str, TableStats] = {}
        self.cleanup = {"redacted_rows": 0, "redacted_fields": 0, "deleted_rows": 0, "delete_candidates": 0}
        self.duplicate_index: Optional[DuplicateIndex] = None
        self.adapter = ADAPTERS.get(config.get("adapter", "mysql-dump"))
        if self.adapter is None:
            raise ToolError(f"Adaptador não implementado: {config.get('adapter')}")
        self.redaction = config.get("redaction", {})
        self.delete_rules = config.get("delete_rules", [])
        if apply_deletes and mode != "clean":
            raise ToolError("--apply-deletes só pode ser usado com clean-copy.")

    def begin_duplicates(self, directory: str) -> None:
        self.duplicate_index = DuplicateIndex(directory, os.urandom(32))

    def table_handler(self, table: str, columns: list[str], raw_fields: list[bytes], raw_tuple: bytes):
        stats = self.tables.setdefault(table, TableStats())
        stats.rows += 1
        stats.sql_bytes += len(raw_tuple)
        config = self.config.get("tables", {}).get(table, {})
        configured_fields = set(config.get("timestamp_fields", []))
        configured_fields.update(config.get("group_by", []))
        configured_fields.update(config.get("duplicate_fields", []))
        selected = set(self.redaction.get("columns_by_table", {}).get(table, []))
        configured_fields.update(selected)
        for rule in self.delete_rules:
            if rule.get("table") == table:
                configured_fields.update(condition["column"] for condition in rule.get("where", []))

        values = {}
        threshold = int(self.redaction.get("base64_min_chars", 512))
        for index, name in enumerate(columns):
            raw_value = raw_fields[index] if index < len(raw_fields) else b"NULL"
            token = raw_value.strip()
            metric = stats.columns.setdefault(name, ColumnStats())
            if token.upper() != b"NULL":
                measured = token
                quote_at = token.find(b"'")
                if quote_at >= 0 and token.endswith(b"'"):
                    measured = token[quote_at + 1:-1]
                size = len(measured)
                metric.non_null += 1
                metric.value_bytes += size
                metric.max_bytes = max(metric.max_bytes, size)
                needs_scan = size >= threshold or b"data:image/" in measured.lower()
                if name in configured_fields or needs_scan:
                    value = decode_sql_value(raw_value)
                    if name in configured_fields:
                        values[name] = value
                else:
                    value = None
                if needs_scan and value:
                    candidates, candidate_bytes, uris, qr = scan_json_markers(value, threshold)
                    # Check the full string as well as nested JSON text.
                    direct_candidate = base64_candidate(value, threshold)
                    if direct_candidate and candidates == 0:
                        candidates, candidate_bytes = 1, size
                    if DATA_URI_RE.search(value) and uris == 0:
                        uris = 1
                    metric.base64_candidates += candidates
                    metric.base64_candidate_bytes += candidate_bytes
                    metric.data_uris += uris
                    metric.qr_secrets += qr
            elif name in configured_fields:
                values[name] = None

        for field in config.get("timestamp_fields", []):
            date_value = values.get(field)
            if date_value and date_value not in ("0000-00-00 00:00:00", "0000-00-00"):
                stats.min_time = date_value if stats.min_time is None else min(stats.min_time, date_value)
                stats.max_time = date_value if stats.max_time is None else max(stats.max_time, date_value)

        for field in config.get("group_by", []):
            value = values.get(field)
            if value is not None:
                counter = stats.groups.setdefault(field, collections.Counter())
                key = value[:120]
                if key in counter or len(counter) < 200:
                    counter[key] += 1
                else:
                    counter["[outros valores]"] += 1

        duplicate_fields = config.get("duplicate_fields", [])
        if duplicate_fields and self.duplicate_index:
            if all(field in values for field in duplicate_fields):
                parts = [(values[field] or "").encode("utf-8") for field in duplicate_fields]
                if self.duplicate_index.add(table, parts):
                    stats.duplicate_rows += 1

        matching_rules = [rule for rule in self.delete_rules if rule.get("table") == table and rule_matches(rule, values)]
        if matching_rules:
            stats.delete_candidates += 1
            self.cleanup["delete_candidates"] += 1

        changed_fields = 0
        cleaned_fields = list(raw_fields)
        if self.mode == "clean":
            for index, name in enumerate(columns):
                if name not in selected or values.get(name) is None:
                    continue
                value = values[name]
                try:
                    node = json.loads(value)
                    cleaned, hits = sanitize_json_value(node, self.redaction)
                    sanitized_value = json.dumps(cleaned, ensure_ascii=False, separators=(",", ":")) if hits else value
                except (ValueError, TypeError):
                    cleaned, hits = sanitize_json_value(value, self.redaction)
                    sanitized_value = cleaned if isinstance(cleaned, str) else value
                if hits:
                    cleaned_fields[index] = encode_sql_string(sanitized_value)
                    changed_fields += hits
            if changed_fields:
                stats.redacted_rows += 1
                stats.redacted_fields += changed_fields
                self.cleanup["redacted_rows"] += 1
                self.cleanup["redacted_fields"] += changed_fields

        if matching_rules and self.apply_deletes:
            stats.deleted_rows += 1
            self.cleanup["deleted_rows"] += 1
            return None
        return cleaned_fields

    def finish(self) -> None:
        if self.duplicate_index:
            for table, stats in self.tables.items():
                config = self.config.get("tables", {}).get(table, {})
                if config.get("duplicate_fields"):
                    stats.duplicate_groups = self.duplicate_index.duplicate_groups(table)
            self.duplicate_index.close()
            self.duplicate_index = None

    def report(self, input_path: Path, output_path: Optional[Path] = None) -> dict:
        table_rows = []
        for name, stats in self.tables.items():
            columns = sorted(
                ({"name": col, **dataclasses.asdict(metric)} for col, metric in stats.columns.items()),
                key=lambda item: item["value_bytes"], reverse=True,
            )
            table_rows.append({
                "name": name,
                "rows": stats.rows,
                "approx_sql_row_bytes": stats.sql_bytes,
                "min_time": stats.min_time,
                "max_time": stats.max_time,
                "duplicate_rows": stats.duplicate_rows,
                "duplicate_groups": stats.duplicate_groups,
                "delete_candidates": stats.delete_candidates,
                "deleted_rows": stats.deleted_rows,
                "redacted_rows": stats.redacted_rows,
                "redacted_fields": stats.redacted_fields,
                "top_columns": columns[:20],
                "group_counts": {field: counter.most_common(15) for field, counter in stats.groups.items()},
            })
        table_rows.sort(key=lambda row: row["approx_sql_row_bytes"], reverse=True)
        result = {
            "tool": "database-health",
            "version": VERSION,
            "adapter": self.adapter.name,
            "input": {"name": input_path.name, "bytes": input_path.stat().st_size},
            "output": ({"name": output_path.name, "bytes": output_path.stat().st_size} if output_path and output_path.exists() else None),
            "mode": self.mode,
            "safe_defaults": {"database_connection": False, "source_modified": False, "delete_rules_applied": self.apply_deletes},
            "summary": {
                "tables_seen": len(table_rows),
                "rows_seen": sum(row["rows"] for row in table_rows),
                "approx_sql_row_bytes": sum(row["approx_sql_row_bytes"] for row in table_rows),
                **self.cleanup,
            },
            "tables": table_rows,
            "delete_rules": self.delete_rules,
        }
        return result


ADAPTERS = {"mysql-dump": MySQLDumpAdapter()}


def load_config(path: Path) -> dict:
    try:
        config = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, ValueError) as exc:
        raise ToolError(f"Não foi possível ler a configuração JSON: {exc}") from exc
    if not isinstance(config, dict) or config.get("version") != 1:
        raise ToolError("Configuração inválida: esperado objeto JSON com version=1.")
    if config.get("adapter", "mysql-dump") not in ADAPTERS:
        raise ToolError(f"Adaptador não suportado: {config.get('adapter')}")
    for rule in config.get("delete_rules", []):
        if not isinstance(rule, dict) or not rule.get("table") or not rule.get("where"):
            raise ToolError("Cada regra de exclusão precisa de table e where não vazio.")
    return config


def sql_members(path: Path, max_uncompressed: int):
    """Yield SQL member streams without extracting archive paths."""
    if zipfile.is_zipfile(path):
        archive = zipfile.ZipFile(path, "r")
        infos = archive.infolist()
        if len(infos) > 512:
            archive.close()
            raise ToolError("ZIP contém mais de 512 membros; revise o arquivo antes da análise.")
        total = sum(info.file_size for info in infos if not info.is_dir())
        if total > max_uncompressed:
            archive.close()
            raise ToolError(f"ZIP excede o limite descompactado ({max_uncompressed} bytes).")
        names = set()
        for info in infos:
            if info.filename in names:
                archive.close()
                raise ToolError("ZIP contém nomes de membro repetidos; análise cancelada por ambiguidade.")
            names.add(info.filename)
        sql_infos = [info for info in infos if not info.is_dir() and info.filename.lower().endswith((".sql", ".sql.gz"))]
        if not sql_infos:
            archive.close()
            raise ToolError("ZIP não contém membro .sql ou .sql.gz.")
        try:
            for info in sql_infos:
                raw = archive.open(info, "r")
                stream = gzip.GzipFile(fileobj=raw, mode="rb") if info.filename.lower().endswith(".gz") else raw
                yield info.filename, stream
                stream.close()
        finally:
            archive.close()
    elif path.name.lower().endswith(".sql.gz") or path.suffix.lower() == ".gz":
        with gzip.open(path, "rb") as stream:
            yield path.name, stream
    else:
        with path.open("rb") as stream:
            yield path.name, stream


def copy_zip_members(source_path: Path, output_zip: zipfile.ZipFile, sql_results: dict[str, bytes]):
    with zipfile.ZipFile(source_path, "r") as source:
        for info in source.infolist():
            if info.is_dir() or info.filename in sql_results:
                continue
            with source.open(info, "r") as src, output_zip.open(info, "w", force_zip64=True) as dst:
                copy_stream(src, dst)


def copy_stream(src: BinaryIO, dst: BinaryIO) -> int:
    total = 0
    while True:
        chunk = src.read(1024 * 1024)
        if not chunk:
            return total
        dst.write(chunk)
        total += len(chunk)


def process_input(input_path: Path, engine: AnalysisEngine, output_writer=None, max_uncompressed: int = DEFAULT_MAX_UNCOMPRESSED):
    seen = set()
    members = []
    total_sql_bytes = 0

    def account(count: int) -> None:
        nonlocal total_sql_bytes
        total_sql_bytes += count
        if total_sql_bytes > max_uncompressed:
            raise ToolError(f"Conteúdo SQL excede o limite descompactado ({max_uncompressed} bytes).")

    if zipfile.is_zipfile(input_path):
        with zipfile.ZipFile(input_path, "r") as source:
            infos = [i for i in source.infolist() if not i.is_dir() and i.filename.lower().endswith((".sql", ".sql.gz"))]
            for info in infos:
                with source.open(info, "r") as raw:
                    if info.filename.lower().endswith(".gz"):
                        with gzip.GzipFile(fileobj=raw, mode="rb") as sql_stream:
                            output_member = output_writer(info.filename) if output_writer else None
                            writer_ctx = gzip.GzipFile(fileobj=output_member, mode="wb", mtime=0) if output_member and info.filename.lower().endswith(".gz") else None
                            stream_out = writer_ctx or output_member
                            account(engine.adapter.process(sql_stream, engine.table_handler, stream_out, rewrite_tables(engine), engine.max_row_bytes))
                            if writer_ctx:
                                writer_ctx.close()
                            if output_member:
                                output_member.close()
                    else:
                        output_member = output_writer(info.filename) if output_writer else None
                        account(engine.adapter.process(raw, engine.table_handler, output_member, rewrite_tables(engine), engine.max_row_bytes))
                        if output_member:
                            output_member.close()
                seen.add(info.filename)
                members.append(info.filename)
            if not infos:
                raise ToolError("ZIP não contém membro SQL.")
            if output_writer:
                # The caller copies remaining non-SQL members after processing.
                pass
    elif input_path.name.lower().endswith(".gz"):
        member_name = input_path.name
        with gzip.open(input_path, "rb") as stream:
            output_member = output_writer(member_name) if output_writer else None
            writer_ctx = gzip.GzipFile(fileobj=output_member, mode="wb", mtime=0) if output_member else None
            account(engine.adapter.process(stream, engine.table_handler, writer_ctx or output_member, rewrite_tables(engine), engine.max_row_bytes))
            if writer_ctx:
                writer_ctx.close()
            if output_member:
                output_member.close()
        members.append(member_name)
    else:
        member_name = input_path.name
        with input_path.open("rb") as stream:
            output_member = output_writer(member_name) if output_writer else None
            account(engine.adapter.process(stream, engine.table_handler, output_member, rewrite_tables(engine), engine.max_row_bytes))
            if output_member:
                output_member.close()
        members.append(member_name)
    return members


def rewrite_tables(engine: AnalysisEngine) -> set[str]:
    if engine.mode != "clean":
        return set()
    tables = set(engine.redaction.get("columns_by_table", {}).keys())
    if engine.apply_deletes:
        tables.update(rule["table"] for rule in engine.delete_rules)
    return tables


def write_report(report: dict, json_path: Path, markdown_path: Optional[Path]) -> None:
    json_path.parent.mkdir(parents=True, exist_ok=True)
    temp = json_path.with_suffix(json_path.suffix + ".tmp")
    temp.write_text(json.dumps(report, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    temp.replace(json_path)
    if markdown_path:
        markdown_path.parent.mkdir(parents=True, exist_ok=True)
        markdown_path.write_text(render_markdown(report), encoding="utf-8")


def render_markdown(report: dict) -> str:
    summary = report["summary"]
    lines = [
        "# Relatório de saúde do banco", "",
        f"- Modo: `{report['mode']}` | Adaptador: `{report['adapter']}` | Versão: `{report['version']}`",
        f"- Arquivo analisado: `{report['input']['name']}` ({report['input']['bytes']:,} bytes)",
        f"- Tabelas com INSERT: {summary['tables_seen']:,} | Linhas analisadas: {summary['rows_seen']:,}",
        f"- Bytes aproximados em tuplas SQL: {summary['approx_sql_row_bytes']:,}",
        f"- Candidatos a exclusão: {summary['delete_candidates']:,} | Excluídos: {summary['deleted_rows']:,}",
        f"- Linhas/campos saneados: {summary['redacted_rows']:,} / {summary['redacted_fields']:,}",
        "", "## Tabelas por volume aproximado", "",
        "| Tabela | Linhas | Bytes SQL | Duplicatas repetidas | Candidatos de exclusão |",
        "|---|---:|---:|---:|---:|",
    ]
    for table in report["tables"][:25]:
        lines.append(f"| {table['name']} | {table['rows']:,} | {table['approx_sql_row_bytes']:,} | {table['duplicate_rows']:,} | {table['delete_candidates']:,} |")
    for table in report["tables"]:
        notable = [col for col in table["top_columns"] if col["base64_candidates"] or col["data_uris"] or col["qr_secrets"]]
        if notable:
            lines.extend(["", f"## Sinais em `{table['name']}`", "", "| Coluna | Bytes | Máximo | Candidatos Base64 | Data URLs | Campos QR |", "|---|---:|---:|---:|---:|---:|"])
            for col in notable[:10]:
                lines.append(f"| {col['name']} | {col['value_bytes']:,} | {col['max_bytes']:,} | {col['base64_candidates']:,} | {col['data_uris']:,} | {col['qr_secrets']:,} |")
    lines.extend(["", "## Limites de segurança", "", "A ferramenta não conecta ao banco nem altera o arquivo de origem. A limpeza só gera uma nova cópia; regras de exclusão só são aplicadas com `--apply-deletes`. Candidatos Base64 são indicadores heurísticos e precisam ser interpretados no contexto da coluna.", ""])
    return "\n".join(lines)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(description="Analisa dumps SQL localmente e cria cópias saneadas sem conexão ao banco.")
    sub = parser.add_subparsers(dest="command", required=True)
    for name in ("analyze", "clean-copy"):
        cmd = sub.add_parser(name)
        cmd.add_argument("input", type=Path, help="arquivo .sql, .sql.gz ou ZIP contendo dump SQL")
        cmd.add_argument("--config", type=Path, required=True, help="perfil JSON de tabelas e regras")
        cmd.add_argument("--report-json", type=Path, help="destino do relatório JSON")
        cmd.add_argument("--report-md", type=Path, help="destino do relatório Markdown")
        cmd.add_argument("--max-uncompressed-gib", type=float, default=20, help="limite de dados SQL descompactados (padrão: 20 GiB)")
        cmd.add_argument("--max-row-mib", type=float, default=64, help="limite de memória por linha SQL (padrão: 64 MiB)")
    clean = sub.choices["clean-copy"]
    clean.add_argument("output", type=Path, help="novo arquivo ZIP de saída; nunca sobrescreve por padrão")
    clean.add_argument("--apply-deletes", action="store_true", help="aplica as regras delete_rules do JSON; sem esta opção apenas conta candidatos")
    clean.add_argument("--overwrite", action="store_true", help="permite substituir somente o caminho de saída informado")
    return parser


def run(args) -> int:
    input_path = args.input.resolve()
    if not input_path.is_file():
        raise ToolError(f"Arquivo de entrada não encontrado: {input_path}")
    config = load_config(args.config)
    mode = "clean" if args.command == "clean-copy" else "analyze"
    max_row_bytes = int(args.max_row_mib * 1024**2)
    max_uncompressed = int(args.max_uncompressed_gib * 1024**3)
    if max_row_bytes < 1024 or max_uncompressed < 1:
        raise ToolError("Limites de tamanho precisam ser positivos e coerentes.")
    if zipfile.is_zipfile(input_path):
        with zipfile.ZipFile(input_path, "r") as source_zip:
            infos = source_zip.infolist()
            if len(infos) > 512:
                raise ToolError("ZIP contém mais de 512 membros; revise o arquivo antes da análise.")
            if sum(info.file_size for info in infos if not info.is_dir()) > max_uncompressed:
                raise ToolError(f"ZIP excede o limite descompactado ({max_uncompressed} bytes).")
            names = [info.filename for info in infos]
            if len(names) != len(set(names)):
                raise ToolError("ZIP contém nomes de membro repetidos; análise cancelada por ambiguidade.")

    output_path = None
    temp_output = None
    if mode == "clean":
        output_path = args.output.resolve()
        if output_path == input_path:
            raise ToolError("A saída não pode apontar para o mesmo arquivo da origem.")
        if output_path.exists() and not args.overwrite:
            raise ToolError(f"A saída já existe; use --overwrite para substituí-la: {output_path}")
        output_path.parent.mkdir(parents=True, exist_ok=True)
        temp_output = output_path.with_name(output_path.name + ".tmp")
        if temp_output.exists():
            temp_output.unlink()

    with tempfile.TemporaryDirectory(prefix="db-health-") as temp_dir:
        engine = AnalysisEngine(config, mode, bool(getattr(args, "apply_deletes", False)), max_row_bytes)
        engine.begin_duplicates(temp_dir)
        try:
            if mode == "clean":
                with zipfile.ZipFile(temp_output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=6, allowZip64=True) as dest:
                    def output_writer(member_name: str):
                        info = zipfile.ZipInfo(member_name)
                        info.compress_type = zipfile.ZIP_DEFLATED
                        info.external_attr = 0o600 << 16
                        return dest.open(info, "w", force_zip64=True)
                    sql_members = process_input(input_path, engine, output_writer, max_uncompressed)
                    if zipfile.is_zipfile(input_path):
                        sql_set = set(sql_members)
                        with zipfile.ZipFile(input_path, "r") as source:
                            for info in source.infolist():
                                if info.is_dir() or info.filename in sql_set:
                                    continue
                                with source.open(info, "r") as src, dest.open(info, "w", force_zip64=True) as dst:
                                    copy_stream(src, dst)
                temp_output.replace(output_path)
            else:
                process_input(input_path, engine, max_uncompressed=max_uncompressed)
            engine.finish()
            report = engine.report(input_path, output_path)
            default_base = output_path if output_path else input_path
            report_json = args.report_json or default_base.with_name(default_base.name + ".report.json")
            report_md = args.report_md or default_base.with_name(default_base.name + ".report.md")
            write_report(report, report_json, report_md)
        except Exception:
            if engine.duplicate_index:
                engine.duplicate_index.close()
                engine.duplicate_index = None
            if temp_output and temp_output.exists():
                temp_output.unlink()
            raise

    print(f"Relatório JSON: {report_json}")
    print(f"Relatório Markdown: {report_md}")
    if output_path:
        print(f"Cópia saneada: {output_path}")
    print(f"Linhas analisadas: {report['summary']['rows_seen']:,}; candidatos a exclusão: {report['summary']['delete_candidates']:,}; linhas saneadas: {report['summary']['redacted_rows']:,}")
    return 0


class _null_context:
    def __enter__(self):
        return None
    def __exit__(self, *args):
        return False


def main(argv: Optional[list[str]] = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)
    try:
        return run(args)
    except (ToolError, OSError, zipfile.BadZipFile, gzip.BadGzipFile) as exc:
        print(f"ERRO: {exc}", file=sys.stderr)
        return 2


if __name__ == "__main__":
    raise SystemExit(main())
