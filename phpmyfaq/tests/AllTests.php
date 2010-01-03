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
 * @copyright 2009-2010 phpMyFAQ Team
 * @since     2009-05-16
 */

date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL | E_STRICT);

if (defined('PHPUNIT_MAIN_METHOD') === false) {
    define('PHPUNIT_MAIN_METHOD', 'AllTests::main');
}

require_once 'PHPUnit/Framework/TestSuite.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

/**
 * AllTests
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2009-2010 phpMyFAQ Team
 * @since     2009-05-16
 */
class AllTests extends PHPUnit_Framework_TestSuite
{
    public static function main()
    {
        $parameters = array(
            'backupGlobals'          => false,
            'backupStaticAttributes' => false);
        
        PHPUnit_TextUI_TestRunner::run(self::suite(), $parameters);
    }

    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ');
        
        return $suite;
    }
}

if (PHPUNIT_MAIN_METHOD === 'AllTests::main') {
    AllTests::main();
}