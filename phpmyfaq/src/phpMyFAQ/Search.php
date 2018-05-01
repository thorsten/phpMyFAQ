<?php

namespace phpMyFAQ;

/**
 * The phpMyFAQ Search class.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-26
 */

use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use phpMyFAQ\Search\Elasticsearch;
use phpMyFAQ\Search\Exception;
use phpMyFAQ\Search\SearchFactory;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Search.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-26
 */
class Search
{
    /**
     * @var Configuration
     */
    private $_config;

    /**
     * Entity ID.
     *
     * @var int
     */
    private $categoryId = null;

    /**
     * Entity object.
     *
     * @var PMF_Category
     */
    private $category = null;

    /**
     * Search table.
     *
     * @var string
     */
    private $_table = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
        $this->_table = Db::getTablePrefix().'faqsearches';
    }

    /**
     * Setter for category.
     *
     * @param int $categoryId Entity ID
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = (int) $categoryId;
    }

    /**
     * Getter for category.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * The search function to handle the different search engines.
     *
     * @param string $searchTerm   Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     *
     * @throws Exception
     *
     * @return array
     */
    public function search($searchTerm, $allLanguages = true)
    {
        if ($this->_config->get('search.enableElasticsearch')) {
            try {
                return $this->searchElasticsearch($searchTerm, $allLanguages);
            } catch (NoNodesAvailableException $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            return $this->searchDatabase($searchTerm, $allLanguages);
        }
    }

    /**
     * The auto complete function to handle the different search engines.
     *
     * @param string $searchTerm Text to auto complete
     *
     * @throws Exception
     *
     * @return array
     */
    public function autoComplete($searchTerm)
    {
        if ($this->_config->get('search.enableElasticsearch')) {

            $esSearch = new Elasticsearch($this->_config);
            $allCategories = $this->getCategory()->getAllCategoryIds();

            $esSearch->setCategoryIds($allCategories);
            $esSearch->setLanguage($this->_config->getLanguage()->getLanguage());

            try {
                return $esSearch->autoComplete($searchTerm);
            } catch (NoNodesAvailableException $e) {
                throw new Exception($e->getMessage());
            }
        } else {
            return $this->searchDatabase($searchTerm, false);
        }
    }

    /**
     * The search function for the database powered full text search.
     *
     * @param string $searchTerm   Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     *
     * @return array
     */
    public function searchDatabase($searchTerm, $allLanguages = true)
    {
        $fdTable = Db::getTablePrefix() . 'faqdata AS fd';
        $fcrTable = Db::getTablePrefix() . 'faqcategoryrelations';
        $condition = ['fd.active' => "'yes'"];
        $search = SearchFactory::create($this->_config, ['database' => Db::getType()]);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            if ($this->getCategory() instanceof Category) {
                $children = $this->getCategory()->getChildNodes($this->getCategoryId());
                $selectedCategory = array(
                    $fcrTable.'.category_id' => array_merge((array) $this->getCategoryId(), $children),
                );
            } else {
                $selectedCategory = array(
                    $fcrTable.'.category_id' => $this->getCategoryId(),
                );
            }
            $condition = array_merge($selectedCategory, $condition);
        }

        if ((!$allLanguages) && (!is_numeric($searchTerm))) {
            $selectedLanguage = array('fd.lang' => "'" . $this->_config->getLanguage()->getLanguage() . "'");
            $condition        = array_merge($selectedLanguage, $condition);
        }

        $search->setTable($fdTable)
            ->setResultColumns(array(
                'fd.id AS id',
                'fd.lang AS lang',
                'fd.solution_id AS solution_id',
                $fcrTable . '.category_id AS category_id',
                'fd.thema AS question',
                'fd.content AS answer'))
            ->setJoinedTable($fcrTable)
            ->setJoinedColumns(array(
                'fd.id = ' . $fcrTable . '.record_id',
                'fd.lang = ' . $fcrTable . '.record_lang'
            ))
            ->setConditions($condition);

        if (is_numeric($searchTerm)) {
            $search->setMatchingColumns(array('fd.solution_id'));
        } else {
            $search->setMatchingColumns(array('fd.thema', 'fd.content', 'fd.keywords'));
        }

        $result = $search->search($searchTerm);

        if (!$this->_config->getDb()->numRows($result)) {
            return [];
        } else {
            return $this->_config->getDb()->fetchAll($result);
        }
    }

    /**
     * The search function for the Elasticsearch powered full text search.
     *
     * @param string $searchTerm   Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     *
     * @return array
     */
    public function searchElasticsearch($searchTerm, $allLanguages = true)
    {
        $esSearch = new Elasticsearch($this->_config);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            if ($this->getCategory() instanceof Category) {
                $children = $this->getCategory()->getChildNodes($this->getCategoryId());
                $esSearch->setCategoryIds(array_merge([$this->getCategoryId()], $children));
            }
        } else {
            $allCategories = $this->getCategory()->getAllCategoryIds();
            $esSearch->setCategoryIds($allCategories);
        }

        if (!$allLanguages) {
            $esSearch->setLanguage($this->_config->getLanguage()->getLanguage());
        }

        $result = $esSearch->search($searchTerm);

        return $result;
    }

    /**
     * Logging of search terms for improvements.
     *
     * @param string $searchTerm Search term
     */
    public function logSearchTerm($searchTerm)
    {
        if (Strings::strlen($searchTerm) == 0) {
            return;
        }

        $date = new \DateTime();
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
     * Deletes a search term.
     *
     * @param string $searchTerm
     *
     * @return bool
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
     * Deletes all search terms.
     *
     * @return bool
     */
    public function deleteAllSearchTerms()
    {
        $query = sprintf('DELETE FROM %s', $this->_table);

        return $this->_config->getDb()->query($query);
    }

    /**
     * Returns the most popular searches.
     *
     * @param int  $numResults Number of Results, default: 7
     * @param bool $withLang   Should the language be included in the result?
     *
     * @return array
     */
    public function getMostPopularSearches($numResults = 7, $withLang = false)
    {
        $searchResult = [];

        $byLang = $withLang ? ', lang' : '';
        $query = sprintf('
            SELECT 
                MIN(id) as id, searchterm, COUNT(searchterm) AS number %s
            FROM
                %s
            GROUP BY
                searchterm %s
            ORDER BY
                number
            DESC',
            $byLang,
            $this->_table,
            $byLang
        );

        $result = $this->_config->getDb()->query($query);

        if (false !== $result) {
            $i = 0;
            while ($row = $this->_config->getDb()->fetchObject($result)) {
                if ($i < $numResults) {
                    $searchResult[] = (array) $row;
                }
                ++$i;
            }
        }

        return $searchResult;
    }

    /**
     * Returns row count from the "faqsearches" table.
     *
     * @return int
     */
    public function getSearchesCount()
    {
        $sql = sprintf(
            'SELECT COUNT(1) AS count FROM %s',
            $this->_table
        );

        $result = $this->_config->getDb()->query($sql);

        return (int) $this->_config->getDb()->fetchObject($result)->count;
    }

    /**
     * Sets the Entity object.
     *
     * @param PMF_Category $category
     */
    public function setCategory($category)
    {
        $this->category = $category;
    }

    /**
     * Returns the CategoryHelper object.
     *
     * @return PMF_Category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
