"""MkDocs build hooks.

Mermaid escaping
----------------
``mkdocs-mermaid2-plugin``'s ``fence_mermaid`` formatter emits the diagram into a
``<div class="mermaid">`` *without* escaping ``<``/``>``. Mermaid 10/11 read the
diagram from ``element.innerHTML`` and run ``entityDecode`` on it, but the browser
parses the page first -- so class-diagram tokens such as ``<<interface>>`` and
``<|--`` (which contain ``<``) get swallowed as bogus HTML tags before Mermaid
ever sees them, producing the "Syntax error in text" box.

To make Mermaid receive the diagram verbatim, this hook entity-escapes ``&``,
``<`` and ``>`` inside every ```` ```mermaid ```` fence *before* Markdown runs.
The escaped entities pass through Markdown untouched and are restored by Mermaid's
own ``entityDecode`` step at render time.
"""

from __future__ import annotations

import re

_MERMAID_FENCE = re.compile(
    r"(?P<indent>[ \t]*)```+[ \t]*mermaid[ \t]*\n(?P<body>.*?)(?P=indent)```",
    re.DOTALL,
)


def _escape_body(match: re.Match) -> str:
    body = match.group("body")
    body = body.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    return f"{match.group('indent')}```mermaid\n{body}{match.group('indent')}```"


def on_page_markdown(markdown: str, **kwargs) -> str:
    """Entity-escape the contents of every mermaid fence."""
    return _MERMAID_FENCE.sub(_escape_body, markdown)
