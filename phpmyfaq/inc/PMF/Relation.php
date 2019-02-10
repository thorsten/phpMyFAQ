<?php

/**
 * The Relation class for dynamic related record linking.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-18
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Relation.
 *
 * @category  phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-18
 */
class PMF_Relation
{
    /**
     * DB handle.
     *
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Constructor.
     *
     * @param PMF_Configuration
     *
     * @return PMF_Relation
     */
    public function __construct(PMF_Configuration $config)
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
        $search = PMF_Search_Factory::create(
            $this->_config,
            ['database' => PMF_Db::getType()]
        );

        $search
            ->setTable(PMF_Db::getTablePrefix().'faqdata AS fd')
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
            ->setJoinedTable(PMF_Db::getTablePrefix().'faqcategoryrelations AS fcr')
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
