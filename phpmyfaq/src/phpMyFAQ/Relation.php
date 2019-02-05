<?php

namespace phpMyFAQ;

/**
 * The Relation class for dynamic related record linking.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Marco Enders <marco@minimarco.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-06-18
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Search\SearchFactory;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Relation.
 *
 * @package phpMyFAQ
 * @author Marco Enders <marco@minimarco.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-06-18
 */
class Relation
{
    /**
     * DB handle.
     *
     * @var Configuration
     */
    private $_config;

    /**
     * Constructor.
     *
     * @param Configuration
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language.
     *
     * @param int    $recordId FAQ ID
     * @param string $question FAQ title
     * @param string $keywords FAQ keywords
     *
     * @return array
     */
    public function getAllRelatedById($recordId, $question, $keywords)
    {
        $terms = str_replace('-', ' ', $question).' '.$keywords;
        $search = SearchFactory::create(
            $this->_config,
            ['database' => Db::getType()]
        );

        $search
            ->setTable(Db::getTablePrefix().'faqdata AS fd')
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
            ->setJoinedTable(Db::getTablePrefix().'faqcategoryrelations AS fcr')
            ->setJoinedColumns(
               [
                'fd.id = fcr.record_id',
                'fd.lang = fcr.record_lang',
               ]
            )
            ->setConditions(
                [
                    'fd.active' => "'yes'",
                    'fd.lang' => "'".$this->_config->getLanguage()->getLanguage()."'",
                ]
            )
            ->setMatchingColumns(['fd.keywords'])
            ->disableRelevance();

        $result = $search->search($terms);

        return $this->_config->getDb()->fetchAll($result);
    }
}
