<?php

/**
 * The Relation class for dynamic related record linking.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-18
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Search\SearchFactory;

/**
 * Class Relation
 * @package phpMyFAQ
 */
readonly class Relation
{
    /**
     * Relation constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language.
     *
     * @param string $question FAQ title
     * @param string $keywords FAQ keywords
     * @throws Exception
     */
    public function getAllRelatedByQuestion(string $question, string $keywords): array
    {
        $terms = str_replace('-', ' ', $question) . ' ' . $keywords;
        $searchDatabase = SearchFactory::create($this->configuration, ['database' => Database::getType()]);

        $searchDatabase
            ->setTable(Database::getTablePrefix() . 'faqdata AS fd')
            ->setResultColumns(
                [
                    'fd.id AS id',
                    'fd.lang AS lang',
                    'fcr.category_id AS category_id',
                    'fd.thema AS question',
                    'fd.content AS answer',
                    'fd.keywords AS keywords'
                ]
            )
            ->setJoinedTable(Database::getTablePrefix() . 'faqcategoryrelations AS fcr')
            ->setJoinedColumns(
                [
                'fd.id = fcr.record_id',
                'fd.lang = fcr.record_lang',
                ]
            )
            ->setConditions(
                [
                    'fd.active' => "'yes'",
                    'fd.lang' => "'" . $this->configuration->getLanguage()->getLanguage() . "'",
                ]
            )
            ->setMatchingColumns(['fd.keywords', 'fd.thema', 'fd.content'])
            ->disableRelevance();

        $result = $searchDatabase->search($terms);

        return $this->configuration->getDb()->fetchAll($result);
    }
}
