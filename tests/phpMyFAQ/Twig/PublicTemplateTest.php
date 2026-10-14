<?php

/**
 * Test case for the public (default) templates
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
 * @since     2026-10-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig;

use PHPUnit\Framework\TestCase;

class PublicTemplateTest extends TestCase
{
    /**
     * The public layout must apply the stored or preferred color scheme
     * before the first paint, i.e. with an inline script placed in <head>
     * before any stylesheet. Otherwise visitors with a dark OS theme get a
     * light flash before the ThemeSwitcher flips data-bs-theme on
     * DOMContentLoaded (see GitHub issue #4761).
     */
    public function testPublicLayoutAppliesThemeBeforeFirstPaint(): void
    {
        $html = (string) file_get_contents(PMF_ROOT_DIR . '/assets/templates/default/index.twig');

        $scriptPosition = strpos($html, "localStorage.getItem('pmf-theme')");
        $stylesheetPosition = strpos($html, 'rel="stylesheet"');

        static::assertNotFalse($scriptPosition, 'Inline theme script is missing from the public layout.');
        static::assertNotFalse($stylesheetPosition);
        static::assertLessThan($stylesheetPosition, $scriptPosition, 'Inline theme script must precede stylesheets.');
        static::assertStringContainsString('prefers-color-scheme: dark', $html);
        static::assertStringContainsString("setAttribute('data-bs-theme'", $html);
    }
}
