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
 * @copyright 2006-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-18
 */

/**
 * PMF_Relation
 * 
 * @category  phpMyFAQ
 * @package   PMF_Relation
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2010 phpMyFAQ Team
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
    * Language strings
    *
    * @var  string
    */
    private $pmf_lang;

    /**
    * Constructor
    *
    */
    function __construct(PMF_DB_Driver $database, PMF_Language $language)
    {
        global $PMF_LANG;

        $this->db       = $database;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * Verlinkt einen Artikel dynamisch mit der Suche �ber die �bergebenen Schl�sselw�rter
     *
     * @param    string     $strHighlight
     * @param    string     $strSource
     * @param    integer    $intCount
     * @return   string
     * @author   Marco Enders <marco@minimarco.de>
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function setRelationLinks($strHighlight, $strSource, $intCount = 0)
    {
        global $in_content;
        $x = 0;
        $arrMatch = array();

        PMF_String::preg_match_all(
            '/(<a[^<>]*?>.*?<\/a>)|(<.*?>)/is',
            $strSource,
            $arrMatch);
        $strSource = PMF_String::preg_replace(
            '/(<a[^<>]*?>.*?<\/a>)|(<.*?>)/is',
            '~+*# replaced html #*+~',
            $strSource);
        $x = $x + PMF_String::preg_match(
            '/('.preg_quote($strHighlight).')/ims',
            $strSource);
        $strSource = PMF_String::preg_replace(
            '/('.preg_quote($strHighlight).')/ims',
            '<a href="index.php?action=search&search='.$strHighlight.'" title="Insgesamt '.$intCount.' Artikel zu diesem Schlagwort ('.$strHighlight.') vorhanden. Jetzt danach suchen..." class="relation">$1</a>',
            $strSource);

        foreach($arrMatch[0] as $html) {
            $strSource = PMF_String::preg_replace(
                '/'.preg_quote('~+*# replaced html #*+~').'/',
                $html,
                $strSource,
                1);
        }

        if ($x == 0) {
            $in_content = false;
        } else {
            $in_content = true;
        }
        return $strSource;
    }


    /**
     * Returns all relevant articles for a FAQ record with the same language
     *
     * @param integer $record_id FAQ ID
     * @param string  $thema     FAQ title
     * 
     * @return string
     */
    public function getAllRelatedById($record_id, $article_name, $keywords)
    {
        global $sids;
        
        $relevantslisting = '';
        $begriffe         = str_replace('-', ' ', $article_name) . $keywords;
        $search           = PMF_Search_Factory::create($this->language, array('database' => PMF_Db::getType()));
        
        $i = $last_id = 0;

        $search->setDatabaseHandle($this->db)
               ->setTable(SQLPREFIX . 'faqdata AS fd')
               ->setResultColumns(array(
                    'fd.id AS id',
                    'fd.lang AS lang',
                    'fcr.category_id AS category_id',
                    'fd.thema AS thema',
                    'fd.content AS content'))
               ->setJoinedTable(SQLPREFIX . 'faqcategoryrelations AS fcr')
               ->setJoinedColumns(array(
                    'fd.id = fcr.record_id', 
                    'fd.lang = fcr.record_lang'))
               ->setConditions(array('fd.active' => "'yes'",
                                     'fd.lang'   => "'" . $this->language->getLanguage() . "'"))
               ->setMatchingColumns(array('fd.thema', 'fd.content', 'fd.keywords'));
        
        $result = $search->search($begriffe);
        
        while (($row = $this->db->fetch_object($result)) && 
               ($i < PMF_Configuration::getInstance()->get('records.numberOfRelatedArticles'))) {
            
             if ($row->id == $record_id || $row->id == $last_id) {
                continue;
            }
            $relevantslisting .= ('' == $relevantslisting ? '<ul>' : '');
            $relevantslisting .= '<li>';
            $url = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang);
            $oLink             = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->itemTitle  = $row->thema;
            $oLink->text       = $row->thema;
            $oLink->tooltip    = $row->thema;
            $relevantslisting .= $oLink->toHtmlAnchor().'</li>';
            $i++;
            $last_id = $row->id;
        }
        $relevantslisting .= ($i > 0 ? '</ul>' : '');

        return ('' == $relevantslisting ? '-' : $relevantslisting);
    }


}
