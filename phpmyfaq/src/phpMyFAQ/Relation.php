<?php

/**
 * The Relation class for dynamic related record linking.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
class Relation
{
    /**
     * Configuration object.
     * @var Configuration
     */
    private $config;

    /**
     * Relation constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language.
     *
     * @param string $question FAQ title
     * @param string $keywords FAQ keywords
     * @return mixed[]
     * @throws Exception
     */
    public function getAllRelatedByQuestion(string $question, string $keywords): array
    {
        $terms = str_replace('-', ' ', $question) . ' ' . $keywords;
        $search = SearchFactory::create(
            $this->config,
            ['database' => Database::getType()]
        );

        $search
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
                    'fd.lang' => "'" . $this->config->getLanguage()->getLanguage() . "'",
                ]
            )
            ->setMatchingColumns(['fd.keywords'])
            ->disableRelevance();

        $result = $search->search($terms);

        return $this->config->getDb()->fetchAll($result);
    }
}
