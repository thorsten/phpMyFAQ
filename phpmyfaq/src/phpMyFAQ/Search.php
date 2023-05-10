<?php

/**
 * The phpMyFAQ Search class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @author    Adrianna Musiol <musiol@imageaccess.de>
 * @copyright 2008-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-26
 */

namespace phpMyFAQ;

use DateTime;
use Exception;
use phpMyFAQ\Search\Elasticsearch;
use phpMyFAQ\Search\SearchFactory;
use stdClass;

/**
 * Class Search
 *
 * @package phpMyFAQ
 */
class Search
{
    private ?int $categoryId = null;

    private ?\phpMyFAQ\Category $category = null;

    private readonly string $table;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
        $this->table = Database::getTablePrefix() . 'faqsearches';
    }

    /**
     * Setter for category.
     *
     * @param int $categoryId Entity ID
     */
    public function setCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Getter for category.
     *
     * @return int
     */
    public function getCategoryId(): ?int
    {
        return $this->categoryId;
    }

    /**
     * The search function to handle the different search engines.
     *
     * @param string $searchTerm Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     * @throws Exception
     */
    public function search(string $searchTerm, bool $allLanguages = true): array
    {
        if (is_numeric($searchTerm)) {
            return $this->searchDatabase($searchTerm, $allLanguages);
        }
        if ($this->config->get('search.enableElasticsearch')) {
            return $this->searchElasticsearch($searchTerm, $allLanguages);
        } else {
            return $this->searchDatabase($searchTerm, $allLanguages);
        }
    }

    /**
     * The auto complete function to handle the different search engines.
     *
     * @param string $searchTerm Text to auto complete
     * @throws Exception
     * @return mixed[]
     */
    public function autoComplete(string $searchTerm): array
    {
        if ($this->config->get('search.enableElasticsearch')) {
            $esSearch = new Elasticsearch($this->config);
            $allCategories = $this->getCategory()->getAllCategoryIds();

            $esSearch->setCategoryIds($allCategories);
            $esSearch->setLanguage($this->config->getLanguage()->getLanguage());

            return $esSearch->autoComplete($searchTerm);
        } else {
            return $this->searchDatabase($searchTerm, false);
        }
    }

    /**
     * The search function for the database powered full text search.
     *
     * @param string $searchTerm Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     * @throws Exception
     * @return mixed[]
     */
    public function searchDatabase(string $searchTerm, $allLanguages = true): array
    {
        $fdTable = Database::getTablePrefix() . 'faqdata AS fd';
        $fcrTable = Database::getTablePrefix() . 'faqcategoryrelations';
        $condition = ['fd.active' => "'yes'"];
        $search = SearchFactory::create($this->config, ['database' => Database::getType()]);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            if ($this->getCategory() instanceof Category) {
                $children = $this->getCategory()->getChildNodes($this->getCategoryId());
                $selectedCategory = [
                    $fcrTable . '.category_id' => array_merge((array)$this->getCategoryId(), $children),
                ];
            } else { // @phpstan-ignore-line
                $selectedCategory = [
                    $fcrTable . '.category_id' => $this->getCategoryId(),
                ];
            }
            $condition = [...$selectedCategory, ...$condition];
        }

        if ((!$allLanguages) && (!is_numeric($searchTerm))) {
            $selectedLanguage = ['fd.lang' => "'" . $this->config->getLanguage()->getLanguage() . "'"];
            $condition        = [...$selectedLanguage, ...$condition];
        }

        $search->setTable($fdTable)
            ->setResultColumns(
                [
                'fd.id AS id',
                'fd.lang AS lang',
                'fd.solution_id AS solution_id',
                $fcrTable . '.category_id AS category_id',
                'fd.thema AS question',
                'fd.content AS answer'
                ]
            )
            ->setJoinedTable($fcrTable)
            ->setJoinedColumns(
                [
                'fd.id = ' . $fcrTable . '.record_id',
                'fd.lang = ' . $fcrTable . '.record_lang'
                ]
            )
            ->setConditions($condition);

        if (is_numeric($searchTerm)) {
            $search->setMatchingColumns(['fd.solution_id']);
        } else {
            $search->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        }

        $result = $search->search($searchTerm);

        if (!$this->config->getDb()->numRows($result)) {
            return [];
        } else {
            return $this->config->getDb()->fetchAll($result);
        }
    }

    /**
     * The search function for the Elasticsearch powered full text search.
     *
     * @param string $searchTerm Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     * @return stdClass[]
     */
    public function searchElasticsearch(string $searchTerm, bool $allLanguages = true): array
    {
        $esSearch = new Elasticsearch($this->config);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            $children = $this->getCategory()->getChildNodes($this->getCategoryId());
            $esSearch->setCategoryIds(array_merge([$this->getCategoryId()], $children));
        } else {
            $allCategories = $this->getCategory()->getAllCategoryIds();
            $esSearch->setCategoryIds($allCategories);
        }

        if (!$allLanguages) {
            $esSearch->setLanguage($this->config->getLanguage()->getLanguage());
        }

        return $esSearch->search($searchTerm);
    }

    /**
     * Logging of search terms for improvements.
     *
     * @param string $searchTerm Search term
     * @throws Exception
     */
    public function logSearchTerm(string $searchTerm): void
    {
        if (Strings::strlen($searchTerm) === 0) {
            return;
        }

        $date = new DateTime();
        $query = sprintf(
            "INSERT INTO %s (id, lang, searchterm, searchdate) VALUES (%d, '%s', '%s', '%s')",
            $this->table,
            $this->config->getDb()->nextId($this->table, 'id'),
            $this->config->getLanguage()->getLanguage(),
            $this->config->getDb()->escape($searchTerm),
            $date->format('Y-m-d H:i:s')
        );

        $this->config->getDb()->query($query);
    }

    /**
     * Deletes a search term.
     */
    public function deleteSearchTermById(int $searchTermId): bool
    {
        $query = sprintf(
            "
            DELETE FROM
                %s
            WHERE
                id = '%d'",
            $this->table,
            $searchTermId
        );

        return $this->config->getDb()->query($query);
    }

    /**
     * Deletes all search terms.
     */
    public function deleteAllSearchTerms(): bool
    {
        $query = sprintf('DELETE FROM %s', $this->table);

        return $this->config->getDb()->query($query);
    }

    /**
     * Returns the most popular searches.
     *
     * @param int  $numResults Number of Results, default: 7
     * @param bool $withLang   Should the language be included in the result?
     *
     * @return array<string[]>
     */
    public function getMostPopularSearches(int $numResults = 7, bool $withLang = false): array
    {
        $searchResult = [];

        $byLang = $withLang ? ', lang' : '';
        $query = sprintf(
            '
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
            $this->table,
            $byLang
        );

        $result = $this->config->getDb()->query($query);

        if (false !== $result) {
            $i = 0;
            while ($row = $this->config->getDb()->fetchObject($result)) {
                if ($i < $numResults) {
                    $searchResult[] = (array)$row;
                }
                ++$i;
            }
        }

        return $searchResult;
    }

    /**
     * Returns row count from the "faqsearches" table.
     */
    public function getSearchesCount(): int
    {
        $sql = sprintf(
            'SELECT COUNT(1) AS count FROM %s',
            $this->table
        );

        $result = $this->config->getDb()->query($sql);

        return (int)$this->config->getDb()->fetchObject($result)->count;
    }

    /**
     * Sets the Entity object.
     */
    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
