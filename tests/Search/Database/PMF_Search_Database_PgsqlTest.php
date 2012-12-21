<?php
/**
 * Test case for PMF_Search_Database_Pgsql
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
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/Search/Database/Pgsql.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/DB/Driver.php';
require_once dirname(dirname(dirname(__DIR__))) . '/phpmyfaq/inc/PMF/DB/Pgsql.php';

require_once dirname(dirname(__DIR__)) . '/Database/PostgressTest.php';

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
class PMF_Search_Database_PgsqlTest extends Database_PostgressTest
{   
    /**
     * Load test data
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
        
        $this->PMF_Search_Pgsql = PMF_Search_Factory::create($this->PMF_Language, 
            array('database' => PMF_Db::getType()));
        $this->PMF_Search_Pgsql->setDatabaseHandle($this->db);        
    }

    /**
     * Test Relevance with all the possibilities.
     */
    public function testSearchRelevance()
    {
        $faqConfig = new PMF_Configuration($this->db);

        $faqConfig->update(array('search.relevance' => 'thema,content,keywords'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','3','2','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'thema,keywords,content'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','2','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'content,thema,keywords'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('3','1','2','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'content,keywords,thema'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('3','2','1','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.relevance' => 'keywords,content,thema'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('2','3','1','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

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
        $this->assertEquals(array('1','2','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));
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
        $this->assertEquals(array('1','3','2','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));

        $faqConfig->update(array('search.enableRelevance' => 'false'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','2','3','4'), array($data[0]->id, $data[1]->id, $data[2]->id, $data[3]->id));
    }

    /**
     * Test Matching columns.
     */
    public function testGetMatchingColumnsAsResult()
    {
        $this->PMF_Search_Pgsql->setMatchingColumns(array('faqdata.thema', 'faqdata.content', 'faqdata.keywords'));
        $result = $this->PMF_Search_Pgsql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals(", "
            . "ts_rank_cd(setweight(to_tsvector(coalesce(faqdata.thema,'')), 'A'), query) AS rel_thema, "
            . "ts_rank_cd(setweight(to_tsvector(coalesce(faqdata.content,'')), 'B'), query) AS rel_content, "
            . "ts_rank_cd(setweight(to_tsvector(coalesce(faqdata.keywords,'')), 'C'), query) AS rel_keywords",
            $result);

        $this->PMF_Search_Pgsql->setMatchingColumns(array('faqdata.thema'));
        $result = $this->PMF_Search_Pgsql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals(", "
            . "ts_rank_cd(setweight(to_tsvector(coalesce(faqdata.thema,'')), 'A'), query) AS rel_thema",
            $result);

        $this->PMF_Search_Pgsql->setMatchingColumns(array());
        $result = $this->PMF_Search_Pgsql->getMatchingColumnsAsResult('pregunta');
        $this->assertEquals("", $result);
    }

    /**
     * Test Matching order by.
     */
    public function testGetMatchingOrder()
    {
        $faqConfig = new PMF_Configuration($this->db);

        $faqConfig->update(array('search.relevance' => 'thema,content,keywords'));
        $result = $this->PMF_Search_Pgsql->getMatchingOrder('pregunta');
        $this->assertEquals("rel_thema DESC, rel_content DESC, rel_keywords DESC", $result);

        $faqConfig->update(array('search.relevance' => 'thema,content'));
        $result = $this->PMF_Search_Pgsql->getMatchingOrder('pregunta');
        $this->assertEquals("rel_thema DESC, rel_content DESC", $result);

        $faqConfig->update(array('search.relevance' => 'thema,content,field1,field2'));
        $result = $this->PMF_Search_Pgsql->getMatchingOrder('pregunta');
        $this->assertEquals("rel_thema DESC, rel_content DESC, rel_field1 DESC, rel_field2 DESC", $result);

        $faqConfig->update(array('search.relevance' => ''));
        $result = $this->PMF_Search_Pgsql->getMatchingOrder('pregunta');
        $this->assertEquals("rel_ DESC", $result);
    }
}
