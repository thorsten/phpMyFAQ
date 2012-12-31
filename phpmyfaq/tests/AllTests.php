<?php
/**
 * Main test suite for phpMyFAQ
 * 
 * PHP Version 5.2
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
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2013 phpMyFAQ Team
 * @since     2009-05-16
 */

// Define the named constant used as a check by any included PHP file
define('IS_VALID_PHPMYFAQ', null);

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);

// include PHPUnit
require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

// include Testsuites
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Attachment' . DIRECTORY_SEPARATOR . 'AllTests.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Category' . DIRECTORY_SEPARATOR . 'AllTests.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Faq' . DIRECTORY_SEPARATOR . 'AllTests.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Search' . DIRECTORY_SEPARATOR . 'AllTests.php';

/**
 * AllTests
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2013 phpMyFAQ Team
 * @since     2009-05-16
 */
class AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ AllTests');

        $suite->addTest(Attachment_AllTests::suite());
        $suite->addTest(Category_AllTests::suite());
        $suite->addTest(Faq_AllTests::suite());
        $suite->addTest(Search_AllTests::suite());
        
        return $suite;
    }
}
