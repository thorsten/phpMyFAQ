<?php

/**
 * The main Sitemap class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-30
 */

namespace phpMyFAQ;

use ParsedownExtra;
use phpMyFAQ\Database\Sqlite3;

/**
 * Class Sitemap
 *
 * @package phpMyFAQ
 */
class Sitemap
{
    /**
     * User
     */
    private int $user = -1;

    /**
     * Groups.
     *
     * @var int[]
     */
    private array $groups = [];

    /**
     * Flag for Group support.
     */
    private bool $groupSupport = false;

    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
        if ($this->config->get('security.permLevel') !== 'basic') {
            $this->groupSupport = true;
        }
    }

    public function setUser(int $userId = -1): void
    {
        $this->user = $userId;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    /**
     * Returns all available first letters.
     */
    public function getAllFirstLetters(): string
    {
        global $sids;

        if ($this->groupSupport) {
            $permPart = sprintf(
                '( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups)
            );
        } else {
            $permPart = sprintf(
                '( fdu.user_id = %d OR fdu.user_id = -1 )',
                $this->user
            );
        }

        $renderLetters = '<ul class="nav">';

        if ($this->config->getDb() instanceof Sqlite3) {
            $query = sprintf(
                "
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
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $this->config->getLanguage()->getLanguage(),
                $permPart
            );
        } else {
            $query = sprintf(
                "
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
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $this->config->getLanguage()->getLanguage(),
                $permPart
            );
        }

        $result = $this->config->getDb()->query($query);
        while ($row = $this->config->getDb()->fetchObject($result)) {
            $letters = Strings::strtoupper($row->letters);
            if (Strings::preg_match("/^\w+/iu", $letters)) {
                $url = sprintf(
                    '%sindex.php?%saction=sitemap&amp;letter=%s&amp;lang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $letters,
                    $this->config->getLanguage()->getLanguage()
                );
                $oLink = new Link($url, $this->config);
                $oLink->text = $letters;
                $oLink->class = 'nav-link';
                $renderLetters .= '<li class="nav-item">' . $oLink->toHtmlAnchor() . '</li>';
            }
        }

        return $renderLetters . '</ul>';
    }

    /**
     * Returns all records from the current first letter.
     *
     * @param string $letter Letter
     * @throws \Exception
     */
    public function getRecordsFromLetter($letter = 'A'): string
    {
        global $sids;

        if ($this->groupSupport) {
            $permPart = sprintf(
                '( fdg.group_id IN (%s)
            OR
                (fdu.user_id = %d AND fdg.group_id IN (%s)))',
                implode(', ', $this->groups),
                $this->user,
                implode(', ', $this->groups)
            );
        } else {
            $permPart = sprintf(
                '( fdu.user_id = %d OR fdu.user_id = -1 )',
                $this->user
            );
        }

        $letter = Strings::strtoupper($this->config->getDb()->escape(Strings::substr($letter, 0, 1)));

        $renderSiteMap = '';

        $query = match (Database::getType()) {
            'sqlite3' => sprintf(
                "
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
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $letter,
                $this->config->getLanguage()->getLanguage(),
                $permPart
            ),
            default => sprintf(
                "
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
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                $letter,
                $this->config->getLanguage()->getLanguage(),
                $permPart
            ),
        };

        $result = $this->config->getDb()->query($query);
        $oldId = 0;
        $parsedown = new ParsedownExtra();
        while ($row = $this->config->getDb()->fetchObject($result)) {
            if ($oldId != $row->id) {
                $title = Strings::htmlspecialchars($row->thema, ENT_QUOTES, 'utf-8');
                $url = sprintf(
                    '%sindex.php?%saction=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->config->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang
                );

                $oLink = new Link($url, $this->config);
                $oLink->itemTitle = $row->thema;
                $oLink->text = $title;
                $oLink->tooltip = $title;

                $renderSiteMap .= '<li>' . $oLink->toHtmlAnchor() . '<br>' . "\n";

                if ($this->config->get('main.enableMarkdownEditor')) {
                    $renderSiteMap .= Utils::chopString(strip_tags((string) $parsedown->text($row->snap)), 25) .
                        " ...</li>\n";
                } else {
                    $renderSiteMap .= Utils::chopString(strip_tags((string) $row->snap), 25) .
                        " ...</li>\n";
                }
            }
            $oldId = $row->id;
        }

        return empty($renderSiteMap) ? '' : '<ul>' . $renderSiteMap . '</ul>';
    }
}
