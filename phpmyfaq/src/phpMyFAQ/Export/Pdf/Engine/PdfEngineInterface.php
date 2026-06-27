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
