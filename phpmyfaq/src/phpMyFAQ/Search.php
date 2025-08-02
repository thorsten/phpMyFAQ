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
 * @copyright 2008-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2008-01-26
 */

namespace phpMyFAQ;

use DateTime;
use Exception;
use phpMyFAQ\Search\Elasticsearch;
use phpMyFAQ\Search\OpenSearch;
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

    private ?Category $category = null;

    private readonly string $table;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
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

        if ($this->configuration->get('search.enableElasticsearch')) {
            return $this->searchElasticsearch($searchTerm, $allLanguages);
        }

        if ($this->configuration->get('search.enableOpenSearch')) {
            return $this->searchOpenSearch($searchTerm, $allLanguages);
        }

        return $this->searchDatabase($searchTerm, $allLanguages);
    }

    /**
     * The auto complete function to handle the different search engines.
     *
     * @param string $searchTerm Text to auto complete
     * @throws Exception
     */
    public function autoComplete(string $searchTerm): array
    {
        if ($this->configuration->get('search.enableElasticsearch')) {
            $elasticsearch = new Elasticsearch($this->configuration);
            $allCategories = $this->getCategory()->getAllCategoryIds();

            $elasticsearch->setCategoryIds($allCategories);
            $elasticsearch->setLanguage($this->configuration->getLanguage()->getLanguage());

            return $elasticsearch->autoComplete($searchTerm);
        }

        if ($this->configuration->get('search.enableOpenSearch')) {
            $opensearch = new OpenSearch($this->configuration);
            $allCategories = $this->getCategory()->getAllCategoryIds();

            $opensearch->setCategoryIds($allCategories);
            $opensearch->setLanguage($this->configuration->getLanguage()->getLanguage());

            return $opensearch->autoComplete($searchTerm);
        }

        return $this->searchDatabase($searchTerm, false);
    }

    /**
     * The search function for the database powered full text search.
     *
     * @param string $searchTerm Text/Number (solution id)
     * @param bool   $allLanguages true to search over all languages
     * @throws Exception
     */
    public function searchDatabase(string $searchTerm, bool $allLanguages = true): array
    {
        $fdTable = Database::getTablePrefix() . 'faqdata AS fd';
        $fcrTable = Database::getTablePrefix() . 'faqcategoryrelations';
        $condition = ['fd.active' => "'yes'"];
        $searchDatabase = SearchFactory::create($this->configuration, ['database' => Database::getType()]);

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
            $selectedLanguage = ['fd.lang' => "'" . $this->configuration->getLanguage()->getLanguage() . "'"];
            $condition        = [...$selectedLanguage, ...$condition];
        }

        $searchDatabase->setTable($fdTable)
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
            $searchDatabase->setMatchingColumns(['fd.solution_id']);
        } else {
            $searchDatabase->setMatchingColumns(['fd.thema', 'fd.content', 'fd.keywords']);
        }

        $result = $searchDatabase->search($searchTerm);

        if ($this->configuration->getDb()->numRows($result) === 0) {
            return [];
        }

        return $this->configuration->getDb()->fetchAll($result);
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
        $elasticsearch = new Elasticsearch($this->configuration);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            $children = $this->getCategory()->getChildNodes($this->getCategoryId());
            $elasticsearch->setCategoryIds(array_merge([$this->getCategoryId()], $children));
        } else {
            $allCategories = $this->getCategory()->getAllCategoryIds();
            $elasticsearch->setCategoryIds($allCategories);
        }

        if (!$allLanguages) {
            $elasticsearch->setLanguage($this->configuration->getLanguage()->getLanguage());
        }

        return $elasticsearch->search($searchTerm);
    }

    public function searchOpenSearch(string $searchTerm, bool $allLanguages = true): array
    {
        $opensearch = new OpenSearch($this->configuration);

        if (!is_null($this->getCategoryId()) && 0 < $this->getCategoryId()) {
            $children = $this->getCategory()->getChildNodes($this->getCategoryId());
            $opensearch->setCategoryIds(array_merge([$this->getCategoryId()], $children));
        } else {
            $allCategories = $this->getCategory()->getAllCategoryIds();
            $opensearch->setCategoryIds($allCategories);
        }

        if (!$allLanguages) {
            $opensearch->setLanguage($this->configuration->getLanguage()->getLanguage());
        }

        return $opensearch->search($searchTerm);
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

        $dateTime = new DateTime();
        $query = sprintf(
            "INSERT INTO %s (id, lang, searchterm, searchdate) VALUES (%d, '%s', '%s', '%s')",
            $this->table,
            $this->configuration->getDb()->nextId($this->table, 'id'),
            $this->configuration->getLanguage()->getLanguage(),
            $this->configuration->getDb()->escape($searchTerm),
            $dateTime->format('Y-m-d H:i:s')
        );

        $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a search term.
     */
    public function deleteSearchTermById(int $searchTermId): bool
    {
        $query = sprintf(
            "DELETE FROM %s WHERE id = '%d'",
            $this->table,
            $searchTermId
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes all search terms.
     */
    public function deleteAllSearchTerms(): bool
    {
        $query = sprintf('DELETE FROM %s', $this->table);

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Returns the most popular searches.
     *
     * @param int  $numResults Number of Results, default: 7
     * @param bool $withLang   Should the language be included in the result?
     * @param int  $timeWindow Number of days to look back for searches, 0 for all time
     *
     * @return array<string[]>
     */
    public function getMostPopularSearches(int $numResults = 7, bool $withLang = false, int $timeWindow = 0): array
    {
        $searchResult = [];

        $byLang = $withLang ? ', lang' : '';
        
        // Build time-based condition if timeWindow is specified
        $timeCondition = '';
        if ($timeWindow > 0) {
            try {
                $dbType = Database::getType();
            } catch (\Error $e) {
                // Database type not initialized, assume MySQL/MariaDB syntax
                $dbType = 'mysql';
            }
            
            $timeCondition = match ($dbType) {
                'pgsql' => sprintf(
                    ' WHERE searchdate >= NOW() - INTERVAL \'%d days\'',
                    $timeWindow
                ),
                'sqlite3' => sprintf(
                    ' WHERE searchdate >= datetime(\'now\', \'-%d days\')',
                    $timeWindow
                ),
                'sqlsrv' => sprintf(
                    ' WHERE searchdate >= DATEADD(day, -%d, GETDATE())',
                    $timeWindow
                ),
                default => sprintf(
                    ' WHERE searchdate >= DATE_SUB(NOW(), INTERVAL %d DAY)',
                    $timeWindow
                )
            };
        }

        $query = sprintf(
            '
            SELECT 
                MIN(id) as id, searchterm, COUNT(searchterm) AS number %s
            FROM
                %s%s
            GROUP BY
                searchterm %s
            ORDER BY
                number DESC
            LIMIT %d',
            $byLang,
            $this->table,
            $timeCondition,
            $byLang,
            $numResults
        );

        $result = $this->configuration->getDb()->query($query);

        if (false !== $result) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $searchResult[] = (array)$row;
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
            'SELECT COUNT(*) AS count FROM %s',
            $this->table
        );

        $result = $this->configuration->getDb()->query($sql);

        return (int) $this->configuration->getDb()->fetchObject($result)->count;
    }

    public function setCategory(Category $category): void
    {
        $this->category = $category;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }
}
