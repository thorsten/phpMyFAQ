<?php
/**
 * Test case for PMF_Helper_Administration
 *
 * PHP Version 5.3
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2014-08-31
 */

use phpMyFAQ\Helper\Administration;
use PHPUnit\Framework\TestCase;

/**
 * PMF_Helper_AdministrationTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2012-12-31
 */
class AdministrationTest extends TestCase
{
    /**
     * @var Administration
     */
    protected $instance;

    protected function setUp()
    {
        $this->instance = new Administration();
    }

    public function testRenderMetaRobotsDropdown()
    {
        $expected = '<option selected>index, follow</option><option>index, nofollow</option><option>noindex, follow</option><option>noindex, nofollow</option>';
        $actual   = $this->instance->renderMetaRobotsDropdown('index, follow');

        $this->assertEquals($expected, $actual);
    }
} 