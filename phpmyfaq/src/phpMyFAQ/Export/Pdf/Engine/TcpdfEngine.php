<?php

/**
 * TCPDF-backed implementation of PdfEngineInterface. The only class that
 * references the TCPDF library and its K_* and PDF_* constants.
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

final readonly class TcpdfEngine implements PdfEngineInterface
{
    private TcpdfDocument $document;

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

    public function writeHtml(
        string $html,
        bool $ln = true,
        bool $fill = false,
        bool $reseth = false,
        bool $cell = false,
        string $align = '',
    ): void {
        $this->document->writeHTML($html, $ln, $fill, $reseth, $cell, $align);
    }

    public function writeHtmlCell(
        float $w,
        float $h,
        ?float $x,
        ?float $y,
        string $html,
        mixed $border = 0,
        int $ln = 0,
        bool $fill = false,
        bool $reseth = true,
        string $align = '',
    ): void {
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

    public function cell(
        float $w,
        float $h,
        string $txt,
        mixed $border = 0,
        int $ln = 0,
        string $align = '',
        bool $fill = false,
        string $link = '',
    ): void {
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
        return $this->document->getTextColorRaw();
    }

    public function setTextColorRaw(string $color): void
    {
        $this->document->setTextColorRaw($color);
    }

    public function getLastH(): float
    {
        return $this->document->getLastH();
    }

    public function addTocPage(): void
    {
        $this->document->addTOCPage();
    }

    public function addToc(
        int $page,
        string $numbersfont,
        string $filler,
        string $tocName,
        string $style,
        array $color,
    ): void {
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

    private static function defineTcpdfConstants(): void
    {
        // Note: this file lives one directory deeper (Engine/) than the original
        // Wrapper, so the fallback paths carry one extra "../" to resolve identically.
        $pmfRootDir = defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR : __DIR__ . '/../../../../';
        $pmfSrcDir = defined('PMF_SRC_DIR') ? PMF_SRC_DIR : __DIR__ . '/../../../';

        self::defineIfMissing('K_TCPDF_EXTERNAL_CONFIG', true);
        self::defineIfMissing('K_PATH_URL', '');
        self::defineIfMissing('K_PATH_MAIN', $pmfSrcDir . '/libs/tecnickcom/tcpdf/');
        self::defineIfMissing('K_PATH_FONTS', $pmfSrcDir . '/fonts/');
        self::defineIfMissing('K_PATH_CACHE', $pmfRootDir . '/content/user/images/');
        self::defineIfMissing('K_PATH_URL_CACHE', K_PATH_CACHE);
        self::defineIfMissing('K_PATH_IMAGES', $pmfRootDir . '/content/user/images/');
        self::defineIfMissing('K_BLANK_IMAGE', K_PATH_IMAGES . '_blank.png');
        self::defineIfMissing('PDF_PAGE_FORMAT', 'A4');
        self::defineIfMissing('PDF_PAGE_ORIENTATION', 'P');
        self::defineIfMissing('PDF_CREATOR', 'TCPDF');
        self::defineIfMissing('PDF_AUTHOR', 'TCPDF');
        self::defineIfMissing('PDF_HEADER_TITLE', 'phpMyFAQ');
        self::defineIfMissing('PDF_HEADER_STRING', 'by phpMyFAQ - www.phpmyfaq.de');
        self::defineIfMissing('PDF_HEADER_LOGO', 'tcpdf_logo.jpg');
        self::defineIfMissing('PDF_HEADER_LOGO_WIDTH', 30);
        self::defineIfMissing('PDF_UNIT', 'mm');
        self::defineIfMissing('PDF_MARGIN_HEADER', 5);
        self::defineIfMissing('PDF_MARGIN_FOOTER', 10);
        self::defineIfMissing('PDF_MARGIN_TOP', 27);
        self::defineIfMissing('PDF_MARGIN_BOTTOM', 25);
        self::defineIfMissing('PDF_MARGIN_LEFT', 15);
        self::defineIfMissing('PDF_MARGIN_RIGHT', 15);
        self::defineIfMissing('PDF_FONT_NAME_MAIN', 'arialunicid0');
        self::defineIfMissing('PDF_FONT_SIZE_MAIN', 10);
        self::defineIfMissing('PDF_FONT_NAME_DATA', 'arialunicid0');
        self::defineIfMissing('PDF_FONT_SIZE_DATA', 8);
        self::defineIfMissing('PDF_FONT_MONOSPACED', 'DejaVuSansMono');
        self::defineIfMissing('PDF_IMAGE_SCALE_RATIO', 1);
        self::defineIfMissing('HEAD_MAGNIFICATION', 1.1);
        self::defineIfMissing('K_CELL_HEIGHT_RATIO', 1.25);
        self::defineIfMissing('K_TITLE_MAGNIFICATION', 1.3);
        self::defineIfMissing('K_SMALL_RATIO', 2 / 3);
    }

    private static function defineIfMissing(string $name, mixed $value): void
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}
