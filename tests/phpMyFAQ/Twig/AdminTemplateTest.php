<?php

/**
 * Test case for the administration templates
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

class AdminTemplateTest extends TestCase
{
    /**
     * The admin layout must apply the stored or preferred color scheme
     * before the first paint, i.e. with an inline script placed in <head>
     * before any stylesheet, to avoid a light flash for dark-mode users
     * (see GitHub issue #4761).
     */
    public function testAdminLayoutAppliesThemeBeforeFirstPaint(): void
    {
        $html = (string) file_get_contents(PMF_ROOT_DIR . '/assets/templates/admin/index.twig');

        $scriptPosition = strpos($html, "localStorage.getItem('pmf-theme')");
        $stylesheetPosition = strpos($html, 'rel="stylesheet"');

        static::assertNotFalse($scriptPosition, 'Inline theme script is missing from the admin layout.');
        static::assertNotFalse($stylesheetPosition);
        static::assertLessThan($stylesheetPosition, $scriptPosition, 'Inline theme script must precede stylesheets.');
        static::assertStringContainsString('prefers-color-scheme: dark', $html);
        static::assertStringContainsString("setAttribute('data-bs-theme'", $html);
    }

    /**
     * A forced layout mode is rendered server-side; the inline script must then be
     * omitted so neither localStorage nor the OS preference can override it, and the
     * ThemeSwitcher must be told to stay out of the way (see GitHub issue #4762).
     */
    public function testAdminLayoutSkipsTheInlineThemeScriptWhenALayoutModeIsForced(): void
    {
        $html = (string) file_get_contents(PMF_ROOT_DIR . '/assets/templates/admin/index.twig');

        $guardPosition = strpos($html, '{% if forcedLayoutMode is not defined or forcedLayoutMode is null %}');
        $scriptPosition = strpos($html, "localStorage.getItem('pmf-theme')");

        static::assertNotFalse($guardPosition, 'Inline theme script is not guarded by the forced layout mode.');
        static::assertNotFalse($scriptPosition);
        static::assertLessThan($scriptPosition, $guardPosition, 'Forced layout mode guard must wrap the inline script.');
        static::assertStringContainsString('data-pmf-forced-theme="{{ forcedLayoutMode }}"', $html);
        static::assertStringContainsString('data-bs-theme="{{ forcedLayoutMode ?? (', $html);
        static::assertStringContainsString("root.getAttribute('data-pmf-allow-user-theme') !== 'false'", $html);
        static::assertStringContainsString("root.getAttribute('data-pmf-default-theme')", $html);
    }
}
