<?php

namespace phpMyFAQ;

/**
 * The News class for phpMyFAQ news.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-25
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Date;
use phpMyFAQ\Db;
use phpMyFAQ\Link;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * The News class for phpMyFAQ news.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2006-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2006-06-25
 */
class News
{
    /**
     * @var Configuration
     */
    private $_config;

    /**
     * Language strings.
     *
     * @var string
     */
    private $pmf_lang;

    /**
     * Constructor.
     *
     * @param Configuration
     */
    public function __construct(Configuration $config)
    {
        global $PMF_LANG;

        $this->_config = $config;
        $this->pmf_lang = $PMF_LANG;
    }

    /**
     * Return the latest news data.
     *
     * @param bool $showArchive    Show archived news
     * @param bool $active         Show active news
     * @param bool $forceConfLimit Force to limit in configuration
     *
     * @return array
     */
    public function getLatestData($showArchive = false, $active = true, $forceConfLimit = false)
    {
        $news = [];
        $counter = 0;
        $now = date('YmdHis');

        $query = sprintf("
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
            Db::getTablePrefix(),
            $now,
            $now,
            $active ? "AND active = 'y'" : '',
            $this->_config->getLanguage()->getLanguage()
        );

        $result = $this->_config->getDb()->query($query);

        if ($this->_config->get('records.numberOfShownNewsEntries') > 0 && $this->_config->getDb()->numRows($result) > 0) {
            while (($row = $this->_config->getDb()->fetchObject($result))) {
                ++$counter;
                if (($showArchive  && ($counter > $this->_config->get('records.numberOfShownNewsEntries'))) ||
                   ((!$showArchive) && (!$forceConfLimit) &&
                   ($counter <= $this->_config->get('records.numberOfShownNewsEntries'))) ||
                   ((!$showArchive) && $forceConfLimit)) {

                    $url = sprintf('%s?action=news&amp;newsid=%d&amp;newslang=%s',
                        $this->_config->getDefaultUrl(),
                        $row->id,
                        $row->lang
                    );
                    $oLink = new Link($url, $this->_config);
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
     * Function for generating the HTML5 code for the current news.
     *
     * @param bool $showArchive Show archived news
     * @param bool $active      Show active news
     *
     * @return string
     */
    public function getNews($showArchive = false, $active = true)
    {
        $output = '';
        $news = $this->getLatestData($showArchive, $active);
        $date = new Date($this->_config);

        foreach ($news as $item) {
            $url = sprintf('%s?action=news&amp;newsid=%d&amp;newslang=%s',
                           Link::getSystemRelativeUri(),
                           $item['id'],
                           $item['lang']);
            $oLink = new Link($url, $this->_config);

            if (isset($item['header'])) {
                $oLink->itemTitle = $item['header'];
            }

            $output .= sprintf(
                '<header><h3><a name="news_%d" href="%s">%s <i aria-hidden="true" class="fa fa-caret-right"></i></a></h3></header>',
                $item['id'],
                $oLink->toString(),
                $item['header'],
                $item['header']
                );

            $output .= sprintf('%s', $item['content']);

            if (strlen($item['link']) > 1) {
                $output .= sprintf(
                    '<br />%s <a href="%s" target="_%s">%s</a>',
                    $this->pmf_lang['msgInfo'],
                    $item['link'],
                    $item['target'],
                    $item['linkTitle']);
            }

            $output .= sprintf(
                '<div class="date">%s</div>',
                $date->format($item['date'])
            );
        }

        return ('' == $output) ? $this->pmf_lang['msgNoNews'] : $output;
    }

    /**
     * Fetches all news headers.
     *
     * @return array
     */
    public function getNewsHeader()
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
                datum DESC",
            Db::getTablePrefix(),
            $this->_config->getLanguage()->getLanguage()
        );

        $result = $this->_config->getDb()->query($query);

        if ($this->_config->getDb()->numRows($result) > 0) {
            while ($row = $this->_config->getDb()->fetchObject($result)) {
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
     * @param int  $id    ID of news
     * @param bool $admin Is admin
     *
     * @return array
     */
    public function getNewsEntry($id, $admin = false)
    {
        $news = [];

        $query = sprintf(
            "SELECT
                *
            FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'",
            Db::getTablePrefix(),
            $id,
            $this->_config->getLanguage()->getLanguage());

        $result = $this->_config->getDb()->query($query);

        if ($this->_config->getDb()->numRows($result) > 0) {
            if ($row = $this->_config->getDb()->fetchObject($result)) {
                $content = $row->artikel;
                $active = ('y' == $row->active);
                $allowComments = ('y' == $row->comment);
                $expired = (date('YmdHis') > $row->date_end);

                if (!$admin) {
                    if (!$active) {
                        $content = $this->pmf_lang['err_inactiveNews'];
                    }
                    if ($expired) {
                        $content = $this->pmf_lang['err_expiredNews'];
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
                    'target' => $row->target, );
            }
        }

        return $news;
    }

    /**
     * Adds a new news entry.
     *
     * @param array $data Array with news data
     *
     * @return bool
     */
    public function addNewsEntry($data)
    {
        $query = sprintf("
            INSERT INTO
                %sfaqnews
            (id, datum, lang, header, artikel, author_name, author_email, date_start, date_end, active, comment,
            link, linktitel, target)
                VALUES
            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Db::getTablePrefix(),
            $this->_config->getDb()->nextId(Db::getTablePrefix().'faqnews', 'id'),
            $data['date'],
            $data['lang'],
            $data['header'],
            $data['content'],
            $data['authorName'],
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $data['link'],
            $data['linkTitle'],
            $data['target']);

        if (!$this->_config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Updates a new news entry identified by its ID.
     *
     * @param int   $id   News ID
     * @param array $data Array with news data
     *
     * @return bool
     */
    public function updateNewsEntry($id, Array $data)
    {
        $query = sprintf("
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
            Db::getTablePrefix(),
            $data['date'],
            $data['lang'],
            $data['header'],
            $data['content'],
            $data['authorName'],
            $data['authorEmail'],
            $data['dateStart'],
            $data['dateEnd'],
            $data['active'],
            $data['comment'],
            $data['link'],
            $data['linkTitle'],
            $data['target'],
            $id);

        if (!$this->_config->getDb()->query($query)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes a news entry identified by its ID.
     *
     * @todo check if there are comments attached to the deleted news
     *
     * @param int $id News ID
     *
     * @return bool
     */
    public function deleteNews($id)
    {
        $query = sprintf(
            "DELETE FROM
                %sfaqnews
            WHERE
                id = %d
            AND
                lang = '%s'",
            Db::getTablePrefix(),
            $id,
            $this->_config->getLanguage()->getLanguage()
        );

        if (!$this->_config->getDb()->query($query)) {
            return false;
        }

        return true;
    }
}
