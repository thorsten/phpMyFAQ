<?php
/**
 * Test case for PMF_Category
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
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Abstract.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Interface.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_Search/Database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_DB/Driver.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/PMF_DB/Sqlite.php';
require_once dirname(dirname(dirname(__FILE__))) . '/inc/Language.php';

/**
 * PMF_Category test case
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-26
 */
class PMF_Search_DatabaseTest extends PHPUnit_Framework_TestCase
{
    private $PMF_Search_Database;
    
    private $PMF_Language;
    
    private $dbHandle;
    
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();
        $this->PMF_Language        = new PMF_Language();
        $this->PMF_Search_Database = new PMF_Search_Database($this->PMF_Language);
        $this->dbHandle            = new PMF_DB_Sqlite();
    }
    
    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->PMF_Search_Database = null;
        parent::tearDown();
    }
    
    public function testSetDatabaseHandle()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->assertEquals(new PMF_DB_Sqlite(), $this->PMF_Search_Database->getDatabaseHandle());
    }
    
    public function testSetDatabaseHandleWrongParameter()
    {
        $this->setExpectedException('Exception');
        try {
            $this->PMF_Search_Database->setDatabaseHandle('wrongParameter');
        } catch (Exception $expected) {
            throw new Exception($expected);
        }
    }
    
    public function testGetDatabaseHandleType()
    {
        $this->PMF_Search_Database->setDatabaseHandle($this->dbHandle);
        $this->assertType('PMF_DB_Sqlite', $this->PMF_Search_Database->getDatabaseHandle());
    }
    
    public function testSetAndGetTable()
    {
        $this->PMF_Search_Database->setTable('faqdata');
        $this->assertEquals('faqdata', $this->PMF_Search_Database->getTable());
        $this->assertType('string', $this->PMF_Search_Database->getTable());
    }

    public function testSetAndGetJoinedTable()
    {
        $this->PMF_Search_Database->setJoinedTable('faqcategoryrelations');
        $this->assertEquals('faqcategoryrelations', $this->PMF_Search_Database->getJoinedTable());
        $this->assertType('string', $this->PMF_Search_Database->getJoinedTable());
    }
}