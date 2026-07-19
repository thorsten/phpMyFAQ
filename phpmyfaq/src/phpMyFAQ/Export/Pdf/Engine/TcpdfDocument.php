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

use Override;
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

    /**
     * @param callable(string, string): (array{0: string, 1: string}|null)|null $resolver
     */
    public function setImageResolver(?callable $resolver): void
    {
        $this->imageResolver = $resolver;
    }

    /**
     * Exposes TCPDF's protected $TextColor (the raw PDF colour command) so the
     * engine can save and restore it. Only this subclass may read/write it.
     */
    public function getTextColorRaw(): string
    {
        return (string) $this->TextColor;
    }

    public function setTextColorRaw(string $color): void
    {
        $this->TextColor = $color;
    }

    #[Override]
    public function Header(): void
    {
        if ($this->headerRenderer !== null) {
            ($this->headerRenderer)();
        }
    }

    #[Override]
    public function Footer(): void
    {
        if ($this->footerRenderer !== null) {
            ($this->footerRenderer)();
        }
    }

    #[Override]
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
            parent::Image(
                $file,
                $x,
                $y,
                $w,
                $h,
                $type,
                $link,
                $align,
                $resize,
                $dpi,
                $palign,
                $ismask,
                $imgmask,
                $border,
                $fitbox,
                $hidden,
                $fitonpage,
                $alt,
                $altimgs,
            );
            return;
        }

        $resolved = ($this->imageResolver)((string) $file, (string) $type);
        if ($resolved === null) {
            return;
        }

        [$resolvedFile, $resolvedType] = $resolved;
        $resolvedFile = (string) $resolvedFile;
        $resolvedType = (string) $resolvedType;
        parent::Image(
            $resolvedFile,
            $x,
            $y,
            $w,
            $h,
            $resolvedType,
            $link,
            $align,
            $resize,
            $dpi,
            $palign,
            $ismask,
            $imgmask,
            $border,
            $fitbox,
            $hidden,
            $fitonpage,
            $alt,
            $altimgs,
        );
    }
}
