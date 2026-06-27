# TCPDF Isolation (pre-migration cleanup)

**Date:** 2026-06-27
**Status:** Approved

## Goal

Isolate TCPDF behind a clean, mid-level engine boundary so that a future
migration to `tecnickcom/tc-lib-pdf` touches one well-defined seam instead of
code scattered across the export layer. **No change to the generated PDF
output** — this is a pure structural refactor guarded by the existing PDF tests.

## Decisions (from brainstorming)

- **Depth:** isolate behind a PDF-engine boundary (interface + a TCPDF adapter).
  Not a full Wrapper decomposition, not a mere tidy.
- **Boundary level:** a **mid-level `PdfEngine` interface** mirroring the PDF
  operations `Pdf.php` already needs (init/lifecycle, text, HTML, fonts, margins,
  bookmarks, TOC, output) plus render-time callback hooks.
- **`Pdf.php` impact:** **Facade** — the repurposed `Wrapper` keeps its current
  public method names and delegates native ops to the engine, so `Pdf.php` stays
  essentially unchanged. (Alternative "split `Pdf.php` into engine+renderer"
  rejected as larger blast radius.)
- **Unavoidable subclass:** a single thin `TcpdfDocument extends TCPDF` remains as
  the sink for TCPDF's virtual callbacks (`Header`/`Footer`/`Image`), containing
  zero business logic. It is the only `extends TCPDF` in the codebase.

## Current state (coupling map)

- Public boundary is already at `phpMyFAQ\Export\Pdf` (callers use
  `Export::create(…, 'pdf')` or `new Pdf(...)`, then `generate()`/`generateFile()`).
- Tight coupling is concentrated in `phpMyFAQ\Export\Pdf\Wrapper` (824 lines) which
  `extends TCPDF` and mixes four concerns:
  1. TCPDF lifecycle + ~30 hardcoded `K_*` / `PDF_*` constants
     (`defineTcpdfConstants()`).
  2. Overridden TCPDF virtual callbacks: `Header()`, `Footer()`, `Image()`,
     `WriteHTML()`.
  3. phpMyFAQ domain logic: font-by-language map, header/footer **content**,
     base64 image embedding (`convertExternalImagesToBase64`, `concatenatePaths`),
     HTML preprocessing, TOC orchestration (`addFaqToc`).
  4. Native PDF passthrough used by `Pdf.php`.
- `tecnickcom/tcpdf: ~6.11.2` in `composer.json`. `tc-lib-pdf` is **not** present.
- Tests: `tests/phpMyFAQ/Export/PdfTest.php` (421 lines, via `Pdf` public API),
  `tests/phpMyFAQ/Export/Pdf/WrapperTest.php` (989 lines), plus PDF controller
  tests (frontend/api) and `ExportTest`.

### Methods `Pdf.php` calls on `$this->wrapper`

- **Native PDF ops** (move behind `PdfEngineInterface`): `Open`, `Output`,
  `AddPage`, `Write`, `Ln`, `WriteHTML`, `SetFont`, `SetMargins`,
  `SetHeaderMargin`, `SetFooterMargin`, `SetDisplayMode`, `SetAuthor`,
  `SetCreator`, `SetTitle`, `Bookmark`, `setPrintHeader`.
- **phpMyFAQ ops** (stay on the renderer): `setCategory`, `setCategories`,
  `setQuestion`, `setConfig`, `setFaq`, `isFullExport`, `enableBookmarks`,
  `getCurrentFont`, `addFaqToc`.

## Target architecture

New namespace: `phpMyFAQ\Export\Pdf\Engine`.

### 1. `PdfEngineInterface`

Declares the mid-level PDF operations the renderer/`Pdf` need. Grouped:

- **Lifecycle:** `open()`, `output(string $dest, string $name): string`,
  `addPage()`, `setPrintHeader(bool)`, `setDisplayMode(...)`.
- **Document metadata:** `setTitle()`, `setAuthor()`, `setCreator()`, `bookmark(...)`.
- **Layout:** `setMargins(...)`, `setHeaderMargin(...)`, `setFooterMargin(...)`,
  `setFont(...)`, `getCurrentFont(): string`.
- **Content:** `write(...)`, `ln(...)`, `writeHtml(...)`, `writeHtmlCell(...)`,
  `image(...)`, `multiCell(...)`, `cell(...)`, `setY(...)`, `setTextColor(...)`.
- **TOC:** `addTocPage()`, `addToc()`, `endTocPage()`, `getAliasNumPage()`,
  `getAliasNbPages()`, `getLastH()`.
- **Render-time callbacks:** `onHeader(callable)`, `onFooter(callable)`,
  `onImage(callable)` — invoked by the engine when TCPDF fires its virtual
  callbacks. The callables draw via the same engine instance.

The exact method set is the union of what `Pdf.php` and the repurposed `Wrapper`
call; method names mirror TCPDF semantics so the Facade delegation is 1:1 and
behavior is preserved. (Casing normalized to the interface; the Facade maps
`Pdf.php`'s existing `Wrapper->Write(...)` etc. onto it.)

### 2. `TcpdfEngine implements PdfEngineInterface`

- The **only** file referencing TCPDF.
- Owns the constants block (moved verbatim from `Wrapper::defineTcpdfConstants()`).
- Holds a private `TcpdfDocument` instance and delegates every interface method to
  it.
- Registers the `onHeader`/`onFooter`/`onImage` callables and exposes them to the
  inner document.

### 3. `TcpdfDocument extends TCPDF` (thin, final, internal)

- Overrides `Header()`, `Footer()`, `Image()` to invoke the registered callables
  (passed in from `TcpdfEngine`). No domain logic.
- This is the single, intentional `extends TCPDF`.

### 4. `Wrapper` (repurposed — no longer `extends TCPDF`)

- Composes a `PdfEngineInterface` (constructor-injected; default `TcpdfEngine`).
- Keeps all domain logic: `$fontFiles` language map + `getCurrentFont`,
  header/footer **content** rendering, `convertExternalImagesToBase64`,
  `concatenatePaths`, `addFaqToc`, and the phpMyFAQ setters
  (`setCategory`/`setCategories`/`setQuestion`/`setConfig`/`setFaq`/
  `isFullExport`/`enableBookmarks`).
- **Facade delegation:** native methods `Pdf.php` calls (`Write`, `Ln`,
  `WriteHTML`, `SetFont`, `AddPage`, `Open`, `Output`, `SetMargins`,
  `SetHeaderMargin`, `SetFooterMargin`, `SetDisplayMode`, `SetAuthor`,
  `SetCreator`, `SetTitle`, `Bookmark`, `setPrintHeader`) become thin delegations
  to the engine. `getCurrentFont` stays domain logic.
- Old overrides become engine callbacks:
  - `Header()`/`Footer()` content → registered via `engine->onHeader/onFooter`.
  - `Image()` path-security + base64 → registered via `engine->onImage`.
  - `WriteHTML()` preprocessing (`convertExternalImagesToBase64`) → done in the
    Facade `WriteHTML` before calling `engine->writeHtml`.

### Data flow (unchanged externally)

`PdfController` / `ExportController` → `Export::create('pdf')` / `new Pdf(...)` →
`Pdf::generate()/generateFile()` → `Wrapper` (Facade + domain) → `PdfEngineInterface`
→ `TcpdfEngine` → `TcpdfDocument extends TCPDF`. Header/Footer/Image flow back via
the registered callbacks into `Wrapper`'s domain methods.

## Error handling

No new error paths. The engine surfaces TCPDF failures exactly as today (return
values / exceptions propagate unchanged through the same call sites).

## Testing

- **Characterization guard:** `PdfTest` (via `Pdf` public API) must pass unchanged
  — it is the byte-level behavior guard.
- **`WrapperTest`:** re-pointed. Engine-lifecycle / callback-wiring assertions move
  to a new `TcpdfEngineTest`; domain-logic tests (font selection, base64
  conversion, header/footer content, `concatenatePaths`, TOC) stay on `Wrapper`,
  now asserted against a test double of `PdfEngineInterface` where they previously
  asserted TCPDF state.
- **New `PdfEngineInterface` double:** a spy/fake used to assert `Wrapper`
  delegates and registers callbacks correctly.
- No output assertions weakened; controller PDF tests unchanged.

## Out of scope

- The actual `tc-lib-pdf` engine implementation and the HTML-rendering gap it
  introduces (no `writeHtml`) — deferred to the migration project.
- Decomposing `Wrapper`'s domain logic further (image/header/footer/font into
  separate services) — explicitly deferred.
- Any change to fonts, constants values, or PDF layout.

## Success criteria

1. No file outside `Engine\TcpdfEngine` / `Engine\TcpdfDocument` references TCPDF
   (no `use TCPDF`, `extends TCPDF`, `new TCPDF`, or `K_*`/`PDF_*` TCPDF constants).
2. `Wrapper` no longer `extends TCPDF`.
3. `Pdf.php` public API and behavior unchanged; `composer test` green
   (PdfTest, WrapperTest→repointed, controller tests).
4. The seam for the future engine swap is a single interface
   (`PdfEngineInterface`).
