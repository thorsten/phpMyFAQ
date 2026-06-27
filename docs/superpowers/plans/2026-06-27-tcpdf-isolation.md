# TCPDF Isolation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Confine all TCPDF references behind a single `PdfEngineInterface` + `TcpdfEngine` adapter so the future `tc-lib-pdf` migration touches one seam — with byte-identical PDF output.

**Architecture:** A mid-level `PdfEngine` interface mirrors the PDF operations `Pdf`/`Wrapper` use. A `TcpdfEngine` adapter implements it and holds a thin `TcpdfDocument extends TCPDF` (the only `extends TCPDF`) that forwards TCPDF's virtual callbacks (`Header`/`Footer`/`Image`) to registered callables. `Wrapper` stops extending TCPDF, composes the engine (Facade: keeps its public method names, delegates native ops), and keeps all phpMyFAQ domain logic.

**Tech Stack:** PHP 8.4 (PER 3.0, strict types), TCPDF 6.11, PHPUnit 13.

**Hard constraint:** No change to generated PDF output. `tests/phpMyFAQ/Export/PdfTest.php` (exercising `Pdf`'s public API) is the characterization guard and must pass unchanged at every step.

**Run the PHP suite with:** `./phpmyfaq/src/libs/bin/phpunit --no-coverage <path>` (NOT `./vendor/bin/phpunit`). Before any full `composer test` or `git commit` (the pre-commit hook runs the full suite), first run `rm -rf .phpunit.cache` — the suite uses `executionOrder="depends,defects"` and a polluted cache makes unrelated `Twig\Extensions` tests fail. Never use `--no-verify`.

---

## File Structure

- **Create** `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/PdfEngineInterface.php` — the mid-level PDF-engine contract (the seam).
- **Create** `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfDocument.php` — thin `final class … extends TCPDF`; forwards `Header`/`Footer`/`Image` to callables. Only `extends TCPDF` in the codebase.
- **Create** `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfEngine.php` — adapter implementing `PdfEngineInterface`; owns the TCPDF constants (moved from `Wrapper`); delegates to a `TcpdfDocument`.
- **Create** `tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php` — lifecycle/delegation/callback tests for the adapter.
- **Modify** `phpmyfaq/src/phpMyFAQ/Export/Pdf/Wrapper.php` — remove `extends TCPDF`; compose `PdfEngineInterface`; Facade-delegate native ops; register `Header`/`Footer`/image-resolver callbacks; keep domain logic; add `applyDefaultLayout()`.
- **Modify** `phpmyfaq/src/phpMyFAQ/Export/Pdf.php` — inject/allow a `Wrapper`; drop the 3 `PDF_MARGIN_*` lines (engine applies defaults); otherwise unchanged (Facade preserves method names).
- **Modify** `tests/phpMyFAQ/Export/Pdf/WrapperTest.php` — re-point: domain tests assert against a `PdfEngineInterface` test double; lifecycle/TCPDF-state assertions move to `TcpdfEngineTest`.

**Interface method set** (union of all calls `Pdf.php` and `Wrapper` make on the wrapper today):
`open`, `output`, `addPage`, `setPrintHeader`, `setDisplayMode`, `setTitle`, `setAuthor`, `setCreator`, `bookmark`, `setMargins`, `setHeaderMargin`, `setFooterMargin`, `setFont`, `setFontSubsetting`, `setImageScale`, `setDefaultMonospacedFont`, `setRtl`, `write`, `ln`, `writeHtml`, `writeHtmlCell`, `image`, `multiCell`, `cell`, `setY`, `setTextColor`, `getTextColor`, `setTextColorRaw`, `getLastH`, `addTocPage`, `addToc`, `endTocPage`, `getAliasNumPage`, `getAliasNbPages`, and callback registration `onHeader(callable)`, `onFooter(callable)`, `onImageResolve(callable)`.

> Each interface method maps 1:1 onto the identically-semantic TCPDF method (camelCased). The Facade in `Wrapper` keeps the PascalCase names `Pdf.php` already calls (e.g. `Wrapper::WriteHTML` → `engine->writeHtml`).

---

## Task 1: `PdfEngineInterface`

**Files:**
- Create: `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/PdfEngineInterface.php`
- Test: (covered via `TcpdfEngineTest` in Task 2; no standalone test for an interface)

- [ ] **Step 1: Create the interface**

```php
<?php

/**
 * Mid-level PDF engine contract for phpMyFAQ PDF export.
 *
 * Confines the underlying PDF library (currently TCPDF) behind a single seam so
 * the engine can be swapped (e.g. for tc-lib-pdf) without touching the export
 * layer. Method semantics mirror the TCPDF operations phpMyFAQ relies on.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

interface PdfEngineInterface
{
    // Lifecycle
    public function open(): void;

    public function output(string $name, string $dest): string;

    public function addPage(): void;

    public function setPrintHeader(bool $val): void;

    public function setDisplayMode(mixed $zoom): void;

    // Document metadata
    public function setTitle(string $title): void;

    public function setAuthor(string $author): void;

    public function setCreator(string $creator): void;

    public function bookmark(string $txt, int $level = 0, float $y = -1): void;

    // Layout / fonts
    public function setMargins(float $left, float $top, float $right = -1): void;

    public function setHeaderMargin(float $margin): void;

    public function setFooterMargin(float $margin): void;

    public function setFont(string $family, string $style = '', float $size = 0): void;

    public function setFontSubsetting(bool $enable): void;

    public function setImageScale(float $scale): void;

    public function setDefaultMonospacedFont(string $font): void;

    public function setRtl(bool $enable): void;

    // Content
    public function write(float $h, string $txt): void;

    public function ln(?float $h = null): void;

    public function writeHtml(string $html, bool $ln = true, bool $fill = false, bool $reseth = false, bool $cell = false, string $align = ''): void;

    public function writeHtmlCell(float $w, float $h, ?float $x, ?float $y, string $html, mixed $border = 0, int $ln = 0, bool $fill = false, bool $reseth = true, string $align = ''): void;

    public function image(string $file, ?float $x, ?float $y, float $w, float $h, string $type, string $link): void;

    public function multiCell(float $w, float $h, string $txt, mixed $border = 0, string $align = 'J'): void;

    public function cell(float $w, float $h, string $txt, mixed $border = 0, int $ln = 0, string $align = '', bool $fill = false, string $link = ''): void;

    public function setY(float $y): void;

    public function setTextColor(int $col1, int $col2 = -1, int $col3 = -1): void;

    public function getTextColor(): string;

    public function setTextColorRaw(string $color): void;

    public function getLastH(): float;

    // Table of contents
    public function addTocPage(): void;

    public function addToc(int $page, string $numbersfont, string $filler, string $tocName, string $style, array $color): void;

    public function endTocPage(): void;

    public function getAliasNumPage(): string;

    public function getAliasNbPages(): string;

    // Render-time callbacks (invoked when the underlying library fires its hooks)
    public function onHeader(callable $renderer): void;

    public function onFooter(callable $renderer): void;

    /**
     * Image resolver. Receives ($file, $type) and returns [resolvedFile, resolvedType]
     * to draw, or null to skip the image entirely.
     */
    public function onImageResolve(callable $resolver): void;
}
```

- [ ] **Step 2: Verify it parses**

Run: `php -l phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/PdfEngineInterface.php`
Expected: `No syntax errors detected`.

- [ ] **Step 3: Commit**

```bash
git add phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/PdfEngineInterface.php
git commit -m "feat(pdf): add PdfEngineInterface seam for PDF library isolation"
```
(Pre-commit hook runs the full suite — `rm -rf .phpunit.cache` first; let it finish; no `--no-verify`.)

---

## Task 2: `TcpdfDocument` + `TcpdfEngine` adapter

**Files:**
- Create: `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfDocument.php`
- Create: `phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfEngine.php`
- Test: `tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php`

- [ ] **Step 1: Write a failing test**

Create `tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php`:

```php
<?php

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

use PHPUnit\Framework\TestCase;

final class TcpdfEngineTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        self::assertInstanceOf(PdfEngineInterface::class, new TcpdfEngine());
    }

    public function testOutputsNonEmptyPdfStringForASimplePage(): void
    {
        $engine = new TcpdfEngine();
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        $engine->writeHtml('<p>hello</p>');
        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
    }

    public function testImageResolverSkipWhenResolverReturnsNull(): void
    {
        $engine = new TcpdfEngine();
        $engine->onImageResolve(static fn(string $file, string $type): ?array => null);
        $engine->open();
        $engine->setPrintHeader(false);
        $engine->addPage();
        // An <img> whose resolver returns null must be skipped without error.
        $engine->writeHtml('<img src="content/user/images/does-not-matter.png">');
        $pdf = $engine->output('test.pdf', 'S');

        self::assertStringStartsWith('%PDF', $pdf);
    }
}
```

- [ ] **Step 2: Run it to verify it fails**

Run: `./phpmyfaq/src/libs/bin/phpunit --no-coverage tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php`
Expected: FAIL — `Class "phpMyFAQ\Export\Pdf\Engine\TcpdfEngine" not found`.

- [ ] **Step 3: Create `TcpdfDocument`**

```php
<?php

/**
 * Thin TCPDF subclass that forwards TCPDF's virtual callbacks to registered
 * callables. This is the only `extends TCPDF` in phpMyFAQ; it contains no
 * domain logic.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

use TCPDF;

final class TcpdfDocument extends TCPDF
{
    /** @var (callable():void)|null */
    private $headerRenderer = null;

    /** @var (callable():void)|null */
    private $footerRenderer = null;

    /** @var (callable(string,string):?array)|null */
    private $imageResolver = null;

    public function setHeaderRenderer(?callable $renderer): void
    {
        $this->headerRenderer = $renderer;
    }

    public function setFooterRenderer(?callable $renderer): void
    {
        $this->footerRenderer = $renderer;
    }

    public function setImageResolver(?callable $resolver): void
    {
        $this->imageResolver = $resolver;
    }

    #[\Override]
    public function Header(): void
    {
        if ($this->headerRenderer !== null) {
            ($this->headerRenderer)();
        }
    }

    #[\Override]
    public function Footer(): void
    {
        if ($this->footerRenderer !== null) {
            ($this->footerRenderer)();
        }
    }

    #[\Override]
    /* @mago-ignore lint:excessive-parameter-list */
    public function Image(
        $file,
        $x = null,
        $y = null,
        $w = 0,
        $h = 0,
        $type = '',
        $link = '',
        $align = '',
        $resize = false,
        $dpi = 300,
        $palign = '',
        $ismask = false,
        $imgmask = false,
        $border = 0,
        $fitbox = false,
        $hidden = false,
        $fitonpage = false,
        $alt = false,
        $altimgs = [],
    ): void {
        if ($this->imageResolver === null) {
            parent::Image($file, $x, $y, $w, $h, $type, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
            return;
        }

        $resolved = ($this->imageResolver)((string) $file, (string) $type);
        if ($resolved === null) {
            return; // skip
        }

        [$resolvedFile, $resolvedType] = $resolved;
        parent::Image($resolvedFile, $x, $y, $w, $h, $resolvedType, $link, $align, $resize, $dpi, $palign, $ismask, $imgmask, $border, $fitbox, $hidden, $fitonpage, $alt, $altimgs);
    }
}
```

- [ ] **Step 4: Create `TcpdfEngine`**

Move `defineTcpdfConstants()` / `defineIfMissing()` **verbatim** from `Wrapper.php:128-173` into this class (private static). Each interface method delegates to the `TcpdfDocument`. The constructor applies the engine-level setup that `Wrapper::__construct` did (`setFontSubsetting(false)`, `setImageScale`, `setDefaultMonospacedFont`) and `open()` applies the default margins that `Pdf::__construct` set.

```php
<?php

/**
 * TCPDF-backed implementation of PdfEngineInterface. The only place that
 * references the TCPDF library and its K_*/PDF_* constants.
 *
 * (MPL header as in the other files; @since 2026-06-27)
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf\Engine;

final class TcpdfEngine implements PdfEngineInterface
{
    private readonly TcpdfDocument $document;

    public function __construct()
    {
        self::defineTcpdfConstants();

        $this->document = new TcpdfDocument(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT);
        $this->document->setFontSubsetting(false);
        $this->document->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->document->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
    }

    public function open(): void
    {
        $this->document->Open();
        // Default page layout (previously set in Pdf::__construct via PDF_MARGIN_* constants).
        $this->document->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $this->document->SetHeaderMargin(PDF_MARGIN_HEADER);
        $this->document->SetFooterMargin(PDF_MARGIN_FOOTER);
    }

    public function output(string $name, string $dest): string
    {
        return $this->document->Output($name, $dest);
    }

    public function addPage(): void
    {
        $this->document->AddPage();
    }

    public function setPrintHeader(bool $val): void
    {
        $this->document->setPrintHeader($val);
    }

    public function setDisplayMode(mixed $zoom): void
    {
        $this->document->SetDisplayMode($zoom);
    }

    public function setTitle(string $title): void
    {
        $this->document->SetTitle($title);
    }

    public function setAuthor(string $author): void
    {
        $this->document->SetAuthor($author);
    }

    public function setCreator(string $creator): void
    {
        $this->document->SetCreator($creator);
    }

    public function bookmark(string $txt, int $level = 0, float $y = -1): void
    {
        $this->document->Bookmark($txt, $level, $y);
    }

    public function setMargins(float $left, float $top, float $right = -1): void
    {
        $this->document->SetMargins($left, $top, $right);
    }

    public function setHeaderMargin(float $margin): void
    {
        $this->document->SetHeaderMargin($margin);
    }

    public function setFooterMargin(float $margin): void
    {
        $this->document->SetFooterMargin($margin);
    }

    public function setFont(string $family, string $style = '', float $size = 0): void
    {
        $this->document->SetFont($family, $style, $size);
    }

    public function setFontSubsetting(bool $enable): void
    {
        $this->document->setFontSubsetting($enable);
    }

    public function setImageScale(float $scale): void
    {
        $this->document->setImageScale($scale);
    }

    public function setDefaultMonospacedFont(string $font): void
    {
        $this->document->SetDefaultMonospacedFont($font);
    }

    public function setRtl(bool $enable): void
    {
        $this->document->setRTL($enable);
    }

    public function write(float $h, string $txt): void
    {
        $this->document->Write($h, $txt);
    }

    public function ln(?float $h = null): void
    {
        $this->document->Ln($h);
    }

    public function writeHtml(string $html, bool $ln = true, bool $fill = false, bool $reseth = false, bool $cell = false, string $align = ''): void
    {
        $this->document->writeHTML($html, $ln, $fill, $reseth, $cell, $align);
    }

    public function writeHtmlCell(float $w, float $h, ?float $x, ?float $y, string $html, mixed $border = 0, int $ln = 0, bool $fill = false, bool $reseth = true, string $align = ''): void
    {
        $this->document->writeHTMLCell($w, $h, $x, $y, $html, $border, $ln, $fill, $reseth, $align);
    }

    public function image(string $file, ?float $x, ?float $y, float $w, float $h, string $type, string $link): void
    {
        $this->document->Image($file, $x, $y, $w, $h, $type, $link);
    }

    public function multiCell(float $w, float $h, string $txt, mixed $border = 0, string $align = 'J'): void
    {
        $this->document->MultiCell($w, $h, $txt, $border, $align);
    }

    public function cell(float $w, float $h, string $txt, mixed $border = 0, int $ln = 0, string $align = '', bool $fill = false, string $link = ''): void
    {
        $this->document->Cell($w, $h, $txt, $border, $ln, $align, $fill, $link);
    }

    public function setY(float $y): void
    {
        $this->document->SetY($y);
    }

    public function setTextColor(int $col1, int $col2 = -1, int $col3 = -1): void
    {
        $this->document->SetTextColor($col1, $col2, $col3);
    }

    public function getTextColor(): string
    {
        return $this->document->TextColor;
    }

    public function setTextColorRaw(string $color): void
    {
        $this->document->TextColor = $color;
    }

    public function getLastH(): float
    {
        return $this->document->getLastH();
    }

    public function addTocPage(): void
    {
        $this->document->addTOCPage();
    }

    public function addToc(int $page, string $numbersfont, string $filler, string $tocName, string $style, array $color): void
    {
        $this->document->addTOC($page, $numbersfont, $filler, $tocName, $style, $color);
    }

    public function endTocPage(): void
    {
        $this->document->endTOCPage();
    }

    public function getAliasNumPage(): string
    {
        return $this->document->getAliasNumPage();
    }

    public function getAliasNbPages(): string
    {
        return $this->document->getAliasNbPages();
    }

    public function onHeader(callable $renderer): void
    {
        $this->document->setHeaderRenderer($renderer);
    }

    public function onFooter(callable $renderer): void
    {
        $this->document->setFooterRenderer($renderer);
    }

    public function onImageResolve(callable $resolver): void
    {
        $this->document->setImageResolver($resolver);
    }

    // --- paste defineTcpdfConstants() and defineIfMissing() verbatim from Wrapper.php:128-173 here ---
}
```

Paste `defineTcpdfConstants()` and `defineIfMissing()` exactly as they currently appear in `Wrapper.php` (lines 128-173), unchanged.

- [ ] **Step 5: Run the test to verify it passes**

Run: `./phpmyfaq/src/libs/bin/phpunit --no-coverage tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php`
Expected: PASS (3 tests).

- [ ] **Step 6: Lint**

Run: `composer lint`
Expected: no NEW issues in the three created files (the `excessive-parameter-list` on `TcpdfDocument::Image` is suppressed via the existing `@mago-ignore` annotation, matching the current `Wrapper::Image`).

- [ ] **Step 7: Commit**

```bash
rm -rf .phpunit.cache
git add phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfDocument.php phpmyfaq/src/phpMyFAQ/Export/Pdf/Engine/TcpdfEngine.php tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php
git commit -m "feat(pdf): add TcpdfEngine adapter and thin TcpdfDocument callback sink"
```

---

## Task 3: Repurpose `Wrapper` to compose the engine

**Files:**
- Modify: `phpmyfaq/src/phpMyFAQ/Export/Pdf/Wrapper.php`
- Test: `tests/phpMyFAQ/Export/Pdf/WrapperTest.php` (re-pointed in Task 5)

This is the core change. Work method-by-method; keep `Pdf::generate`/`generateFile` green via `PdfTest` after.

- [ ] **Step 1: Change the class declaration and add the engine**

- Remove `use TCPDF;` (line 30) and add `use phpMyFAQ\Export\Pdf\Engine\PdfEngineInterface;` and `use phpMyFAQ\Export\Pdf\Engine\TcpdfEngine;`.
- Change `class Wrapper extends TCPDF` → `class Wrapper`.
- Add a constructor-injected engine (default `TcpdfEngine` for production; injectable for tests):

```php
    private readonly PdfEngineInterface $engine;

    public function __construct(?PdfEngineInterface $engine = null)
    {
        $this->engine = $engine ?? new TcpdfEngine();

        // RTL + language font selection (phpMyFAQ domain logic; previously in __construct).
        if ('rtl' === Translation::get(key: 'direction')) {
            $this->engine->setRtl(true);
        }

        $metaLanguage = (string) (Translation::get(key: 'metaLanguage') ?? '');
        if ($metaLanguage !== '' && array_key_exists($metaLanguage, $this->fontFiles)) {
            $this->currentFont = (string) $this->fontFiles[$metaLanguage];
        }

        // Register render-time callbacks so TCPDF's Header/Footer/Image hooks
        // call back into this renderer's domain logic.
        $this->engine->onHeader(fn(): void => $this->renderHeader());
        $this->engine->onFooter(fn(): void => $this->renderFooter());
        $this->engine->onImageResolve(fn(string $file, string $type): ?array => $this->resolveImage($file, $type));
    }
```

- Delete `defineTcpdfConstants()` and `defineIfMissing()` (now in `TcpdfEngine`).

- [ ] **Step 2: Add the Facade delegation methods**

Add thin methods (PascalCase, the names `Pdf.php` already calls) that forward to the engine. Keep the exact public signatures `Pdf.php` uses:

```php
    public function Open(): void { $this->engine->open(); }
    public function Output(string $name, string $dest): string { return $this->engine->output($name, $dest); }
    public function AddPage(): void { $this->engine->addPage(); }
    public function setPrintHeader(bool $val = true): void { $this->engine->setPrintHeader($val); }
    public function SetDisplayMode(mixed $zoom): void { $this->engine->setDisplayMode($zoom); }
    public function SetMargins(float $left, float $top, float $right = -1): void { $this->engine->setMargins($left, $top, $right); }
    public function SetHeaderMargin(float $margin): void { $this->engine->setHeaderMargin($margin); }
    public function SetFooterMargin(float $margin): void { $this->engine->setFooterMargin($margin); }
    public function SetCreator(string $creator): void { $this->engine->setCreator($creator); }
    public function SetTitle(string $title): void { $this->engine->setTitle($title); }
    public function SetAuthor(string $author): void { $this->engine->setAuthor($author); }
    public function SetFont(string $family, string $style = '', float $size = 0): void { $this->engine->setFont($family, $style, $size); }
    public function Ln(?float $h = null): void { $this->engine->ln($h); }
    public function Write(float $h, string $txt): void { $this->engine->write($h, $txt); }
    public function Bookmark(string $txt, int $level = 0, float $y = -1): void { $this->engine->bookmark($txt, $level, $y); }

    public function WriteHTML($html, $ln = true, $fill = false, $reseth = false, $cell = false, $align = ''): void
    {
        // Preprocess external images to base64 (domain logic), then delegate.
        $this->engine->writeHtml($this->convertExternalImagesToBase64((string) $html), $ln, $fill, $reseth, $cell, $align);
    }

    public function applyDefaultLayout(): void
    {
        // No-op kept for clarity: TcpdfEngine::open() already applies default margins.
    }
```

> Note: `applyDefaultLayout()` exists so `Pdf.php` has a domain-named hook if needed; default margins are applied by `TcpdfEngine::open()`. If the implementer prefers, drop `applyDefaultLayout()` and rely solely on `open()` — but keep `Pdf.php` free of `PDF_MARGIN_*`.

- [ ] **Step 3: Convert `Header()`/`Footer()` to renderer methods**

Rename `Header()` → `renderHeader()` and `Footer()` → `renderFooter()` (remove the `#[\Override]` attributes — they are no longer TCPDF overrides). Inside them, replace every `$this->X(...)` TCPDF call with the engine equivalent:
- `$this->SetTextColor(...)` → `$this->engine->setTextColor(...)`
- `$this->SetFont(...)` → `$this->engine->setFont(...)`
- `$this->writeHTMLCell(...)` → `$this->engine->writeHtmlCell(...)`
- `$this->Ln()` → `$this->engine->ln()`
- `$this->MultiCell(...)` → `$this->engine->multiCell(...)`
- `$this->SetMargins(...)` → `$this->engine->setMargins(...)` (keep `PDF_MARGIN_LEFT`/`PDF_MARGIN_RIGHT` replaced — see note below)
- `$this->getLastH()` → `$this->engine->getLastH()`
- `$this->SetY(...)` → `$this->engine->setY(...)`
- `$this->Cell(...)` → `$this->engine->cell(...)`
- `$this->getAliasNumPage()` / `$this->getAliasNbPages()` → `$this->engine->getAliasNumPage()` / `...NbPages()`
- `$this->TextColor` (read at line 288) → `$this->engine->getTextColor()`; assignment at line 330 (`$this->TextColor = $currentTextColor;`) → `$this->engine->setTextColorRaw($currentTextColor);`

`renderHeader()` references `PDF_MARGIN_LEFT`/`PDF_MARGIN_RIGHT` (current line 249). To keep TCPDF constants out of `Wrapper`, add private constants `private const float MARGIN_LEFT = 15;` and `private const float MARGIN_RIGHT = 15;` to `Wrapper` (matching the values in `defineTcpdfConstants`) and use them. Keep `setCustomHeader()`, `setCustomFooter()` unchanged (pure domain logic).

- [ ] **Step 4: Convert `Image()` to an image resolver**

Replace the `Image()` override with a private `resolveImage(string $file, string $type): ?array` that returns `[resolvedFile, resolvedType]` to draw or `null` to skip. Port the existing logic (`Wrapper.php:449-555`) so that each former `parent::Image($file, …)` becomes `return [$file, $type];`, each `parent::Image('@'.$decoded, …)` becomes `return ['@' . $decoded, $type];`, and each `return;` (skip) becomes `return null;`:

```php
    private function resolveImage(string $file, string $type): ?array
    {
        if ($file !== '' && ($file[0] === '@' || $file[0] === '*')) {
            return [$file, $type];
        }

        if (str_starts_with($file, 'data:')) {
            if (preg_match('#^data:[^;]+;base64,(.+)$#', $file, $matches)) {
                $decoded = base64_decode($matches[1], strict: true);
                if ($decoded !== false && $this->checkBase64Image($decoded)) {
                    return ['@' . $decoded, $type];
                }
            }
            return null;
        }

        $path = parse_url($file, PHP_URL_PATH);
        if ($path === false || $path === null || $path === '') {
            return null;
        }

        $path = urldecode($path);
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $resolvedPath = $this->concatenatePaths(PMF_ROOT_DIR, $path);
        if ($resolvedPath === '' || !$this->isWithinRoot($resolvedPath)) {
            return null;
        }

        if (!is_file($resolvedPath) || !is_readable($resolvedPath)) {
            return null;
        }

        $data = file_get_contents($resolvedPath);
        if ($data === false) {
            return null;
        }

        if ($this->checkBase64Image($data)) {
            return ['@' . $data, $type];
        }

        return [$path, $type];
    }
```

Keep `checkBase64Image`, `concatenatePaths`, `isWithinRoot`, `convertExternalImagesToBase64`, `fetchExternalImage`, `validateImageData`, `getImageMimeType` **verbatim** (pure domain logic).

- [ ] **Step 5: Keep domain setters unchanged**

`setCategory`, `setQuestion`, `setCategories`, `setConfig`, `setFaq`, `getCurrentFont`, `addFaqToc`, and the public properties (`$enableBookmarks`, `$isFullExport`, `$categories`, `$category`, `$faq`) stay. In `addFaqToc()`, replace its TCPDF self-calls (`addTOCPage`, `SetFont`, `MultiCell`, `Ln`, `addTOC`, `endTOCPage`) with the engine equivalents (`$this->engine->addTocPage()`, `->setFont(...)`, `->multiCell(...)`, `->ln()`, `->addToc(...)`, `->endTocPage()`).

- [ ] **Step 6: Run the characterization guard**

Run: `./phpmyfaq/src/libs/bin/phpunit --no-coverage tests/phpMyFAQ/Export/PdfTest.php`
Expected: PASS (unchanged). This proves the public `Pdf` behavior — and thus PDF output — is preserved. If anything fails, fix the delegation/port until green before committing.

- [ ] **Step 7: Lint**

Run: `composer lint`
Expected: no NEW issues in `Wrapper.php`.

- [ ] **Step 8: Commit**

```bash
rm -rf .phpunit.cache
git add phpmyfaq/src/phpMyFAQ/Export/Pdf/Wrapper.php
git commit -m "refactor(pdf): compose PdfEngine in Wrapper instead of extending TCPDF"
```

---

## Task 4: Update `Pdf.php` to drop TCPDF constants

**Files:**
- Modify: `phpmyfaq/src/phpMyFAQ/Export/Pdf.php:67-72`
- Test: `tests/phpMyFAQ/Export/PdfTest.php`

- [ ] **Step 1: Remove the `PDF_MARGIN_*` lines**

In `Pdf::__construct`, delete lines 70-72 (the `SetMargins`/`SetHeaderMargin`/`SetFooterMargin` calls using `PDF_MARGIN_*`). `TcpdfEngine::open()` (called via `$this->wrapper->Open()` on line 68) now applies those exact default margins. Keep `Open()` (line 68) and `SetDisplayMode(zoom: 'real')` (line 69).

Resulting constructor block:
```php
        $this->wrapper = new Wrapper();
        $this->wrapper->setConfig($this->config);

        // Set PDF options
        $this->wrapper->Open();
        $this->wrapper->SetDisplayMode(zoom: 'real');
```

- [ ] **Step 2: Run the guard**

Run: `./phpmyfaq/src/libs/bin/phpunit --no-coverage tests/phpMyFAQ/Export/PdfTest.php tests/phpMyFAQ/ExportTest.php`
Expected: PASS.

- [ ] **Step 3: Verify no TCPDF constant remains in Pdf.php**

Run: `grep -nE "PDF_[A-Z]|K_[A-Z]|TCPDF" phpmyfaq/src/phpMyFAQ/Export/Pdf.php`
Expected: no output.

- [ ] **Step 4: Commit**

```bash
rm -rf .phpunit.cache
git add phpmyfaq/src/phpMyFAQ/Export/Pdf.php
git commit -m "refactor(pdf): drop TCPDF margin constants from Pdf (engine applies defaults)"
```

---

## Task 5: Re-point `WrapperTest`

**Files:**
- Modify: `tests/phpMyFAQ/Export/Pdf/WrapperTest.php`

The old tests asserted TCPDF state on a `Wrapper extends TCPDF`. Now `Wrapper` composes a `PdfEngineInterface`. Re-point each test to one of two homes:

- [ ] **Step 1: Move lifecycle/TCPDF-state tests to `TcpdfEngineTest`**

Tests that asserted TCPDF rendering/state (constructor produces a usable document, `WriteHTML` produces output, image drawing reaches TCPDF) belong on the engine. Add equivalents to `tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php` using the real `TcpdfEngine` and asserting on the `output('x','S')` string (starts with `%PDF`) — do not assert exact bytes.

- [ ] **Step 2: Re-point domain tests to use a `PdfEngineInterface` spy**

For `Wrapper` domain behavior (font-by-language selection via `getCurrentFont`, `convertExternalImagesToBase64`, `concatenatePaths`, `isWithinRoot`, `resolveImage` skip/transform decisions, header/footer content, `addFaqToc` orchestration), construct `new Wrapper($spy)` where `$spy` is a test double implementing `PdfEngineInterface`. Assert the `Wrapper` calls the expected engine methods / registers callbacks, and that `resolveImage`/`convertExternalImagesToBase64` return the expected values.

Provide the spy as an anonymous class or a PHPUnit mock of `PdfEngineInterface`. Example shape for a header-content assertion:
```php
public function testHeaderRendererWritesCustomHeader(): void
{
    $engine = $this->createMock(PdfEngineInterface::class);
    $captured = [];
    $engine->method('onHeader')->willReturnCallback(function (callable $cb) use (&$captured): void {
        $captured['header'] = $cb;
    });
    // ... stub onFooter/onImageResolve/setRtl as needed ...
    $wrapper = new Wrapper($engine);
    $wrapper->setConfig($this->makeConfigStub());
    $wrapper->setCategories([1 => ['name' => 'Cat']]);
    $wrapper->setCategory(1);

    $engine->expects(self::atLeastOnce())->method('writeHtmlCell');
    ($captured['header'])(); // invoke the registered header renderer
}
```

- [ ] **Step 3: Delete or migrate tests that asserted `instanceof TCPDF`**

Any test asserting `Wrapper` is a `TCPDF` must be removed (the isolation makes that false by design). Replace with `self::assertInstanceOf(Wrapper::class, $wrapper)` where a smoke check is still useful.

- [ ] **Step 4: Run the PDF/export test group**

Run: `./phpmyfaq/src/libs/bin/phpunit --no-coverage tests/phpMyFAQ/Export/`
Expected: PASS (PdfTest, WrapperTest re-pointed, TcpdfEngineTest, JsonTest, ExportTest).

- [ ] **Step 5: Commit**

```bash
rm -rf .phpunit.cache
git add tests/phpMyFAQ/Export/Pdf/WrapperTest.php tests/phpMyFAQ/Export/Pdf/Engine/TcpdfEngineTest.php
git commit -m "test(pdf): re-point Wrapper tests onto the PdfEngine boundary"
```

---

## Task 6: Verification — TCPDF confined + full suite

**Files:** none (verification only).

- [ ] **Step 1: Prove TCPDF is confined to the engine**

Run:
```bash
grep -rEn "extends TCPDF|use TCPDF|new TCPDF" phpmyfaq/src/phpMyFAQ/ | grep -v "Export/Pdf/Engine/TcpdfDocument.php"
```
Expected: **no output** (the only `extends TCPDF` / `use TCPDF` is in `TcpdfDocument.php`).

Run:
```bash
grep -rEn "\bK_[A-Z_]+|\bPDF_[A-Z_]+" phpmyfaq/src/phpMyFAQ/ | grep -v "Export/Pdf/Engine/TcpdfEngine.php"
```
Expected: no output (TCPDF constants only in `TcpdfEngine.php`).

- [ ] **Step 2: Confirm `Wrapper` no longer extends TCPDF**

Run: `grep -n "class Wrapper" phpmyfaq/src/phpMyFAQ/Export/Pdf/Wrapper.php`
Expected: `class Wrapper` (no `extends`).

- [ ] **Step 3: Full suite + lint**

Run: `rm -rf .phpunit.cache && composer test && composer lint`
Expected: all green; lint clean.

- [ ] **Step 4: Build is unaffected (no asset changes)** — skip; PDF is server-side only.

- [ ] **Step 5: Final commit (if any lint autofixes)**

```bash
rm -rf .phpunit.cache
git add -A
git commit -m "chore(pdf): finalize TCPDF isolation behind PdfEngine boundary"
```

---

## Self-Review Notes

- **Spec coverage:** `PdfEngineInterface` (Task 1) ✓; `TcpdfEngine` + thin `TcpdfDocument extends TCPDF` callback sink (Task 2) ✓; `Wrapper` composes engine, no longer extends TCPDF, Facade keeps `Pdf.php` calls working (Task 3) ✓; constants moved to engine (Task 2) ✓; `Pdf.php` freed of TCPDF constants (Task 4) ✓; tests re-pointed with `PdfTest` as guard (Task 5) ✓; success criteria 1–4 verified (Task 6) ✓.
- **Wrinkles handled:** `Image` mutates `$type` → resolver returns `[file, type]`; `TextColor` property → `getTextColor`/`setTextColorRaw`; `Pdf.php` `PDF_MARGIN_*` → engine `open()` applies defaults; RTL + font-by-language stays domain logic in `Wrapper`.
- **Naming consistency:** interface methods camelCase; `Wrapper` Facade keeps PascalCase public names (`WriteHTML`, `AddPage`, …) that `Pdf.php` calls; engine callback names `onHeader`/`onFooter`/`onImageResolve` used identically in `TcpdfEngine`, `TcpdfDocument`, and `Wrapper`.
- **Behavior guard:** `PdfTest` runs after Tasks 3 and 4 and in the full suite; no PDF-byte assertions are introduced or weakened.
