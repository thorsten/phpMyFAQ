<?php

/**
 * PhpConfigurator Test.
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
 * @since     2026-02-08
 */

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpConfigurator::class)]
class PhpConfiguratorTest extends TestCase
{
    public function testFixIncludePathEnsuresDotIsPresent(): void
    {
        PhpConfigurator::fixIncludePath();

        $paths = explode(PATH_SEPARATOR, ini_get('include_path'));
        $this->assertContains('.', $paths);
    }

    public function testConfigurePcreSetsLimits(): void
    {
        PhpConfigurator::configurePcre();

        $this->assertEquals('100000000', ini_get('pcre.backtrack_limit'));
        $this->assertEquals('100000000', ini_get('pcre.recursion_limit'));
    }

    public function testConfigureSessionSetsIniValues(): void
    {
        PhpConfigurator::configureSession();

        $this->assertEquals('1', ini_get('session.use_only_cookies'));
        $this->assertEquals('0', ini_get('session.use_trans_sid'));
        $this->assertEquals('1', ini_get('session.cookie_httponly'));
    }
}
