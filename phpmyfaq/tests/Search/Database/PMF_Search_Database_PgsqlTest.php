<?php
/**
 * Test case for PMF_Search_Database_Pgsql
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
 * @since     2010-06-06
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/Configuration.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/functions.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/Search.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/PMF_Search/Database/Pgsql.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/PMF_DB/Driver.php';
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/inc/PMF_DB/Pgsql.php';

require_once dirname(dirname(dirname(__FILE__))) . '/Database/PostgressTest.php';

/**
 * PMF_Search test case
 * 
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
        return $this->createFlatXMLDataSet(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'data.xml');
    }

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->PMF_Language = new PMF_Language();
        $this->PMF_Search   = new PMF_Search($this->db, $this->PMF_Language);
    }

    /**
     * Test Relevance with all the possibilities.
     */    
    public function testSearchRelevance()
    {
        $faqconfig = PMF_Configuration::getInstance();

        $faqconfig->update(array('search.relevance' => 'thema,keywords,content'));
        $data = $this->PMF_Search->search('pregunta');
        $this->assertEquals(array('1','2','3'), array($data[0]->id, $data[1]->id, $data[2]->id));
    }
}
