<?php
/**
 * Test suite for Category related classes
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2010 phpMyFAQ Team
 * @since     2010-01-03
 */

// include PHPUnit
require_once __DIR__ . '/../../vendor/phpunit/phpunit/src/Framework/TestSuite.php';
require_once __DIR__ . '/../../vendor/phpunit/phpunit/src/TextUI/TestRunner.php';

/**
 * Category_AllTests
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2010 phpMyFAQ Team
 * @since     2010-01-03
 */
class Category_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ Category');
        
        return $suite;
    }
}