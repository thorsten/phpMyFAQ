<?php
/**
 * Test case for PMF_Search_Database_Mysql
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-06-06
 */

require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/Configuration.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/Search.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/Search/Database/Mysqli.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/DB/Driver.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/DB/Mysqli.php';

require_once dirname(dirname(__DIR__)) . '/Database/MysqlTest.php';

/**
 * Search test case
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-01-26
 */
class PMF_Search_Database_MysqlTest extends Database_MysqlTest
{ 
    /**
     * Load the test data
     *
     * @return Flat XML
     */
    protected function getDataSet() 
    {
        return $this->createFlatXMLDataSet(__DIR__ . DIRECTORY_SEPARATOR . 'data.xml');
    }
        
    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->PMF_Language = new PMF_Language();
        $this->PMF_Search   = new PMF_Search($this->db, $this->PMF_Language);

        $this->PMF_Search_Mysql = PMF_Search_Factory::create($this->PMF_Language, 
            array('database' => PMF_Db::getType()));
        $this->PMF_Search_Mysql->setDatabaseHandle($this->db);
    }

    /**
     * Test Relevance with all the possibilities.
     */
    public function testSearchRelevance()
    {
        $faqConfig = new PMF_Configuration($this->db);

        $faqConfig->update(array('search.relevance' => 'thema,content,keywords'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','3','4','2'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'thema,keywords,content'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','2','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'content,thema,keywords'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('3','4','1','2'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'content,keywords,thema'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('3','4','2','1'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'keywords,content,thema'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('2','3','4','1'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'keywords,thema,content'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('2','1','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));
    }

    /**
     * Test search with 0 results on fullText search.
     * Use normal search.
     */
    public function testSearchRelevanceShortWords()
    {    
        $data = $this->PMF_Search->search('p');
        $this->assertEquals(array('2','1','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));
    }

    /**
     * Test disable relevance support
     */
    public function testSearchWithoutRelevance()
    {
        $faqConfig = new PMF_Configuration($this->db);

        $faqConfig->update(array('search.relevance'       => 'thema,content,keywords',
                                 'search.enableRelevance' => 'true'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','3','4','2'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.enableRelevance' => 'false'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','2','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));
    }

    /**
     * Test Matching columns.
     */
    public function testGetMatchingColumnsAsResult()
    {
        $this->PMF_Search_Mysql->setMatchingColumns(array('faqdata.thema', 'faqdata.content', 'faqdata.keywords'));
        $result = $this->PMF_Search_Mysql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals(", MATCH (faqdata.thema) AGAINST ('*pregunta*' IN BOOLEAN MODE) AS rel_thema, "
            . "MATCH (faqdata.content) AGAINST ('*pregunta*' IN BOOLEAN MODE) AS rel_content, "
            . "MATCH (faqdata.keywords) AGAINST ('*pregunta*' IN BOOLEAN MODE) AS rel_keywords", $result);

        $this->PMF_Search_Mysql->setMatchingColumns(array('faqdata.thema'));
        $result = $this->PMF_Search_Mysql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals(", MATCH (faqdata.thema) AGAINST ('*pregunta*' IN BOOLEAN MODE) AS rel_thema", $result);

        $this->PMF_Search_Mysql->setMatchingColumns(array());
        $result = $this->PMF_Search_Mysql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals("", $result);
    }

    /**
     * Test Matching order by.
     */
    public function testGetMatchingOrder()
    {
        $faqConfig = new PMF_Configuration($this->db);

        $faqConfig->update(array('search.relevance' => 'thema,content,keywords'));
        $result = $this->PMF_Search_Mysql->getMatchingOrder('pregunta');
        $this->assertEquals("(rel_thema*3)+(rel_content*2)+(rel_keywords*1)", $result);

        $faqConfig->update(array('search.relevance' => 'thema,content'));
        $result = $this->PMF_Search_Mysql->getMatchingOrder('pregunta');
        $this->assertEquals("(rel_thema*2)+(rel_content*1)", $result);

        $faqConfig->update(array('search.relevance' => 'thema,content,field1,field2'));
        $result = $this->PMF_Search_Mysql->getMatchingOrder('pregunta');
        $this->assertEquals("(rel_thema*4)+(rel_content*3)+(rel_field1*2)+(rel_field2*1)", $result);

        $faqConfig->update(array('search.relevance' => ''));
        $result = $this->PMF_Search_Mysql->getMatchingOrder('pregunta');
        $this->assertEquals("(rel_*1)", $result);
    }   
}