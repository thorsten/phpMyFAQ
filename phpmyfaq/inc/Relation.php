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
     * @return string
     */
    public function getAllRelatedById($recordId, $question, $keywords)
    {
        global $sids;
        
        $relatedHtml = '';
        $terms       = str_replace('-', ' ', $question) . $keywords;
        $search      = PMF_Search_Factory::create($this->language, array('database' => PMF_Db::getType()));
        
        $i = $lastId = 0;

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

        // @todo use PMF_Search_Resultset
        // @todo add missing check on permissions!

        while (($row = $this->db->fetch_object($result)) && 
               ($i < PMF_Configuration::getInstance()->get('records.numberOfRelatedArticles'))) {
            
             if ($row->id == $recordId || $row->id == $lastId) {
                continue;
            }
            $relatedHtml .= ('' == $relatedHtml ? '<ul>' : '');
            $relatedHtml .= '<li>';
            $url = sprintf(
                '%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                $sids,
                $row->category_id,
                $row->id,
                $row->lang
            );
            $oLink             = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle  = $row->question;
            $oLink->text       = $row->question;
            $oLink->tooltip    = $row->question;
            $relatedHtml .= $oLink->toHtmlAnchor().'</li>';
            $i++;
            $lastId = $row->id;
        }
        $relatedHtml .= ($i > 0 ? '</ul>' : '');
        
        return ('' == $relatedHtml ? '-' : $relatedHtml);
    }
}
