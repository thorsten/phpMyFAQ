<?php
/**
 * The main Sitemap class
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Sitemap
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Sitemap 
 *
 * @category  phpMyFAQ
 * @package   PMF_Sitemap
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2007-03-30
 */
class PMF_Sitemap
{
    /**
     * @var PMF_Configuration
     */
    private $_config;

    /**
     * Database type
     *
     * @var string
     */
    private $type = '';

    /**
     * Users
     *
     * @var integer
     */
    private $user = -1;

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
     * @param PMF_Configuration $config
     *
     * @return PMF_Sitemap
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config = $config;

        if ($this->_config->get('security.permLevel') == 'medium') {
            $this->groupSupport = true;
        }
    }

    /**
     * @param integer $userId
     */
    public function setUser($userId = -1)
    {
        $this->user = $userId;
    }

    /**
     * @param array $groups
     */
    public function setGroups(Array $groups)
    {
        $this->groups = $groups;
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

        if ($this->_config->getDb() instanceof PMF_DB_Sqlite || $this->_config->getDb() instanceof PMF_DB_Sqlite3) {

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
                $this->_config->getLanguage()->getLanguage(),
                $permPart
            );
        } else {

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
                $this->_config->getLanguage()->getLanguage(),
                $permPart
            );
        }

        $result = $this->_config->getDb()->query($query);
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            $letters = PMF_String::strtoupper($row->letters);
            if (PMF_String::preg_match("/^[一-龠]+|[ぁ-ん]+|[ァ-ヴー]+|[a-zA-Z0-9]+|[ａ-ｚＡ-Ｚ０-９]/i", $letters)) {
                $url = sprintf(
                    '%s?%saction=sitemap&amp;letter=%s&amp;lang=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $sids,
                    $letters,
                    $this->_config->getLanguage()->getLanguage());
                $oLink         = new PMF_Link($url, $this->_config);
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

        $letter = PMF_String::strtoupper($this->_config->getDb()->escape(PMF_String::substr($letter, 0, 1)));

        $writeMap = '';

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
                    $this->_config->getLanguage()->getLanguage(),
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
                    $this->_config->getLanguage()->getLanguage(),
                    $permPart);
                break;
        }

        $result = $this->_config->getDb()->query($query);
        $oldId = 0;
        while ($row = $this->_config->getDb()->fetchObject($result)) {
            if ($oldId != $row->id) {
                $title = PMF_String::htmlspecialchars($row->thema, ENT_QUOTES, 'utf-8');
                $url   = sprintf(
                    '%s?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    PMF_Link::getSystemRelativeUri(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink            = new PMF_Link($url, $this->_config);
                $oLink->itemTitle = $row->thema;
                $oLink->text      = $title;
                $oLink->tooltip   = $title;

                $writeMap .= '<li>'.$oLink->toHtmlAnchor().'<br />'."\n";
                $writeMap .= PMF_Utils::chopString(strip_tags($row->snap), 25). " ...</li>\n";
            }
            $oldId = $row->id;
        }

        $writeMap = empty($writeMap) ? '' : '<ul>' . $writeMap . '</ul>';

        return $writeMap;
    }
}
