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

    public function setHeaderRenderer(?callable $renderer): void
    {
        $this->headerRenderer = $renderer;
    }

    public function setFooterRenderer(?callable $renderer): void
    {
        $this->footerRenderer = $renderer;
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
}
