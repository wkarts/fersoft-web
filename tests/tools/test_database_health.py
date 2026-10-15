import json
import subprocess
import sys
import tempfile
import unittest
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / "tools" / "database_health" / "db_health.py"


class DatabaseHealthCliTest(unittest.TestCase):
    def setUp(self):
        self.temp = tempfile.TemporaryDirectory()
        self.directory = Path(self.temp.name)
        self.config = {
            "version": 1,
            "adapter": "mysql-dump",
            "tables": {
                "logs": {
                    "timestamp_fields": ["created_at"],
                    "group_by": ["acao", "modelo"],
                    "duplicate_fields": ["acao", "modelo", "created_at", "dados_depois"],
                },
                "connect_api_webhook_events": {
                    "timestamp_fields": ["received_at"],
                    "duplicate_fields": ["deduplication_key"],
                },
            },
            "redaction": {
                "columns_by_table": {
                    "logs": ["dados_depois"],
                    "connect_api_webhook_events": ["payload", "normalized_payload"],
                },
                "sensitive_json_paths": [["data", "qrcode", "code"]],
                "sensitive_json_keys": ["senha"],
                "base64_min_chars": 64,
                "replacement": "[REDACTED]",
            },
            "delete_rules": [
                {
                    "name": "remove-not-found",
                    "table": "logs",
                    "where": [{"column": "modelo", "op": "equals", "value": "NotFoundHttpException"}],
                }
            ],
        }
        self.config_path = self.directory / "profile.json"
        self.config_path.write_text(json.dumps(self.config), encoding="utf-8")
        self.sql_path = self.directory / "input.sql"
        self.original_sql = (
            "-- dump fixture\n"
            "INSERT INTO `ordinary` (`id`, `message`) VALUES\n"
            "(1, 'keep comma, parentheses (and semicolon;) intact');\n"
            "INSERT INTO `logs` (`id`, `acao`, `modelo`, `created_at`, `dados_depois`) VALUES\n"
            "(1, 'exception', 'NotFoundHttpException', '2026-01-01 10:00:00', '\"{\\\"message\\\":\\\"route not found\\\"}\"'),\n"
            "(2, 'update', 'Pedido', '2026-01-02 10:00:00', '{\\\"ok\\\":true,\\\"senha\\\":\\\"sample-secret\\\"}'),\n"
            "(3, 'update', 'Pedido', '2026-01-02 10:00:00', '{\\\"ok\\\":true,\\\"senha\\\":\\\"sample-secret\\\"}');\n"
            "INSERT INTO `connect_api_webhook_events` (`id`, `event_type`, `deduplication_key`, `payload`, `normalized_payload`, `received_at`) VALUES\n"
            "(1, 'qrcode-updated', 'evt-1', '{\"data\":{\"qrcode\":{\"code\":\"PAIRING-SECRET\",\"status\":\"pending\"},\"avatar\":\"data:image/png;base64,AAAA\"}}', NULL, '2026-01-03 10:00:00');\n"
        ).encode("utf-8")
        self.sql_path.write_bytes(self.original_sql)
        self.zip_path = self.directory / "input.zip"
        with zipfile.ZipFile(self.zip_path, "w", compression=zipfile.ZIP_DEFLATED) as archive:
            archive.writestr("dump/fixture.sql", self.original_sql)
            archive.writestr("notes.txt", b"keep this attachment")

    def tearDown(self):
        self.temp.cleanup()

    def run_cli(self, *args, expect=0):
        result = subprocess.run(
            [sys.executable, str(SCRIPT), *map(str, args)],
            cwd=ROOT,
            text=True,
            capture_output=True,
            check=False,
        )
        self.assertEqual(expect, result.returncode, result.stdout + result.stderr)
        return result

    def test_analyze_counts_volume_markers_and_duplicate_fingerprints(self):
        report_path = self.directory / "analysis.json"
        self.run_cli("analyze", self.zip_path, "--config", self.config_path, "--report-json", report_path)
        report = json.loads(report_path.read_text(encoding="utf-8"))
        tables = {table["name"]: table for table in report["tables"]}
        self.assertEqual(3, tables["logs"]["rows"])
        self.assertEqual(1, tables["logs"]["duplicate_rows"])
        self.assertEqual(1, tables["logs"]["duplicate_groups"])
        self.assertEqual(1, tables["logs"]["delete_candidates"])
        self.assertEqual("2026-01-01 10:00:00", tables["logs"]["min_time"])
        event_columns = {column["name"]: column for column in tables["connect_api_webhook_events"]["top_columns"]}
        self.assertEqual(1, event_columns["payload"]["qr_secrets"])
        self.assertEqual(1, event_columns["payload"]["data_uris"])
        log_columns = {column["name"]: column for column in tables["logs"]["top_columns"]}
        self.assertEqual(2, log_columns["dados_depois"]["sensitive_fields"])

    def test_clean_copy_redacts_qr_without_changing_other_member_or_deleting(self):
        output = self.directory / "clean.zip"
        self.run_cli("clean-copy", self.zip_path, output, "--config", self.config_path)
        with zipfile.ZipFile(output) as archive:
            cleaned = archive.read("dump/fixture.sql")
            self.assertEqual(b"keep this attachment", archive.read("notes.txt"))
        self.assertIn(b"PAIRING-SECRET", self.original_sql)
        self.assertNotIn(b"PAIRING-SECRET", cleaned)
        self.assertNotIn(b"data:image/png;base64", cleaned)
        self.assertNotIn(b"sample-secret", cleaned)
        self.assertIn(b"[REDACTED]", cleaned)
        self.assertIn(b"INSERT INTO `ordinary` (`id`, `message`) VALUES\n(1, 'keep comma, parentheses (and semicolon;) intact');\n", cleaned)
        self.assertIn(b"'NotFoundHttpException'", cleaned)
        self.assertLess(cleaned.index(b"INSERT INTO `logs`"), cleaned.index(b"(1, 'exception'"))
        self.assertIn(b'"senha":"[REDACTED]"', cleaned)
        self.assertNotEqual(self.original_sql, cleaned)
        report_path = self.directory / "clean-analysis.json"
        self.run_cli("analyze", output, "--config", self.config_path, "--report-json", report_path)
        report = json.loads(report_path.read_text(encoding="utf-8"))
        tables = {table["name"]: table for table in report["tables"]}
        logs = {column["name"]: column for column in tables["logs"]["top_columns"]}
        events = {column["name"]: column for column in tables["connect_api_webhook_events"]["top_columns"]}
        self.assertEqual(0, logs["dados_depois"]["sensitive_fields"])
        self.assertEqual(0, events["payload"]["qr_secrets"])
        self.assertEqual(3, tables["logs"]["rows"])

    def test_explicit_delete_rebuilds_statement_without_trailing_comma(self):
        output = self.directory / "pruned.zip"
        self.run_cli("clean-copy", self.zip_path, output, "--config", self.config_path, "--apply-deletes")
        with zipfile.ZipFile(output) as archive:
            cleaned = archive.read("dump/fixture.sql")
        self.assertNotIn(b"NotFoundHttpException", cleaned)
        self.assertIn(b"(2, 'update'", cleaned)
        self.assertIn(b"(3, 'update'", cleaned)
        self.assertIn(b"(3, 'update', 'Pedido', '2026-01-02 10:00:00', '{\"ok\":true,\"senha\":\"[REDACTED]\"}');", cleaned)
        self.assertNotIn(b"),\n;", cleaned)
        report_path = self.directory / "pruned-analysis.json"
        self.run_cli("analyze", output, "--config", self.config_path, "--report-json", report_path)
        report = json.loads(report_path.read_text(encoding="utf-8"))
        logs = next(table for table in report["tables"] if table["name"] == "logs")
        self.assertEqual(2, logs["rows"])

    def test_refuses_to_overwrite_existing_output_by_default(self):
        output = self.directory / "already.zip"
        output.write_bytes(b"preserve")
        self.run_cli("clean-copy", self.zip_path, output, "--config", self.config_path, expect=2)
        self.assertEqual(b"preserve", output.read_bytes())


if __name__ == "__main__":
    unittest.main()
