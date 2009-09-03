<?php
/**
 * The phpMyFAQ Search class
 *
 * @package   phpMyFAQ
 * @license   MPL
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @version   SVN: $Id$
 * @copyright 2008-2009 phpMyFAQ Team
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

/**
 * PMF_Search
 *
 * @package   phpMyFAQ
 * @license   MPL
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2009 phpMyFAQ Team
 */
class PMF_Search
{
    /**
     * DB handle
     *
     * @var PMF_Db
     */
    private $db;

    /**
     * Language
     *
     * @var string
     */
    private $language;
    
    /**
     * Category
     * 
     * @var integer
     */
    private $categoryId = null;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
    }
    
    /**
     * Setter for category
     * 
     * @param  integer $categoryId Category ID
     * @return void
     */
    public function setCategory($categoryId)
    {
    	$this->categoryId = (int)$categoryId;
    }
    
    /**
     * Getter for category
     * 
     * @return integer
     */
    public function getCategory()
    {
    	return $this->categoryId;
    }

    /**
     * The main search function for the full text search
     *
     * TODO: add filter for (X)HTML tag names and attributes!
     *
     * @param   string  $searchterm     Text/Number (solution id)
     * @param   boolean $allLanguages   true to search over all languages
     * @param   boolean $hasMore        true to disable the results paging
     * @param   boolean $instantRespnse true to use it for Instant Response
     * @return  array
     */
    public function search($searchterm, $allLanguages = true, $hasMore = false, $instantResponse = false)
    {
        $condition = array(SQLPREFIX . 'faqdata.active' => "'yes'");

        // Search in all or one category?
        if (!is_null($this->categoryId)) {
            $selectedCategory = array(SQLPREFIX . 'faqcategoryrelations.category_id' => $searchcategory);
            $condition        = array_merge($selectedCategory, $condition);
        }

        if ((!$allLanguages) && (!is_numeric($searchterm))) {
            $selectedLanguage = array(SQLPREFIX . 'faqdata.lang' => "'" . $this->language . "'");
            $condition        = array_merge($selectedLanguage, $condition);
        }

        if (is_numeric($searchterm)) {
            // search for the solution_id
            $result = $this->db->search(SQLPREFIX.'faqdata',
                array(
                SQLPREFIX.'faqdata.id AS id',
                SQLPREFIX.'faqdata.lang AS lang',
                SQLPREFIX.'faqdata.solution_id AS solution_id',
                SQLPREFIX.'faqcategoryrelations.category_id AS category_id',
                SQLPREFIX.'faqdata.thema AS thema',
                SQLPREFIX.'faqdata.content AS content'),
                SQLPREFIX.'faqcategoryrelations',
                array(SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id',
                      SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang'),
                array(SQLPREFIX.'faqdata.solution_id'),
                $searchterm,
                $condition);
        } else {
            $result = $this->db->search(SQLPREFIX."faqdata",
                array(
                SQLPREFIX."faqdata.id AS id",
                SQLPREFIX."faqdata.lang AS lang",
                SQLPREFIX."faqcategoryrelations.category_id AS category_id",
                SQLPREFIX."faqdata.thema AS thema",
                SQLPREFIX."faqdata.content AS content"),
                SQLPREFIX."faqcategoryrelations",
                array(SQLPREFIX."faqdata.id = ".SQLPREFIX."faqcategoryrelations.record_id",
                      SQLPREFIX."faqdata.lang = ".SQLPREFIX."faqcategoryrelations.record_lang"),
                array(SQLPREFIX."faqdata.thema",
                SQLPREFIX."faqdata.content",
                SQLPREFIX."faqdata.keywords"),
                $searchterm,
                $condition);
        }

        if ($result) {
            $num = $this->db->num_rows($result);
        }

        if ($num) {
            return array();
        } else {
            return $this->db->fetchAll($result);
        }
    }

    /**
     * Logging of search terms for improvements
     *
     * @param  string $searchterm Search term
     * @return void
     */
    public function logSearchTerm($searchterm)
    {
        $date = new DateTime();
        
        $query = sprintf("
            INSERT INTO
                %s
            (id, lang, searchterm, searchdate)
                VALUES
            (%d, '%s', '%s', '%s')",
            SQLPREFIX . 'faqsearches',
            $this->db->nextID(SQLPREFIX . 'faqsearches', 'id'),
            $this->language,
            $this->db->escape_string($searchterm),
            $date->format('Y-m-d H:i:s'));
        
        $this->db->query($query);
    }
    
    /**
     * Returns the most popular searches
     * 
     * @param  integer $numResults Number of Results, default: 7
     * @param  boolean weither language must be included into result
     * @return array
     */
    public function getMostPopularSearches($numResults = 7, $withLang = false)
    {
        $searchResult = array();
        
        $byLang = $withLang ? ', lang' : '';
        $query  = sprintf("
            SELECT 
                searchterm, COUNT(searchterm) AS number %s
            FROM
                %s
            GROUP BY
                searchterm %s
            ORDER BY
                number
            DESC",
            $byLang, SQLPREFIX . 'faqsearches', $byLang);
        
        $result = $this->db->query($query);
        
        if ($result) {
        	$i = 0;
            while ($row = $this->db->fetch_object($result)) {
            	if ($i < 10) {
            		$searchResult[] = (array) $row;
            	}
            	$i++;
            }
        }
        
        return $searchResult;
    }
    
    /**
     * Returns row count from the faqsearches table
     * 
     * @return integer
     */
    public function getSearchesCount()
    {
    	$sql = 'SELECT COUNT(1) AS count FROM ' . SQLPREFIX . 'faqsearches';
    	
    	$result = $this->db->query($sql);

    	return (int) $this->db->fetch_object($result)->count;
    }
}