<?php

/**
 * Bootstrapper Test.
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

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bootstrapper::class)]
class BootstrapperTest extends TestCase
{
    public function testGettersReturnNullBeforeRun(): void
    {
        $bootstrapper = new Bootstrapper();

        $this->assertNull($bootstrapper->getFaqConfig());
        $this->assertNull($bootstrapper->getDb());
        $this->assertNull($bootstrapper->getRequest());
    }
}
