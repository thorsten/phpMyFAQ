<?php

declare(strict_types=1);

/**
 * Class for statistics based on FAQs
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-16
 */

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use stdClass;

class Statistics
{
    /** User */
    private int $user = -1;

    /** @var int[] Groups */
    private array $groups = [-1];

    /** Flag for Group support. */
    private bool $groupSupport = false;

    /** Plural form support. */
    private readonly Plurals $plurals;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->plurals = new Plurals();

        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $this->groupSupport = true;
        }
    }

    /**
     * Returns the number of activated and not expired FAQs, optionally
     * not limited to the current language.
     *
     * @param string|null $language Language
     */
    public function totalFaqs(?string $language = null): int
    {
        $now = date(format: 'YmdHis');

        $query = sprintf(
            "SELECT id FROM %sfaqdata WHERE active = 'yes' %s AND date_start <= '%s' AND date_end >= '%s'",
            Database::getTablePrefix(),
            null === $language ? '' : "AND lang = '" . $this->configuration->getDb()->escape($language) . "'",
            $now,
            $now,
        );

        $num = $this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));

        if ($num > 0) {
            return $num;
        }

        return 0;
    }

    /**
     * This function generates the list with the latest published records.
     */
    public function getLatest(): array
    {
        $date = new Date($this->configuration);
        $result = $this->getLatestData(PMF_NUMBER_RECORDS_LATEST, $this->configuration->getLanguage()->getLanguage());
        $output = [];

        foreach ($result as $row) {
            $entry = new stdClass();
            $entry->url = $row['url'];
            $entry->title = Utils::makeShorterText($row['question'], 8);
            $entry->preview = $row['question'];
            $entry->date = $date->format($row['date']);
            $output[] = $entry;
        }

        return $output;
    }

    /**
     * This function generates a list with the most voted or most visited records.
     *
     * @param string $type Type definition visits/voted
     */
    public function getTopTen(string $type = 'visits'): array
    {
        if ('visits' === $type) {
            $result = $this->getTopTenData(
                PMF_NUMBER_RECORDS_TOPTEN,
                0,
                $this->configuration->getLanguage()->getLanguage(),
            );
        } else {
            $result = $this->getTopVotedData(
                PMF_NUMBER_RECORDS_TOPTEN,
                $this->configuration->getLanguage()->getLanguage(),
            );
        }

        $output = [];

        foreach ($result as $row) {
            $entry = new stdClass();
            $entry->title = Utils::makeShorterText($row['question'], 8);
            $entry->preview = $row['question'];
            $entry->url = $row['url'];
            if ('visits' === $type) {
                $entry->visits = $this->plurals->GetMsg('plmsgViews', $row['visits']);
            } else {
                $entry->voted = sprintf(
                    '%s %s 5 - %s',
                    round($row['avg'], 2),
                    Translation::get(key: 'msgVoteFrom'),
                    $this->plurals->GetMsg('plmsgVotes', $row['user']),
                );
            }

            $output[] = $entry;
        }

        return $output;
    }

    /**
     * This function generates the list with the most trending FAQs.
     *
     * @return stdClass[]
     */
    public function getTrending(): array
    {
        $date = new Date($this->configuration);
        $result = $this->getTrendingData(
            PMF_NUMBER_RECORDS_TRENDING,
            $this->configuration->getLanguage()->getLanguage(),
        );
        $output = [];

        foreach ($result as $row) {
            $entry = new stdClass();
            $entry->url = $row['url'];
            $entry->title = Utils::makeShorterText($row['question'], 8);
            $entry->preview = $row['question'];
            $entry->visits = $this->plurals->GetMsg('plmsgViews', $row['visits']);
            $entry->date = $date->format($row['date']);
            $output[] = $entry;
        }

        return $output;
    }

    /**
     * This function generates an array with a specified number of most recent
     * published records.
     *
     * @param int         $count Number of records
     * @param string|null $language Language
     */
    public function getLatestData(int $count = PMF_NUMBER_RECORDS_LATEST, ?string $language = null): array
    {
        global $sids;

        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query =
            '
            SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.thema AS question,
                fd.content AS content,
                fd.updated AS updated,
                fv.visits AS visits,
                fdg.group_id AS group_id,
                fdu.user_id AS user_id
            FROM
                '
            . Database::getTablePrefix()
            . 'faqvisits fv,
                '
            . Database::getTablePrefix()
            . 'faqdata fd
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''
            . $now
            . '\'
                AND fd.date_end   >= \''
            . $now
            . '\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .=
            '
                ' . $queryHelper->queryPermission($this->groupSupport) . '
            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fdg.group_id,fdu.user_id
            ORDER BY
                fd.updated DESC';

        $result = $this->configuration->getDb()->query($query);
        $latest = [];
        $data = [];

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }

                    if (!in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } elseif (!in_array($row->user_id, [-1, $this->user])) {
                    continue;
                }

                $data['date'] = Date::createIsoDate($row->updated, DATE_ATOM);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->content;
                $data['visits'] = (int) $row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang,
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $latest[$row->id] = $data;

                if (count($latest) === $count) {
                    break;
                }
            }
        }

        return $latest;
    }

    /**
     * This function generates the Trending data with the most visited records.
     *
     * @param int         $count Number of records
     * @param string|null $language Language
     */
    public function getTrendingData(int $count = PMF_NUMBER_RECORDS_TRENDING, ?string $language = null): array
    {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query =
            '
            SELECT
                fd.id AS id,
                fd.lang AS language,
                fcr.category_id AS category_id,
                fd.thema AS question,
                fd.content AS content,
                fd.created AS created,
                fv.visits AS visits,
                fdg.group_id AS group_id,
                fdu.user_id AS user_id
            FROM
                '
            . Database::getTablePrefix()
            . 'faqvisits fv,
                '
            . Database::getTablePrefix()
            . 'faqdata fd
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''
            . $now
            . '\'
                AND fd.date_end   >= \''
            . $now
            . '\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .=
            '
                '
            . $queryHelper->queryPermission($this->groupSupport)
            . '
            GROUP BY
                fd.id, fd.lang, fd.created, fcr.category_id, fv.visits, fdg.group_id, fdu.user_id
            ORDER BY
                fd.created DESC, fv.visits DESC';

        $result = $this->configuration->getDb()->query($query);
        $trending = [];
        $data = [];

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }

                    if (!in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } elseif (!in_array($row->user_id, [-1, $this->user])) {
                    continue;
                }

                $data['date'] = Filter::filterVar($row->created, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->content;
                $data['visits'] = (int) $row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->language,
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $trending[$row->id] = $data;

                if (count($trending) === $count) {
                    break;
                }
            }
        }

        return $trending;
    }

    /**
     * This function generates the Top Ten data with the most viewed records.
     *
     * @param int  $count Number of records
     * @param int  $categoryId Entity ID
     * @param string|null $language Language
     */
    public function getTopTenData(
        int $count = PMF_NUMBER_RECORDS_TOPTEN,
        int $categoryId = 0,
        ?string $language = null,
    ): array {
        global $sids;

        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query =
            'SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS question,
                fd.content AS answer,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                fv.visits AS visits,
                fv.last_visit AS last_visit,
                fdg.group_id AS group_id,
                fdu.user_id AS user_id
            FROM
                '
            . Database::getTablePrefix()
            . 'faqvisits fv,
                '
            . Database::getTablePrefix()
            . 'faqdata fd
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                '
            . Database::getTablePrefix()
            . 'faqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                    fd.date_start <= \''
            . $now
            . '\'
                AND fd.date_end   >= \''
            . $now
            . '\'
                AND fd.id = fv.id
                AND fd.lang = fv.lang
                AND fd.active = \'yes\'';

        if ($categoryId != 0) {
            $query .= '
            AND
                fcr.category_id = \'' . $categoryId . "'";
        }

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .=
            '
                ' . $queryHelper->queryPermission($this->groupSupport) . '

            GROUP BY
                fd.id,fd.lang,fcr.category_id,fv.visits,fv.last_visit,fdg.group_id,fdu.user_id
            ORDER BY
                fv.visits DESC';

        $result = $this->configuration->getDb()->query($query);
        $topTen = [];
        $data = [];

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                if ($this->groupSupport) {
                    if (!in_array($row->user_id, [-1, $this->user])) {
                        continue;
                    }

                    if (!in_array($row->group_id, $this->groups)) {
                        continue;
                    }
                } elseif (!in_array($row->user_id, [-1, $this->user])) {
                    continue;
                }

                $data['visits'] = (int) $row->visits;
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->answer;
                $data['date'] = Date::createIsoDate($row->updated, DATE_ATOM);
                $data['last_visit'] = date(
                    format: 'c',
                    timestamp: (int) $row->last_visit,
                );

                $title = $row->question;
                $url = sprintf(
                    '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang,
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($row->question);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topTen[$row->id] = $data;

                if (count($topTen) === $count) {
                    break;
                }
            }

            array_multisort($topTen, SORT_DESC);
        }

        return $topTen;
    }

    /**
     * This function generates data-set with the most voted FAQs.
     *
     * @param int         $count    Number of records
     * @param string|null $language Language
     */
    public function getTopVotedData(int $count = PMF_NUMBER_RECORDS_TOPTEN, ?string $language = null): array
    {
        global $sids;
        $topten = [];
        $data = [];

        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $query = sprintf(
            "SELECT
                fd.id AS id,
                fd.lang AS lang,
                fd.thema AS thema,
                fd.updated AS updated,
                fcr.category_id AS category_id,
                (fv.vote/fv.usr) AS avg,
                fv.usr AS user
            FROM
                %sfaqvoting fv,
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            LEFT JOIN
                %sfaqdata_group AS fdg
            ON
                fd.id = fdg.record_id
            LEFT JOIN
                %sfaqdata_user AS fdu
            ON
                fd.id = fdu.record_id
            WHERE
                fd.date_start <= '%s'
            AND fd.date_end   >= '%s'
            AND fd.id = fv.artikel
            AND fd.active = 'yes'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $now,
            $now,
        );

        if (isset($categoryId) && is_numeric($categoryId) && $categoryId != 0) {
            $query .= '
            AND
                fcr.category_id = \'' . $categoryId . "'";
        }

        if (isset($language) && Language::isASupportedLanguage($language)) {
            $query .= '
            AND
                fd.lang = \'' . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .= '
                ' . $queryHelper->queryPermission($this->groupSupport) . '
            ORDER BY
                avg DESC';

        $result = $this->configuration->getDb()->query($query);

        $i = 1;
        $oldId = 0;
        while (($row = $this->configuration->getDb()->fetchObject($result)) && $i <= $count) {
            if ($oldId != $row->id) {
                $data['avg'] = $row->avg;
                $data['question'] = $row->thema;
                $data['date'] = $row->updated;
                $data['user'] = $row->user;

                $title = $row->thema;
                $url = sprintf(
                    '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $sids,
                    $row->category_id,
                    $row->id,
                    $row->lang,
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($row->thema);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topten[] = $data;
                ++$i;
            }

            $oldId = $row->id;
        }

        return $topten;
    }

    public function setUser(int $userId = -1): Statistics
    {
        $this->user = $userId;
        return $this;
    }

    /**
     * @param int[] $groups
     */
    public function setGroups(array $groups): Statistics
    {
        $this->groups = $groups;
        return $this;
    }
}
