<?php
/**
 * Test case for AdministrationHelper
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link https://www.phpmyfaq.de
 * @since 2014-08-31
 */

use phpMyFAQ\Helper\AdministrationHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class AdministrationTest
 */
class AdministrationHelperTest extends TestCase
{
    /** @var AdministrationHelper */
    protected $instance;

    public function testRenderMetaRobotsDropdown()
    {
        $expected = '<option selected>index, follow</option><option>index, nofollow</option><option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }

    protected function setUp(): void
    {
        $this->instance = new AdministrationHelper();
    }
} 
