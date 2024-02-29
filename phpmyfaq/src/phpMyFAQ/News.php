<?php

/**
 * The News class for phpMyFAQ news.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
     * Constructor.
     */
    public function __construct(private readonly Configuration $configuration)
    {
    }

    /**
     * Function for generating the HTML5 code for the current news.
     *
     * @todo move method to a helper class
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @throws Exception
     */
    public function getNews(bool $showArchive = false, bool $active = true): string
    {
        $output = '';
        $news = $this->getLatestData($showArchive, $active);
        $date = new Date($this->configuration);

        foreach ($news as $new) {
            $url = sprintf(
                '%sindex.php?action=news&amp;newsid=%d&amp;newslang=%s',
                $this->configuration->getDefaultUrl(),
                $new['id'],
                $new['lang']
            );

            $oLink = new Link($url, $this->configuration);

            if (isset($new['header'])) {
                $oLink->itemTitle = Strings::htmlentities($new['header']);
            }

            $output .= sprintf(
                '<h5%s><a id="news_%d" href="%s">%s</a> <i aria-hidden="true" class="bi bi-caret-right"></i></h6>',
                ' class="mt-3 pmf-news-heading"',
                $new['id'],
                Strings::htmlentities($oLink->toString()),
                Strings::htmlentities($new['header'])
            );

            $output .= strip_tags((string) $new['content']);

            if (strlen((string) $new['link']) > 1) {
                $output .= sprintf(
                    '<br>%s <a href="%s" target="_%s">%s</a>',
                    Translation::get('msgInfo'),
                    Strings::htmlentities($new['link']),
                    $new['target'],
                    Strings::htmlentities($new['linkTitle'])
                );
            }

            $output .= sprintf('<small class="text-muted ms-1">%s</small>', $date->format($new['date']));
        }

        return ('' == $output) ? Translation::get('msgNoNews') : $output;
    }

    /**
     * Return the latest news data.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @param bool $forceConfLimit Force to limit in configuration
     * @return array<int, array<mixed>>
     */
    public function getLatestData(bool $showArchive = false, bool $active = true, bool $forceConfLimit = false): array
    {
        $news = [];
        $counter = 0;
        $now = date('YmdHis');

        $query = sprintf(
            "SELECT
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
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        $numberOfShownNewsEntries = $this->configuration->get('records.numberOfShownNewsEntries');
        if ($numberOfShownNewsEntries > 0 && $this->configuration->getDb()->numRows($result) > 0) {
            while (($row = $this->configuration->getDb()->fetchObject($result))) {
                ++$counter;
                if (
                    ($showArchive && ($counter > $numberOfShownNewsEntries)) ||
                    ((!$showArchive) && (!$forceConfLimit) && ($counter <= $numberOfShownNewsEntries)) ||
                    ((!$showArchive) && $forceConfLimit)
                ) {
                    $url = sprintf(
                        '%sindex.php?action=news&amp;newsid=%d&amp;newslang=%s',
                        $this->configuration->getDefaultUrl(),
                        $row->id,
                        $row->lang
                    );
                    $oLink = new Link($url, $this->configuration);
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
                datum DESC", Database::getTablePrefix(), $this->configuration->getLanguage()->getLanguage());

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $expired = ($now > $row->date_end);
                $headers[] = [
                    'id' => $row->id,
                    'lang' => $row->lang,
                    'header' => $row->header,
                    'date' => Date::createIsoDate($row->datum),
                    'active' => $row->active,
                    'expired' => $expired
                ];
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
    public function getNewsEntry(int $id, bool $admin = false): array
    {
        $news = [];

        $query = sprintf(
            "SELECT * FROM %sfaqnews WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage())
        );

        $result = $this->configuration->getDb()->query($query);

        if (
            $this->configuration->getDb()->numRows($result) > 0 &&
            ($row = $this->configuration->getDb()->fetchObject($result))
        ) {
            $content = $row->artikel;
            $active = ('y' == $row->active);
            $allowComments = ('y' == $row->comment);
            $expired = (date('YmdHis') > $row->date_end);
            if (!$admin) {
                if (!$active) {
                    $content = Translation::get('err_inactiveNews');
                }

                if ($expired) {
                    $content = Translation::get('err_expiredNews');
                }
            }
            $news = [
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
                'target' => $row->target
            ];
        }

        return $news;
    }

    /**
     * Adds a new news entry.
     *
     * @param array<mixed> $data Array with news data
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
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqnews', 'id'),
            $data['date'],
            $data['lang'],
            $this->configuration->getDb()->escape($data['header']),
            $this->configuration->getDb()->escape($data['content']),
            $this->configuration->getDb()->escape($data['authorName']),
            $this->configuration->getDb()->escape($data['authorEmail']),
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $this->configuration->getDb()->escape($data['link']),
            $this->configuration->getDb()->escape($data['linkTitle']),
            $data['target']
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates a new news entry identified by its ID.
     *
     * @param int          $id News ID
     * @param array<mixed> $data Array with news data
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
            $this->configuration->getDb()->escape($data['header']),
            $this->configuration->getDb()->escape($data['content']),
            $this->configuration->getDb()->escape($data['authorName']),
            $this->configuration->getDb()->escape($data['authorEmail']),
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $this->configuration->getDb()->escape($data['link']),
            $this->configuration->getDb()->escape($data['linkTitle']),
            $data['target'],
            $id
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a news entry identified by its ID.
     *
     * @param int $id News ID
     * @todo   check if there are comments attached to the deleted news
     */
    public function deleteNews(int $id): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqnews WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage())
        );

        return (bool) $this->configuration->getDb()->query($query);
    }
}
