<?php
/**
 * The main Sitemap class
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
 * @package   PMF_Sitemap
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

require_once PMF_INCLUDE_DIR . '/Link.php';

/**
 * PMF_Sitemap 
 *
 * @category  phpMyFAQ
 * @package   PMF_Sitemap
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-30
 */
class PMF_Sitemap
{
    /**
     * DB handle
     *
     * @var PMF_Db
     */
    private $db = null;

    /**
     * Language
     *
     * @var string
     */
    private $language = '';

    /**
     * Database type
     *
     * @var string
     */
    private $type = '';

    /**
     * Users
     *
     * @var array
     */
    private $user = array();

    /**
     * Groups
     *
     * @var array
     */
    private $groups = array();

    /**
     * Flag for Group support
     *
     * @var boolean
     */
    private $groupSupport = false;

    /**
     * Constructor
     *
     * @param integer $user   User
     * @param array   $groups Groups
     * 
     * @return PMF_Sitemap
     */
    public function __construct($user = null, $groups = null)
    {
        global $DB;

        $this->db       = PMF_Db::getInstance();
        $this->language = PMF_Language::$language;
        $this->type     = $DB['type'];

        if (is_null($user)) {
            $this->user  = -1;
        } else {
            $this->user  = $user;
        }
        if (is_null($groups)) {
            $this->groups       = array(-1);
        } else {
            $this->groups       = $groups;
        }
        if (PMF_Configuration::getInstance()->get('security.permLevel') == 'medium') {
            $this->groupSupport = true;
        }
    }

    /**
     * Returns all available first letters
     *
     * @return array
     * @since  2007-03-30
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getAllFirstLetters()
    {
        global $sids;

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $writeLetters = '<p id="sitemapletters">';

        switch($this->type) {
        case 'db2':
        case 'sqlite':
            $query = sprintf("
                    SELECT
                        DISTINCT UPPER(SUBSTR(fd.thema, 1, 1)) AS letters
                    FROM
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqdata_group AS fdg
                    ON
                        fd.id = fdg.record_id
                    LEFT JOIN
                        %sfaqdata_user AS fdu
                    ON
                        fd.id = fdu.record_id
                    WHERE
                        fd.lang = '%s'
                    AND
                        fd.active = 'yes'
                    AND
                        %s
                    ORDER BY
                        letters",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $this->language,
            $permPart);
            break;

        default:
            $query = sprintf("
                    SELECT
                        DISTINCT UPPER(SUBSTRING(fd.thema, 1, 1)) AS letters
                    FROM
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqdata_group AS fdg
                    ON
                        fd.id = fdg.record_id
                    LEFT JOIN
                        %sfaqdata_user AS fdu
                    ON
                        fd.id = fdu.record_id
                    WHERE
                        fd.lang = '%s'
                    AND
                        fd.active = 'yes'
                    AND
                        %s
                    ORDER BY
                        letters",
            SQLPREFIX,
            SQLPREFIX,
            SQLPREFIX,
            $this->language,
            $permPart);
            break;
        }

        $result = $this->db->query($query);
        while ($row = $this->db->fetch_object($result)) {
            $letters = PMF_String::strtoupper($row->letters);
            if (PMF_String::preg_match("/^[一-龠]+|[ぁ-ん]+|[ァ-ヴー]+|[a-zA-Z0-9]+|[ａ-ｚＡ-Ｚ０-９]/i", $letters)) {
                $url = sprintf('%saction=sitemap&amp;letter=%s&amp;lang=%s',
                    $sids,
                    $letters,
                    $this->language);
                $oLink         = new PMF_Link(PMF_Link::getSystemRelativeUri() . '?' . $url);
                $oLink->text   = (string)$letters;
                $writeLetters .= $oLink->toHtmlAnchor().' ';
            }
        }
        $writeLetters .= '</p>';

        return $writeLetters;
    }

    /**
     * Returns all records from the current first letter
     *
     * @param  string $letter Letter
     * @return array
     * @since  2007-03-30
     * @author Thorsten Rinne <thorsten@phpmyfaq.de>
     */
    public function getRecordsFromLetter($letter = 'A')
    {
        global $sids, $PMF_LANG;

        if ($this->groupSupport) {
            $permPart = sprintf("( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))",
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups));
        } else {
            $permPart = sprintf("( fdu.user_id = %d OR fdu.user_id = -1 )",
                $this->user);
        }

        $letter = PMF_String::strtoupper($this->db->escape_string(PMF_String::substr($letter, 0, 1)));

        $writeMap = '<ul>';

        switch($this->type) {
            case 'db2':
            case 'sqlite':
                $query = sprintf("
                    SELECT
                        fd.thema AS thema,
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.content AS snap
                    FROM
                        %sfaqcategoryrelations fcr,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqdata_group AS fdg
                    ON
                        fd.id = fdg.record_id
                    LEFT JOIN
                        %sfaqdata_user AS fdu
                    ON
                        fd.id = fdu.record_id
                    WHERE
                        fd.id = fcr.record_id
                    AND
                        SUBSTR(fd.thema, 1, 1) = '%s'
                    AND
                        fd.lang = '%s'
                    AND
                        fd.active = 'yes'
                    AND
                        %s",
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX,
                    $letter,
                    $this->language,
                    $permPart);
                break;

            default:
                $query = sprintf("
                    SELECT
                        fd.thema AS thema,
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.content AS snap
                    FROM
                        %sfaqcategoryrelations fcr,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqdata_group AS fdg
                    ON
                        fd.id = fdg.record_id
                    LEFT JOIN
                        %sfaqdata_user AS fdu
                    ON
                        fd.id = fdu.record_id
                    WHERE
                        fd.id = fcr.record_id
                    AND
                        SUBSTRING(fd.thema, 1, 1) = '%s'
                    AND
                        fd.lang = '%s'
                    AND
                        fd.active = 'yes'
                    AND
                        %s",
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX,
                    SQLPREFIX,
                    $letter,
                    $this->language,
                    $permPart);
                break;
        }

        $result = $this->db->query($query);
        $oldId = 0;
        while ($row = $this->db->fetch_object($result)) {
            if ($oldId != $row->id) {
                $title = PMF_String::htmlspecialchars($row->thema, ENT_QUOTES, 'utf-8');
                $url   = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang);

                $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().'?'.$url);
                $oLink->itemTitle = $row->thema;
                $oLink->text      = $title;
                $oLink->tooltip   = $title;

                $writeMap .= '<li>'.$oLink->toHtmlAnchor().'<br />'."\n";
                $writeMap .= PMF_Utils::chopString(strip_tags($row->snap), 25). " ...</li>\n";
            }
            $oldId = $row->id;
        }

        $writeMap .= '</ul>';

        return $writeMap;
    }
}
