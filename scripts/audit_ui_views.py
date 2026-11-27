#!/usr/bin/env python3
"""Inventário estático de views, formulários, diálogos e rotas declaradas.

Não renderiza Blade nem executa a aplicação. A relação view/rota é registrada
separadamente porque controllers e grupos com prefixo tornam o vínculo dinâmico.
"""

import json
import re
from collections import Counter
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
VIEWS = ROOT / "resources" / "views"
OUT = ROOT / "docs" / "auditoria-views-2026-10-16.json"
TAG = re.compile(r"<(form|div|input|select|textarea|button|a)\b[^>]*>", re.I | re.S)
ATTR = re.compile(r"([\w:-]+)\s*=\s*([\"'])(.*?)\2", re.S)
MODAL = re.compile(r"\bmodal\b(?!-)")


def line_of(source, offset):
    return source.count("\n", 0, offset) + 1


def tags(source, name):
    for match in TAG.finditer(source):
        if match.group(1).lower() != name:
            continue
        attrs = {key.lower(): value for key, _quote, value in ATTR.findall(match.group(0))}
        yield {"line": line_of(source, match.start()), "attributes": attrs}


def inspect_view(path):
    source = path.read_text(encoding="utf-8", errors="replace")
    ext = re.search(r"@extends\s*\(([^\n]+?)\)", source)
    form_tags = list(tags(source, "form"))
    inputs = list(tags(source, "input")) + list(tags(source, "select")) + list(tags(source, "textarea"))
    modal_tags = [item for item in tags(source, "div") if MODAL.search(item["attributes"].get("class", ""))]
    dimensions = []
    for match in re.finditer(r"\bstyle\s*=\s*([\"'])(.*?)\1", source, re.I | re.S):
        if re.search(r"(?:min-|max-)?(?:width|height)\s*:", match.group(2), re.I):
            dimensions.append(line_of(source, match.start()))
    links = []
    for item in tags(source, "a"):
        href = item["attributes"].get("href", "")
        if href and (href.startswith("/") or "route(" in href or "url(" in href):
            links.append({"line": item["line"], "href": href})
    layout = ext.group(1).strip() if ext else None
    patterns = {name: name in source for name in ("ui-admin", "fer-page", "fer-form", "fer-dialog", "card", "select2", "datatable", "table-responsive")}
    risk_flags = []
    if dimensions and form_tags:
        risk_flags.append("form_has_inline_dimensions")
    if len(modal_tags) >= 5:
        risk_flags.append("five_or_more_modal_markup_blocks")
    if layout and "'default.layout'" not in layout and (form_tags or modal_tags):
        risk_flags.append("form_or_modal_in_other_layout")
    if patterns["ui-admin"] and (form_tags or modal_tags):
        risk_flags.append("admin_palette_requires_variant_review")
    return {
        "path": str(path.relative_to(ROOT)),
        "layout_expression": layout,
        "forms": [
            {"line": item["line"], "method": item["attributes"].get("method", "get"),
             "action": item["attributes"].get("action"), "id": item["attributes"].get("id")}
            for item in form_tags
        ],
        "field_names": sorted({item["attributes"]["name"] for item in inputs if "name" in item["attributes"]}),
        "modals": [{"line": item["line"], "id": item["attributes"].get("id"),
                    "class": item["attributes"].get("class", "")} for item in modal_tags],
        "local_links": links,
        "inline_style_count": len(re.findall(r"\bstyle\s*=", source, re.I)),
        "inline_dimensions_at_lines": dimensions,
        "patterns": patterns,
        "risk_flags_for_manual_review": risk_flags,
        "includes": re.findall(r"@include\s*\(([^\n]+?)\)", source),
    }


def inspect_routes():
    source = (ROOT / "routes" / "web.php").read_text(encoding="utf-8", errors="replace")
    result = []
    pattern = re.compile(r"\bRoute::(get|post|put|patch|delete|any|match|resource)\s*\(\s*([\"'])(.*?)\2", re.I)
    for match in pattern.finditer(source):
        result.append({"line": line_of(source, match.start()), "method": match.group(1).upper(), "raw_path": match.group(3)})
    return result


def inspect_menu():
    source = (ROOT / "app" / "Helpers" / "Menu.php").read_text(encoding="utf-8", errors="replace")
    entries = []
    group = None
    for number, line in enumerate(source.splitlines(), 1):
        title = re.search(r"['\"]titulo['\"]\s*=>\s*['\"]([^'\"]+)['\"]", line)
        if title:
            group = title.group(1)
        route = re.search(r"['\"]rota['\"]\s*=>\s*['\"]([^'\"]*)['\"]", line)
        if route and not line.lstrip().startswith(("//", "*")):
            entries.append({"line": number, "group": group, "raw_path": route.group(1)})
    return entries


def main():
    views = [inspect_view(path) for path in sorted(VIEWS.rglob("*.blade.php"))]
    routes = inspect_routes()
    menu = inspect_menu()
    summary = {
        "blade_views": len(views),
        "application_views_excluding_vendor": sum(not item["path"].startswith("resources/views/vendor/") for item in views),
        "views_with_form": sum(bool(item["forms"]) for item in views),
        "form_elements": sum(len(item["forms"]) for item in views),
        "views_with_modal": sum(bool(item["modals"]) for item in views),
        "modal_elements": sum(len(item["modals"]) for item in views),
        "views_with_inline_styles": sum(item["inline_style_count"] > 0 for item in views),
        "views_with_inline_dimensions": sum(bool(item["inline_dimensions_at_lines"]) for item in views),
        "views_with_ui_admin": sum(item["patterns"]["ui-admin"] for item in views),
        "views_with_fer_page": sum(item["patterns"]["fer-page"] for item in views),
        "layout_expressions": Counter(item["layout_expression"] or "(partial/standalone)" for item in views).most_common(),
        "route_declarations_literal": len(routes),
        "menu_routes_literal": len(menu),
        "menu_groups_literal": sorted({item["group"] for item in menu if item["group"]}),
        "risk_flags_for_manual_review": Counter(flag for item in views for flag in item["risk_flags_for_manual_review"]),
    }
    OUT.parent.mkdir(parents=True, exist_ok=True)
    OUT.write_text(json.dumps({"method": "static, source-only; no Blade rendering or live route resolution", "summary": summary,
                               "views": views, "routes_web_literal": routes, "menu_helper_literal": menu},
                              ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(summary, ensure_ascii=False, indent=2))
    print(OUT)


if __name__ == "__main__":
    main()
