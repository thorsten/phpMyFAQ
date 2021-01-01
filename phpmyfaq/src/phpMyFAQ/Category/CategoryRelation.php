<?php

/**
 * Category relations class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Class CategoryRelation
 *
 * @package phpMyFAQ\Category
 */
class CategoryRelation
{
    /**
     * @var Configuration
     */
    private $config;

    /** @var array */
    private $groups;

    /**
     * CategoryRelation constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $groups
     * @return CategoryRelation
     */
    public function setGroups(array $groups): CategoryRelation
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Create a matrix for representing categories and FAQs.
     *
     * @return array
     */
    public function getCategoryFaqsMatrix()
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

    /**
     * Returns the number of records in each category.
     *
     * @param bool $categoryRestriction
     * @return int[]
     */
    public function getNumberOfFaqsPerCategory(bool $categoryRestriction = false): array
    {
        $numRecordsByCat = [];
        if ($categoryRestriction) {
            $query = sprintf(
                '
                SELECT
                    fcr.category_id AS category_id,
                    COUNT(fcr.record_id) AS number
                FROM
                    %sfaqcategoryrelations fcr
                LEFT JOIN
                    %sfaqdata fd on fcr.record_id = fd.id
                LEFT JOIN
                    %sfaqdata_group fdg on fdg.record_id = fcr.record_id
                WHERE
                    fdg.group_id = %s
                AND
                    fcr.record_lang = fd.lang
                GROUP BY fcr.category_id',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $this->groups[0]
            );
        } else {
            $query = sprintf(
                '
                SELECT
                    fcr.category_id AS category_id,
                    COUNT(fcr.record_id) AS number
                FROM
                    %sfaqcategoryrelations fcr, %sfaqdata fd
                WHERE
                    fcr.record_id = fd.id
                AND
                    fcr.record_lang = fd.lang
                GROUP BY fcr.category_id',
                Database::getTablePrefix(),
                Database::getTablePrefix()
            );
        }
        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $numRecordsByCat[$row->category_id] = (int)$row->number;
            }
        }

        return $numRecordsByCat;
    }

    /**
     * Returns the categories from a FAQ id and language.
     *
     * @param int    $faqId FAQ id
     * @param string $faqLang FAQ language
     * @return array
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
            $categories[$row->category_id] = array(
                'category_id' => $row->category_id,
                'category_lang' => $row->category_lang,
            );
        }

        return $categories;
    }

    /**
     * Adds new category relations to a FAQ
     *
     * @param array  $categories Array of categories
     * @param int    $faqId FAQ id
     * @param string $language Language
     * @return bool
     */
    public function add(array $categories, $faqId, $language): bool
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
     * @return bool
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

        return $this->config->getDb()->query($query);
    }

    /**
     * Deletes category relations to a record.
     *
     * @param int    $faqId   Record id
     * @param string $faqLanguage Language
     * @return bool
     */
    public function deleteByFAQ(int $faqId, string $faqLanguage): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $faqLanguage
        );

        return $this->config->getDb()->query($query);
    }
}
