<?php

/**
 * Category relations class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Class CategoryRelation
 *
 * @package phpMyFAQ\Category
 */
class CategoryRelation
{
    /** @var int[] */
    private array $groups;

    /**
     * CategoryRelation constructor.
     */
    public function __construct(private readonly Configuration $config, private readonly Category $category)
    {
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): CategoryRelation
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Create a matrix for representing categories and FAQs.
     */
    public function getCategoryFaqsMatrix(): array
    {
        $matrix = [];

        $query = sprintf(
            '
            SELECT
                fcr.category_id AS id_cat,
                fd.id AS id
            FROM
                %sfaqdata fd
            INNER JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.category_lang
            ORDER BY
                fcr.category_id, fd.id',
            Database::getTablePrefix(),
            Database::getTablePrefix()
        );
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $matrix[$row->id_cat][$row->id] = true;
            }
        }

        return $matrix;
    }

    public function getCategoryWithFaqs(): array
    {
        $categoryTree = [];

        $query = sprintf(
            '
            SELECT
                fcr.category_id AS id,
                fc.parent_id AS parent_id,
                fc.name AS category_name,
                fc.description AS description,
                count(fcr.category_id) AS number
            FROM
                %sfaqcategoryrelations fcr
                JOIN %sfaqdata fd ON fcr.record_id = fd.id AND fcr.record_lang = fd.lang
                LEFT JOIN %sfaqdata_group AS fdg ON fd.id = fdg.record_id
                LEFT JOIN %sfaqdata_user AS fdu ON fd.id = fdu.record_id
                LEFT JOIN %sfaqcategory_group AS fcg ON fcr.category_id = fcg.category_id
                LEFT JOIN %sfaqcategory_user AS fcu ON fcr.category_id = fcu.category_id
                LEFT JOIN %sfaqcategories AS fc ON fcr.category_id = fc.id AND fcr.category_lang = fc.lang
            WHERE 1=1 
            ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix()
        );

        if ($this->config->get('security.permLevel') !== 'basic') {
            if (-1 === $this->category->getUser()) {
                $query .= sprintf(
                    'AND fdg.group_id IN (%s) AND fcg.group_id IN (%s)',
                    implode(', ', $this->category->getGroups()),
                    implode(', ', $this->category->getGroups())
                );
            } else {
                $query .= sprintf(
                    'AND ( fdu.user_id = %d OR fdg.group_id IN (%s) )
                    AND ( fcu.user_id = %d OR fcg.group_id IN (%s) )',
                    $this->category->getUser(),
                    implode(', ', $this->category->getGroups()),
                    $this->category->getUser(),
                    implode(', ', $this->category->getGroups())
                );
            }
        }

        if (strlen($this->config->getLanguage()->getLanguage()) > 0) {
            $query .= sprintf(
                " AND fd.lang = '%s'",
                $this->config->getLanguage()->getLanguage()
            );
        }

        $query .= " AND fd.active = 'yes' GROUP BY fcr.category_id, fc.parent_id, fc.name, fc.description";

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($category = $this->config->getDb()->fetchObject($result)) {
                $categoryTree[(int) $category->id] = [
                    'category_id' => (int) $category->id,
                    'parent_id' => (int) $category->parent_id,
                    'name' => $category->category_name,
                    'description' => $category->description,
                    'faqs' => (int) $category->number
                ];
            }
        }

        return $categoryTree;
    }

    /**
     * Returns the number of records in each category.
     * @return int[]
     */
    public function getNumberOfFaqsPerCategory(bool $categoryRestriction = false, bool $onlyActive = false): array
    {
        $numRecordsByCat = [];
        if ($categoryRestriction) {
            $query = sprintf(
                "
                SELECT
                    fcr.category_id AS category_id,
                    fc.parent_id as parent_id,
                    COUNT(fcr.record_id) AS number
                FROM
                    %sfaqcategoryrelations fcr
                LEFT JOIN
                    %sfaqdata fd on fcr.record_id = fd.id
                LEFT JOIN
                    %sfaqdata_group fdg on fdg.record_id = fcr.record_id
                LEFT JOIN 
                    %sfaqcategories fc ON fc.id = fcr.category_id AND fcr.category_lang = fc.lang
                WHERE
                    fdg.group_id = %s
                AND
                    fcr.record_lang = fd.lang
                %s",
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $this->groups[0],
                $onlyActive ? " AND fd.active = 'yes'" : ''
            );
        } else {
            $query = sprintf(
                "
                SELECT
                    fcr.category_id AS category_id,
                    fc.parent_id as parent_id,
                    COUNT(fcr.record_id) AS number
                FROM
                    %sfaqcategoryrelations fcr
                LEFT JOIN
                    %sfaqdata fd on fcr.record_id = fd.id
                LEFT JOIN 
                    %sfaqcategories fc ON fc.id = fcr.category_id
                WHERE
                    fcr.record_id = fd.id
                AND
                    fcr.record_lang = fd.lang
                %s",
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $onlyActive ? " AND fd.active = 'yes'" : ''
            );
        }

        if (strlen($this->config->getLanguage()->getLanguage()) > 0) {
            $query .= sprintf(
                " AND fd.lang = '%s'",
                $this->config->getLanguage()->getLanguage()
            );
        }

        $query .= " GROUP BY fcr.category_id, fc.parent_id";

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $numRecordsByCat[$row->category_id] = (int)$row->number;
            }
        }

        return $numRecordsByCat;
    }

    /**
     * Calculates the aggregated numbers of FAQs
     */
    public function getAggregatedFaqNumbers(array $categories): array
    {
        $aggregatedFaqs = [];

        foreach ($categories as $category) {
            $categoryId = $category['category_id'];
            $parentId = $category['parent_id'];
            $numFaqs = $category['faqs'];

            if ($parentId !== 0) {
                if (!isset($aggregatedFaqs[$parentId])) {
                    $aggregatedFaqs[$parentId] = $numFaqs;
                } else {
                    $aggregatedFaqs[$parentId] += $numFaqs;
                }
            }

            if (!isset($aggregatedFaqs[$categoryId])) {
                $aggregatedFaqs[$categoryId] = $numFaqs;
            } else {
                $aggregatedFaqs[$categoryId] += $numFaqs;
            }
        }

        return $aggregatedFaqs;
    }

    /**
     * Returns the categories from a FAQ id and language.
     *
     * @param int    $faqId FAQ id
     * @param string $faqLang FAQ language
     */
    public function getCategories(int $faqId, string $faqLang): array
    {
        $categories = [];

        $query = sprintf(
            "
            SELECT
                category_id, category_lang
            FROM
                %sfaqcategoryrelations
            WHERE
                record_id = %d
            AND
                record_lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $faqLang
        );

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $categories[$row->category_id] = [
                'category_id' => $row->category_id,
                'category_lang' => $row->category_lang
            ];
        }

        return $categories;
    }

    /**
     * Adds new category relations to a FAQ
     *
     * @param array  $categories Array of categories
     * @param int    $faqId FAQ id
     * @param string $language Language
     */
    public function add(array $categories, int $faqId, string $language): bool
    {
        foreach ($categories as $categoryId) {
            $this->config->getDb()->query(
                sprintf(
                    "INSERT INTO %sfaqcategoryrelations VALUES (%d, '%s', %d, '%s')",
                    Database::getTablePrefix(),
                    $categoryId,
                    $language,
                    $faqId,
                    $language
                )
            );
        }

        return true;
    }

    /**
     * Deletes a category relation for a given category
     *
     * @param int    $categoryId Category id
     * @param string $categoryLang Category language
     * @param bool   $deleteForAllLanguages Delete all languages?
     */
    public function delete(int $categoryId, string $categoryLang, bool $deleteForAllLanguages = false): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqcategoryrelations WHERE category_id = %d',
            Database::getTablePrefix(),
            $categoryId
        );

        if (!$deleteForAllLanguages) {
            $query .= sprintf(" AND category_lang = '%s'", $categoryLang);
        }

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Deletes category relations to a record.
     *
     * @param int    $faqId   Record id
     * @param string $faqLanguage Language
     */
    public function deleteByFAQ(int $faqId, string $faqLanguage): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->config->getDb()->escape($faqLanguage)
        );

        return (bool) $this->config->getDb()->query($query);
    }
}
