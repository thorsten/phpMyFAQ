<?php

/**
 * Test case for the setup wizard templates
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
 * @since     2026-08-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Twig\TwigWrapper;
use PHPUnit\Framework\TestCase;

class SetupTemplateTest extends TestCase
{
    /**
     * The install form must post to the front controller with PATH_INFO
     * ("./index.php/install") instead of the virtual URL "./install", so the
     * setup also works when URL rewriting (mod_rewrite / try_files) is not
     * configured yet - a fresh Debian Apache ships with AllowOverride None
     * and mod_rewrite disabled, which turned the virtual URL into a 404.
     *
     * @throws Exception
     */
    public function testInstallFormDoesNotRequireUrlRewriting(): void
    {
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates', isSetup: true);
        $template = $twigWrapper->loadTemplate('@setup/index.twig');

        $html = $template->render([]);

        static::assertStringContainsString('action="./index.php/install"', $html);
        static::assertStringNotContainsString('action="./install"', $html);
    }
}
