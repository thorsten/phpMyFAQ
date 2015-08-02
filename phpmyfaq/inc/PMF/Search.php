<?php
/**
 * The phpMyFAQ Search class
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Search
 *
 * @category  phpMyFAQ
 * @package   Search
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2008-01-26
 */
class PMF_Search
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

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
     * @param PMF_Configuration $config
     *
     * @return PMF_Search
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;
        $this->_table  = PMF_Db::getTablePrefix() . 'faqsearches';
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
     * @param string  $searchTerm   Text/Number (solution id)
     * @param boolean $allLanguages true to search over all languages
     *
     * @return array
     */
    public function search($searchTerm, $allLanguages = true)
    {
        $fdTable   = PMF_Db::getTablePrefix() . 'faqdata';
        $fcrTable  = PMF_Db::getTablePrefix() . 'faqcategoryrelations';
        $condition = array($fdTable . '.active' => "'yes'");
        $search    = PMF_Search_Factory::create($this->_config, array('database' => PMF_Db::getType()));
        
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

        if ((!$allLanguages) && (!is_numeric($searchTerm))) {
            $selectedLanguage = [$fdTable . '.lang' => "'" . $this->_config->getLanguage()->getLanguage() . "'"];
            $condition        = array_merge($selectedLanguage, $condition);
        }
        
        $search->setTable($fdTable)
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
        
        if (is_numeric($searchTerm)) {
            $search->setMatchingColumns([$fdTable . '.solution_id']);
        } else {
            $search->setMatchingColumns([$fdTable . '.thema', $fdTable . '.content', $fdTable . '.keywords']);
        }
        
        $result = $search->search($searchTerm);
        
        if (!$this->_config->getDb()->numRows($result)) {
            return [];
        } else {
            return $this->_config->getDb()->fetchAll($result);
        }
    }

    /**
     * Logging of search terms for improvements
     *
     * @param  string $searchTerm Search term
     * @return void
     */
    public function logSearchTerm($searchTerm)
    {
        if (PMF_String::strlen($searchTerm) == 0) {
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
            $this->_config->getDb()->nextId($this->_table, 'id'),
            $this->_config->getLanguage()->getLanguage(),
            $this->_config->getDb()->escape($searchTerm),
            $date->format('Y-m-d H:i:s')
        );
        
        $this->_config->getDb()->query($query);
    }

    /**
     * Deletes a search term
     *
     * @param string $searchTerm
     * @return boolean
     */
    public function deleteSearchTerm($searchTerm)
    {
        $query = sprintf("
            DELETE FROM
                %s
            WHERE
                searchterm = '%s'",
            $this->_table,
            $searchTerm
        );

        return $this->_config->getDb()->query($query);
    }

    /**
     * Deletes all search terms
     *
     * @return boolean
     */
    public function deleteAllSearchTerms()
    {
        $query = sprintf("DELETE FROM %s", $this->_table);

        return $this->_config->getDb()->query($query);
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
        $searchResult = [];
        
        $byLang = $withLang ? ', lang' : '';
        $query  = sprintf("
            SELECT 
                MIN(id), searchterm, COUNT(searchterm) AS number %s
            FROM
                %s
            GROUP BY
                searchterm %s,faqsearches.id
            ORDER BY
                number
            DESC",
            $byLang, 
            $this->_table, 
            $byLang
        );
        
        $result = $this->_config->getDb()->query($query);
        
        if (false !== $result) {
            $i = 0;
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                if ($i < $numResults) {
                    $searchResult[] = (array)$row;
                }
                $i++;
            }
        }
        
        return $searchResult;
    }
    
    /**
     * Returns row count from the "faqsearches" table
     *
     * @return integer
     */
    public function getSearchesCount()
    {
        $sql = sprintf(
            "SELECT COUNT(1) AS count FROM %s",
            $this->_table
        );
    
        $result = $this->_config->getDb()->query($sql);

        return (int)$this->_config->getDb()->fetchObject($result)->count;
    }

    /**
     * Sets the Category object
     *
     * @param PMF_Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Returns the Category object
     *
     * @return PMF_Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}