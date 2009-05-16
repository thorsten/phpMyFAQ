<?php
/**
 * Static test suite for PHPUnit
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Tests
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-05-16
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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
 */

require_once 'PHPUnit/Framework.php';
require_once 'Package/AllTests.php';

/**
 * AllTests
 * 
 * @package    phpMyFAQ
 * @subpackage PMF_Tests
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-05-16
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
 */
class AllTests extends PHPUnit_Framework_TestSuite
{
    /**
     * Constructs the test suite handler.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->setName('AllTests');
    }
    
    /**
     * Creates the suite.
     * 
     * return AllTests
     */
    public static function suite()
    {
        $suite = new PHPUnit_Framework_TestSuite('phpMyFAQ');
        
        return $suite;
    }
}

