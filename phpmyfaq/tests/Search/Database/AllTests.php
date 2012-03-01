<?php
/**
 * Test suite for PMF_Search_Database related classes
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
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-03
 */

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

require_once 'PMF_Search_Database_MysqlTest.php';
require_once 'PMF_Search_Database_PgsqlTest.php';

/**
 * AllTests
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2010 phpMyFAQ Team
 * @since     2009-05-16
 */
class PMF_Search_Database_AllTests
{
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ PMF_Search Database');
        
        //$suite->addTestSuite('PMF_Search_Database_MysqlTest');
        //$suite->addTestSuite('PMF_Search_Database_PgsqlTest');
        
        return $suite;
    }
}