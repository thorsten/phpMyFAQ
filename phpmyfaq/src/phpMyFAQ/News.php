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
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-25
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Entity\NewsMessage;
use stdClass;

/**
 * Class News
 *
 * @package phpMyFAQ
 */
readonly class News
{
    /**
     * Constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Returns the current news as an array.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @return stdClass[]
     * @throws Exception
     */
    public function getAll(bool $showArchive = false, bool $active = true): array
    {
        $output = [];
        $newsData = $this->getLatestData($showArchive, $active);
        $date = new Date($this->configuration);

        foreach ($newsData as $news) {
            $entry = new stdClass();
            $url = sprintf(
                '%sindex.php?action=news&newsid=%d&newslang=%s',
                $this->configuration->getDefaultUrl(),
                $news['id'],
                $news['lang']
            );

            $link = new Link($url, $this->configuration);
            $link->itemTitle = $news['header'];

            $entry->url = $link->toString();
            $entry->header = $news['header'];
            $entry->content = strip_tags((string) $news['content']);
            $entry->date =  $date->format($news['date']);

            $output[] = $entry;
        }

        return $output;
    }

    /**
     * Return the latest news data.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active Show active news
     * @param bool $forceConfLimit Force to limit in configuration
     * @return array<int, array>
     */
    public function getLatestData(bool $showArchive = false, bool $active = true, bool $forceConfLimit = false): array
    {
        $news = [];
        $counter = 0;

        $query = sprintf(
            "SELECT * FROM %sfaqnews WHERE lang = '%s' %s ORDER BY datum DESC",
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage(),
            $active ? "AND active = 'y'" : '',
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
                        'date' => Date::createIsoDate($row->datum, DATE_ATOM),
                        'header' => $row->header,
                        'content' => $row->artikel,
                        'authorName' => $row->author_name,
                        'authorEmail' => $row->author_email,
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
     * @return array
     */
    public function getHeader(): array
    {
        $headers = [];

        $query = sprintf("
            SELECT
                id, datum, lang, header, active
            FROM
                %sfaqnews
            WHERE
                lang = '%s'
            ORDER BY
                datum DESC", Database::getTablePrefix(), $this->configuration->getLanguage()->getLanguage());

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $headers[] = [
                    'id' => $row->id,
                    'lang' => $row->lang,
                    'header' => $row->header,
                    'date' => Date::createIsoDate($row->datum),
                    'active' => $row->active
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
    public function get(int $id, bool $admin = false): array
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
            if (!$admin) {
                if (!$active) {
                    $content = Translation::get('err_inactiveNews');
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
     * @param NewsMessage $data NewsMessage object with news data
     */
    public function create(NewsMessage $data): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqnews
            (id, datum, lang, header, artikel, author_name, author_email, active, comment, link, linktitel, target)
                VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqnews', 'id'),
            $data->getCreated()->format('YmdHis'),
            $this->configuration->getDb()->escape($data->getLanguage()),
            $this->configuration->getDb()->escape($data->getHeader()),
            $this->configuration->getDb()->escape($data->getMessage()),
            $this->configuration->getDb()->escape($data->getAuthor()),
            $this->configuration->getDb()->escape($data->getEmail()),
            $data->isActive() ? 'y' : 'n',
            $data->isComment() ? 'y' : 'n',
            $this->configuration->getDb()->escape($data->getLink() ?? ''),
            $this->configuration->getDb()->escape($data->getLinkTitle() ?? ''),
            $this->configuration->getDb()->escape($data->getLinkTarget() ?? '')
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates a new news entry identified by its ID.
     *
     * @param NewsMessage $data NewsMessage object with news data
     */
    public function update(NewsMessage $data): bool
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
                active = '%s',
                comment = '%s',
                link = '%s',
                linktitel = '%s',
                target = '%s'
            WHERE
                id = %d",
            Database::getTablePrefix(),
            $data->getCreated()->format('YmdHis'),
            $this->configuration->getDb()->escape($data->getLanguage()),
            $this->configuration->getDb()->escape($data->getHeader()),
            $this->configuration->getDb()->escape($data->getMessage()),
            $this->configuration->getDb()->escape($data->getAuthor()),
            $this->configuration->getDb()->escape($data->getEmail()),
            $data->isActive() ? 'y' : 'n',
            $data->isComment() ? 'y' : 'n',
            $this->configuration->getDb()->escape($data->getLink() ?? ''),
            $this->configuration->getDb()->escape($data->getLinkTitle() ?? ''),
            $this->configuration->getDb()->escape($data->getLinkTarget() ?? ''),
            $data->getId()
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes a news entry identified by its ID.
     *
     * @param int $id News ID
     * @todo   check if there are comments attached to the deleted news
     */
    public function delete(int $id): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqnews WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($this->configuration->getLanguage()->getLanguage())
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Activates/Deactivates a news message
     *
     * @param int $id News ID
     * @param bool $status Status of activation
     */
    public function activate(int $id, bool $status): bool
    {
        $query = sprintf(
            "UPDATE %sfaqnews SET active = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $status ? 'y' : 'n',
            $id
        );

        return (bool) $this->configuration->getDb()->query($query);
    }
}
