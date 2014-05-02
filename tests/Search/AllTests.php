<?php
/**
 * Test suite for Search related classes
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-03
 */

// include PHPUnit
require_once __DIR__ . '/../../vendor/phpunit/phpunit/src/Framework/TestSuite.php';
require_once __DIR__ . '/../../vendor/phpunit/phpunit/src/TextUI/TestRunner.php';

require_once 'PMF_Search_DatabaseTest.php';
require_once 'PMF_Search_FactoryTest.php';
require_once 'PMF_Search_ResultsetTest.php';
require_once 'Database' . DIRECTORY_SEPARATOR . 'AllTests.php';

/**
 * Search_AllTests
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-03
 */
class Search_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ Search');
        
        $suite->addTestSuite('PMF_Search_DatabaseTest');
        $suite->addTestSuite('PMF_Search_FactoryTest');
        $suite->addTestSuite('PMF_Search_ResultsetTest');
        $suite->addTest(PMF_Search_Database_AllTests::suite());
        
        return $suite;
    }
}