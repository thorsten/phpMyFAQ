<?php

/**
 * The News class for phpMyFAQ news.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-25
 */

namespace phpMyFAQ;

use Exception;

/**
 * Class News
 *
 * @package phpMyFAQ
 */
class News
{
    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * Language strings.
     *
     * @var array<string>
     */
    private $pmfLang;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        global $PMF_LANG;

        $this->config = $config;
        $this->pmfLang = $PMF_LANG;
    }

    /**
     * Function for generating the HTML5 code for the current news.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @return string
     * @throws Exception
     */
    public function getNews(bool $showArchive = false, bool $active = true): string
    {
        $output = '';
        $news = $this->getLatestData($showArchive, $active);
        $date = new Date($this->config);

        foreach ($news as $item) {
            $url = sprintf(
                '%sindex.php?action=news&amp;newsid=%d&amp;newslang=%s',
                $this->config->getDefaultUrl(),
                $item['id'],
                $item['lang']
            );

            $oLink = new Link($url, $this->config);

            if (isset($item['header'])) {
                $oLink->itemTitle = Strings::htmlentities($item['header']);
            }

            $output .= sprintf(
                '<h6%s><a id="news_%d" href="%s">%s <i aria-hidden="true" class="fa fa-caret-right"></i></a></h6>',
                ' class="pmf-news-heading"',
                $item['id'],
                Strings::htmlentities($oLink->toString()),
                Strings::htmlentities($item['header'])
            );

            $output .= sprintf('%s', $item['content']);

            if (strlen($item['link']) > 1) {
                $output .= sprintf(
                    '<br>%s <a href="%s" target="_%s">%s</a>',
                    $this->pmfLang['msgInfo'],
                    Strings::htmlentities($item['link']),
                    $item['target'],
                    Strings::htmlentities($item['linkTitle'])
                );
            }

            $output .= sprintf('<small class="text-muted">%s</small>', $date->format($item['date']));
        }

        return ('' == $output) ? $this->pmfLang['msgNoNews'] : $output;
    }

    /**
     * Return the latest news data.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @param bool $forceConfLimit Force to limit in configuration
     * @return array<int, array<mixed>>
     */
    public function getLatestData($showArchive = false, $active = true, $forceConfLimit = false): array
    {
        $news = [];
        $counter = 0;
        $now = date('YmdHis');

        $query = sprintf(
            "
            SELECT
                *
            FROM
                %sfaqnews
            WHERE
                date_start <= '%s'
            AND 
                date_end   >= '%s'
            %s
            AND
                lang = '%s'
            ORDER BY
                datum DESC",
            Database::getTablePrefix(),
            $now,
            $now,
            $active ? "AND active = 'y'" : '',
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        $numberOfShownNewsEntries = $this->config->get('records.numberOfShownNewsEntries');
        if ($numberOfShownNewsEntries > 0 && $this->config->getDb()->numRows($result) > 0) {
            while (($row = $this->config->getDb()->fetchObject($result))) {
                ++$counter;
                if (
                    ($showArchive && ($counter > $numberOfShownNewsEntries)) ||
                    ((!$showArchive) && (!$forceConfLimit) && ($counter <= $numberOfShownNewsEntries)) ||
                    ((!$showArchive) && $forceConfLimit)
                ) {
                    $url = sprintf(
                        '%sindex.php?action=news&amp;newsid=%d&amp;newslang=%s',
                        $this->config->getDefaultUrl(),
                        $row->id,
                        $row->lang
                    );
                    $oLink = new Link($url, $this->config);
                    $oLink->itemTitle = $row->header;

                    $item = [
                        'id' => (int)$row->id,
                        'lang' => $row->lang,
                        'date' => Date::createIsoDate($row->datum, DATE_ISO8601, true),
                        'header' => $row->header,
                        'content' => $row->artikel,
                        'authorName' => $row->author_name,
                        'authorEmail' => $row->author_email,
                        'dateStart' => $row->date_start,
                        'dateEnd' => $row->date_end,
                        'active' => ('y' == $row->active),
                        'allowComments' => ('y' == $row->comment),
                        'link' => $row->link,
                        'linkTitle' => $row->linktitel,
                        'target' => $row->target,
                        'url' => $oLink->toString()
                    ];
                    $news[] = $item;
                }
            }
        }

        return $news;
    }

    /**
     * Fetches all news headers.
     *
     * @return array<mixed>
     */
    public function getNewsHeader(): array
    {
        $headers = [];
        $now = date('YmdHis');

        $query = sprintf("
            SELECT
                id, datum, lang, header, active, date_start, date_end
            FROM
                %sfaqnews
            WHERE
                lang = '%s'
            ORDER BY
                datum DESC", Database::getTablePrefix(), $this->config->getLanguage()->getLanguage());

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $expired = ($now > $row->date_end);
                $headers[] = array(
                    'id' => $row->id,
                    'lang' => $row->lang,
                    'header' => $row->header,
                    'date' => Date::createIsoDate($row->datum),
                    'active' => $row->active,
                    'expired' => $expired,
                );
            }
        }

        return $headers;
    }

    /**
     * Fetches a news entry identified by its ID.
     *
     * @param int  $id ID of news
     * @param bool $admin Is admin
     * @return array<mixed>
     */
    public function getNewsEntry($id, $admin = false): array
    {
        $news = [];

        $query = sprintf("SELECT
                *
            FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'", Database::getTablePrefix(), $id, $this->config->getLanguage()->getLanguage());

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            if ($row = $this->config->getDb()->fetchObject($result)) {
                $content = $row->artikel;
                $active = ('y' == $row->active);
                $allowComments = ('y' == $row->comment);
                $expired = (date('YmdHis') > $row->date_end);

                if (!$admin) {
                    if (!$active) {
                        $content = $this->pmfLang['err_inactiveNews'];
                    }
                    if ($expired) {
                        $content = $this->pmfLang['err_expiredNews'];
                    }
                }

                $news = array(
                    'id' => $row->id,
                    'lang' => $row->lang,
                    'date' => Date::createIsoDate($row->datum),
                    'header' => $row->header,
                    'content' => $content,
                    'authorName' => $row->author_name,
                    'authorEmail' => $row->author_email,
                    'dateStart' => $row->date_start,
                    'dateEnd' => $row->date_end,
                    'active' => $active,
                    'allowComments' => $allowComments,
                    'link' => $row->link,
                    'linkTitle' => $row->linktitel,
                    'target' => $row->target,
                );
            }
        }

        return $news;
    }

    /**
     * Adds a new news entry.
     *
     * @param array<mixed> $data Array with news data
     * @return bool
     */
    public function addNewsEntry(array $data): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqnews
            (id, datum, lang, header, artikel, author_name, author_email, date_start, date_end, active, comment,
            link, linktitel, target)
                VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqnews', 'id'),
            $data['date'],
            $data['lang'],
            $this->config->getDb()->escape($data['header']),
            $this->config->getDb()->escape($data['content']),
            $this->config->getDb()->escape($data['authorName']),
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $this->config->getDb()->escape($data['link']),
            $this->config->getDb()->escape($data['linkTitle']),
            $data['target']
        );

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Updates a new news entry identified by its ID.
     *
     * @param int          $id News ID
     * @param array<mixed> $data Array with news data
     * @return bool
     */
    public function updateNewsEntry(int $id, array $data): bool
    {
        $query = sprintf(
            "
            UPDATE
                %sfaqnews
            SET
                datum = '%s',
                lang = '%s',
                header = '%s',
                artikel = '%s',
                author_name = '%s',
                author_email = '%s',
                date_start = '%s',
                date_end = '%s',
                active = '%s',
                comment = '%s',
                link = '%s',
                linktitel = '%s',
                target = '%s'
            WHERE
                id = %d",
            Database::getTablePrefix(),
            $data['date'],
            $data['lang'],
            $this->config->getDb()->escape($data['header']),
            $this->config->getDb()->escape($data['content']),
            $this->config->getDb()->escape($data['authorName']),
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $this->config->getDb()->escape($data['link']),
            $this->config->getDb()->escape($data['linkTitle']),
            $data['target'],
            $id
        );

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a news entry identified by its ID.
     *
     * @param int $id News ID
     * @return bool
     * @todo   check if there are comments attached to the deleted news
     */
    public function deleteNews($id): bool
    {
        $query = sprintf("DELETE FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'", Database::getTablePrefix(), $id, $this->config->getLanguage()->getLanguage());

        if (!$this->config->getDb()->query($query)) {
            return false;
        }

        return true;
    }
}
