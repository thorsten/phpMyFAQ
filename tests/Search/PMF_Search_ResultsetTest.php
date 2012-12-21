<?php
/**
 * Test case for PMF_Search_Resultset
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
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-24
 */

require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Language.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/String.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/String/Abstract.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/String/Mbstring.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Search/Resultset.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/DB/Driver.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/DB/Sqlite3.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Exception.php';
require_once dirname(dirname(__DIR__)) . '/phpmyfaq/inc/PMF/Configuration.php';

/**
 * Category test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-07-24
 */
class PMF_Search_ResultsetTest extends PHPUnit_Framework_TestCase
{
    private $PMF_Search_Resultset;

    private $PMF_Configuration;

    private $dbHandle;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        
        PMF_String::init('en');

        $this->dbHandle = new PMF_DB_Sqlite3();
        $this->PMF_Configuration = new PMF_Configuration($this->dbHandle);
        
        $this->PMF_Search_Resultset = new PMF_Search_Resultset(
            $this->getMock('PMF_User'),
            $this->getMock('PMF_Faq'),
            $this->PMF_Configuration
        );
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Search_Resultset = null;
        parent::tearDown();
    }
    
    public function testReviewResultset()
    {
        
    }
    
    public function testSetAndGetNumberOfResults()
    {
        $this->PMF_Search_Resultset->setNumberOfResults(array(1,2));
        $this->assertEquals($this->PMF_Search_Resultset->getNumberOfResults(), 2);
        $this->PMF_Search_Resultset->setNumberOfResults(array());
        $this->assertEquals($this->PMF_Search_Resultset->getNumberOfResults(), 0);
    }
}