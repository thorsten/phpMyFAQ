<?php
/**
* $Id: Relation.php,v 1.1 2006-06-18 18:35:48 thorstenr Exp $
*
* The Relation class for dynamic related record linking
*
* @author       Marco Enders <marco@minimarco.de>
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @package      phpMyFAQ
* @since        2006-06-18
* @copyright    (c) 2006 phpMyFAQ Team
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
*/

class PMF_Relation
{
    /**
    * DB handle
    *
    * @var  object
    */
    var $db;

    /**
    * Language
    *
    * @var  string
    */
    var $language;

    /**
    * Language strings
    *
    * @var  string
    */
    var $pmf_lang;

    /**
    * Constructor
    *
    */
    function PMF_Relation(&$db, $language)
    {
        global $PMF_LANG;

        $this->db = &$db;
        $this->language = $language;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * Saves the keywords for the related articles
     *
     * @param    string
     * @access   public
     * @author   Marco Enders <marco@minimarco.de>
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function saveRelationKeywords($relation_keywords)
    {
        $relation = explode(' ', $relation_keywords);
        foreach ($relation as $relation_word) {
            if (strlen($relation_word) > 2) {
                $query = sprintf(
                    "SELECT
                        keyword
                    FROM
                        %sfaqkeywords
                    WHERE
                        lang = '%s' AND keyword = '%s'",
                    SQLPREFIX,
                    $this->language,
                    $relation_word);
                $result_relation = $this->db->query($query);
            }

            if ($db->num_rows($result_relation) == 0) {
                $query = sprintf(
                    "INSERT INTO
                        %sfaqkeywords
                    VALUES
                        (%d,'%s')",
                    SQLPREFIX,
                    $relation_word);
                $this->db->query($query);
            }
        }
    }

    /**
     * Verlinkt einen Artikel dynamisch mit der Suche über die übergebenen Schlüsselwörter
     *
     * @param    string     $strHighlight
     * @param    string     $strSource
     * @param    integer    $intCount
     * @return   string
     * @access   public
     * @author   Marco Enders <marco@minimarco.de>
     * @author   Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function setRelationLinks($strHighlight, $strSource, $intCount = 0)
    {
        global $in_content;
        $x = 0;
        $arrMatch = array();

        preg_match_all(
            '/(<a[^<>]*?>.*?<\/a>)|(<.*?>)/is',
            $strSource,
            $arrMatch);
        $strSource = preg_replace(
            '/(<a[^<>]*?>.*?<\/a>)|(<.*?>)/is',
            '~+*# replaced html #*+~',
            $strSource);
        $x = $x + preg_match(
            '/('.preg_quote($strHighlight).')/ims',
            $strSource);
        $strSource = preg_replace(
            '/('.preg_quote($strHighlight).')/ims',
            '<a href="index.php?action=search&search='.$strHighlight.'" title="Insgesamt '.$intCount.' Artikel zu diesem Schlagwort ('.$strHighlight.') vorhanden. Jetzt danach suchen..." class="relation">$1</a>',
            $strSource);

        foreach($arrMatch[0] as $html) {
            $strSource = preg_replace(
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

}
