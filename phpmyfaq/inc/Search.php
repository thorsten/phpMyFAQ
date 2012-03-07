<?php
/**
 * The phpMyFAQ Search class
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Search
 *
 * @category  phpMyFAQ
 * @package   PMF_Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-26
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
     * @var PMF_Language
     */
    private $language;
    
    /**
     * Category ID
     *
     * @var integer
     */
    private $categoryId = null;

    /**
     * Category object
     *
     * @var PMF_Category
     */
    private $category = null;
    
    /**
     * Search table
     *
     * @var string
     */
    private $_table = null;

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Search
     */
    public function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        $this->db       = $database;
        $this->language = $language;
        $this->_table   = SQLPREFIX . 'faqsearches';
    }
    
    /**
     * Setter for category
     *
     * @param  integer $categoryId Category ID
     * @return void
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = (int)$categoryId;
    }
    
    /**
     * Getter for category
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * The main search function for the full text search
     *
     * @param string  $searchterm   Text/Number (solution id)
     * @param boolean $allLanguages true to search over all languages
     *
     * @return  array
     */
    public function search($searchterm, $allLanguages = true)
    {
        $fdTable   = SQLPREFIX . 'faqdata';
        $fcrTable  = SQLPREFIX . 'faqcategoryrelations';
        $condition = array($fdTable . '.active' => "'yes'");
        $search    = PMF_Search_Factory::create($this->language, array('database' => PMF_Db::getType()));
        
        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            if ($this->getCategory() instanceof PMF_Category) {
                $children = $this->getCategory()->getChildNodes($this->getCategoryId());
                $selectedCategory = array(
                    $fcrTable . '.category_id' => array_merge((array)$this->getCategoryId(), $children)
                );
            } else {
                $selectedCategory = array(
                    $fcrTable . '.category_id' => $this->getCategoryId()
                );
            }
            $condition = array_merge($selectedCategory, $condition);
        }

        if ((!$allLanguages) && (!is_numeric($searchterm))) {
            $selectedLanguage = array($fdTable . '.lang' => "'" . $this->language->getLanguage() . "'");
            $condition        = array_merge($selectedLanguage, $condition);
        }
        
        $search->setDatabaseHandle($this->db)
               ->setTable($fdTable)
               ->setResultColumns(array(
                    $fdTable . '.id AS id',
                    $fdTable . '.lang AS lang',
                    $fdTable . '.solution_id AS solution_id',
                    $fcrTable . '.category_id AS category_id',
                    $fdTable . '.thema AS question',
                    $fdTable . '.content AS answer'))
               ->setJoinedTable($fcrTable)
               ->setJoinedColumns(array(
                    $fdTable . '.id = ' . $fcrTable . '.record_id',
                    $fdTable . '.lang = ' . $fcrTable . '.record_lang'))
               ->setConditions($condition);
        
        if (is_numeric($searchterm)) {
            $search->setMatchingColumns(array($fdTable . '.solution_id'));
        } else {
            $search->setMatchingColumns(array($fdTable . '.thema', $fdTable . '.content', $fdTable . '.keywords'));
        }
        
        $result = $search->search($searchterm);
        
        if (!$this->db->numRows($result)) {
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
        if (PMF_String::strlen($searchterm) == 0) {
            return;
        }
        
        $date  = new DateTime();
        $query = sprintf("
            INSERT INTO
                %s
            (id, lang, searchterm, searchdate)
                VALUES
            (%d, '%s', '%s', '%s')",
            $this->_table,
            $this->db->nextId($this->_table, 'id'),
            $this->language->getLanguage(),
            $this->db->escape($searchterm),
            $date->format('Y-m-d H:i:s'));
        
        $this->db->query($query);
    }

    /**
     * Deletes a searchterm
     *
     * @param string $searchterm
     * @return boolean
     */
    public function deleteSearchTerm($searchterm)
    {
        $query = sprintf("
            DELETE FROM
                %s
            WHERE
                searchterm = '%s'",
            $this->_table,
            $searchterm
        );

        return $this->db->query($query);
    }
    
    /**
     * Returns the most popular searches
     *
     * @param integer $numResults Number of Results, default: 7
     * @param boolean $withLang   Should the language be included in the result?
     *
     * @return array
     */
    public function getMostPopularSearches($numResults = 7, $withLang = false)
    {
        $searchResult = array();
        
        $byLang = $withLang ? ', lang' : '';
        $query  = sprintf("
            SELECT 
                id, searchterm, COUNT(searchterm) AS number %s
            FROM
                %s
            GROUP BY
                searchterm %s
            ORDER BY
                number
            DESC",
            $byLang, 
            $this->_table, 
            $byLang);
        
        $result = $this->db->query($query);
        
        if (false !== $result) {
            $i = 0;
            while ($row = $this->db->fetchObject($result)) {
                if ($i < $numResults) {
                    $searchResult[] = (array)$row;
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
        $sql = sprintf("
            SELECT 
                COUNT(1) AS count 
            FROM 
                %s",
        $this->_table);
    
        $result = $this->db->query($sql);

        return (int)$this->db->fetchObject($result)->count;
    }

    /**
     * Sets the PMF_Category object
     *
     * @param PMF_Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Returns the PMF_Category object
     *
     * @return PMF_Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}