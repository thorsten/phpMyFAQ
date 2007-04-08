<?php
/**
 * $Id$
 *
 * The main Sitemap class
 *
 * @package      phpMyFAQ
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2007-03-30
 * @copyright    (c) 2007 phpMyFAQ Team
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

/**
 * This include is needed for accessing to mod_rewrite support configuration value
 */
require_once(PMF_INCLUDE_DIR.'/Link.php');

class PMF_Sitemap
{
    /**
     * DB handle
     *
     * @var object PMF_Db
     */
    var $db;

    /**
     * Language
     *
     * @var string
     */
    var $language;

    /**
     * Database type
     *
     * @var string
     */
    var $type;

    /**
     * Constructor
     *
     * @param   object  PMF_Db
     * @param   string  $language
     * @since   2007-03-30
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function PMF_Sitemap(&$db, $language)
    {
        global $DB;

        $this->db       = &$db;
        $this->language = $language;
        $this->type     = $DB['type'];
    }

    /**
     * Returns all available first letters
     *
     * @return  array
     * @access  public
     * @since   2007-03-30
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getAllFirstLetters()
    {
        global $sids;

        $writeLetters = '<p id="sitemapletters">';

        switch($this->type) {
            case 'db2':
            case 'sqlite':
                $query = sprintf("
                    SELECT
                        DISTINCT substr(thema, 1, 1) AS letters
                    FROM
                        %sfaqdata
                    WHERE
                        lang = '%s'
                    AND
                        active = 'yes'
                    ORDER BY
                        letters",
                    SQLPREFIX,
                    $this->language);
                break;

            default:
                $query = sprintf("
                    SELECT
                        DISTINCT substring(thema, 1, 1) AS letters
                    FROM
                        %sfaqdata
                    WHERE
                        lang = '%s'
                    AND
                        active = 'yes'
                    ORDER BY
                        letters",
                    SQLPREFIX,
                    $this->language);
                break;
        }

        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $letters = strtoupper($row->letters);
            if (preg_match("/^[a-z0-9]/i", $letters)) {
                $url = sprintf('%saction=sitemap&amp;letter=%s&amp;lang=%s',
                    $sids,
                    $letters,
                    $this->language);
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
            $oLink->text = $letters;
            $writeLetters .= $oLink->toHtmlAnchor().' ';
            }
        }
        $writeLetters .= '</p>';

        return $writeLetters;
    }

    /**
     * Returns all records from the current first letter
     *
     * @param   string  $letter
     * @return  array
     * @access  public
     * @since   2007-03-30
     * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    function getRecordsFromLetter($letter = 'A')
    {
        global $sids, $PMF_LANG;

        $letter = strtoupper($this->db->escape_string(substr($letter, 0, 1)));

        $writeMap = '<ul>';

        switch($this->type) {
            case 'db2':
            case 'sqlite':
                $query = sprintf("
                    SELECT
                        a.thema AS thema,
                        a.id AS id,
                        a.lang AS lang,
                        b.category_id AS category_id,
                        a.content AS snap
                    FROM
                        %sfaqdata a,
                        %sfaqcategoryrelations b
                    WHERE
                        a.id = b.record_id
                    AND
                        substr(thema, 1, 1) = '%s'
                    AND lang = '%s' AND active = 'yes'",
                    SQLPREFIX,
                    SQLPREFIX,
                    $letter,
                    $this->language);
                break;

            default:
                $query = sprintf("
                    SELECT
                        a.thema AS thema,
                        a.id AS id,
                        a.lang AS lang,
                        b.category_id AS category_id,
                        a.content AS snap
                    FROM
                        %sfaqdata a,
                        %sfaqcategoryrelations b
                    WHERE
                        a.id = b.record_id
                    AND
                        substring(thema, 1, 1) = '%s'
                    AND lang = '%s' AND active = 'yes'",
                    SQLPREFIX,
                    SQLPREFIX,
                    $letter,
                    $this->language);
                break;
        }

        $result = $this->db->query($query);
        $oldId = 0;
        while ($row = $this->db->fetch_object($result)) {
            if ($oldId != $row->id) {
                $title = PMF_htmlentities($row->thema, ENT_QUOTES, $PMF_LANG['metaCharset']);
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang);

                $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;

                $writeMap .= '<li>'.$oLink->toHtmlAnchor().'<br />'."\n";
                $writeMap .= chopString(strip_tags($row->snap), 25). " ...</li>\n";
            }
            $oldId = $row->id;
        }

        $writeMap .= '</ul>';

        return $writeMap;
    }
}