<?php
/**
 * Main test suite for phpMyFAQ
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
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2015 phpMyFAQ Team
 * @since     2009-05-16
 */

// Define the named constant used as a check by any included PHP file
define('IS_VALID_PHPMYFAQ', null);

/**
 * AllTests
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2015 phpMyFAQ Team
 * @since     2009-05-16
 */
class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ AllTests');

        $suite->addTestSuite('PMFTest_Attachment_Filesystem_File_VanillaTest');
        $suite->addTestSuite('PMFTest_ConfigurationTest');
        $suite->addTestSuite('PMFTest_Helper_AdministrationTest');
        $suite->addTestSuite('PMFTest_Instance_ClientTest');
        $suite->addTestSuite('PMFTest_LinkTest');
        $suite->addTestSuite('PMFTest_LinkverifierTest');
        $suite->addTestSuite('PMFTest_Search_DatabaseTest');
        $suite->addTestSuite('PMFTest_Search_FactoryTest');
        $suite->addTestSuite('PMFTest_Search_ResultsetTest');
        
        return $suite;
    }
}
