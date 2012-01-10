<?php
/**
 * The Relation class for dynamic related record linking
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   PMF_Relation
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Relation
 * 
 * @category  phpMyFAQ
 * @package   PMF_Relation
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-18
 */
class PMF_Relation
{
    /**
     * DB handle
     *
     * @var PMF_DD_Driver
     */
    private $db;

    /**
     * Language
     *
     * @var PMF_Language
     */
    private $language;

    /**
     * Constructor
     *
     * @param PMF_DB_Driver $database Database connection
     * @param PMF_Language  $language Language object
     *
     * @return PMF_Relation
     */
    function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        $this->db       = $database;
        $this->language = $language;
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language
     *
     * @param integer $recordId FAQ ID
     * @param string  $question FAQ title
     * @param string  $keywords FAQ keywords
     * 
     * @return array
     */
    public function getAllRelatedById($recordId, $question, $keywords)
    {
        $terms  = str_replace('-', ' ', $question) . $keywords;
        $search = PMF_Search_Factory::create($this->language, array('database' => PMF_Db::getType()));

        $search->setDatabaseHandle($this->db)
               ->setTable(SQLPREFIX . 'faqdata AS fd')
               ->setResultColumns(array(
                    'fd.id AS id',
                    'fd.lang AS lang',
                    'fcr.category_id AS category_id',
                    'fd.thema AS question',
                    'fd.content AS answer'))
               ->setJoinedTable(SQLPREFIX . 'faqcategoryrelations AS fcr')
               ->setJoinedColumns(array(
                    'fd.id = fcr.record_id', 
                    'fd.lang = fcr.record_lang'))
               ->setConditions(array('fd.active' => "'yes'",
                                     'fd.lang'   => "'" . $this->language->getLanguage() . "'"))
               ->setMatchingColumns(array('fd.thema', 'fd.content', 'fd.keywords')
        );
        
        $result = $search->search($terms);

        return $this->db->fetchAll($result);
    }
}
