<?php

/**
 * Class for statistics based on FAQs
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-16
 */

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
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
        $result = $this->getTopVotedData(PMF_NUMBER_RECORDS_TOPTEN, $this->configuration->getLanguage()->getLanguage());
        if ('visits' === $type) {
            $result = $this->getTopTenData(
                PMF_NUMBER_RECORDS_TOPTEN,
                0,
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
                $entry->visits = $this->plurals->get(key: 'plmsgViews', number: $row['visits']);
            }

            if ('visits' !== $type) {
                $entry->voted = sprintf(
                    '%s %s 5 - %s',
                    round(num: $row['avg'], precision: 2),
                    Translation::get(key: 'msgVoteFrom'),
                    $this->plurals->get(key: 'plmsgVotes', number: $row['user']),
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
            $entry->visits = $this->plurals->get(key: 'plmsgViews', number: $row['visits']);
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
        $query = $this->buildStatisticsQuery(
            selectList: 'fd.id AS id, fd.lang AS lang, fd.thema AS question, fd.content AS content, '
            . 'fd.updated AS updated, fv.visits AS visits',
            sourceTable: 'faqvisits',
            joinCondition: 'fd.id = fv.id AND fd.lang = fv.lang',
            language: $language,
            orderBy: 'fd.updated DESC',
        );

        $result = $this->configuration->getDb()->query($query, 0, $count);
        $latest = [];
        $data = [];

        if ($result) {
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if ($row === false || $row === null || $row === []) {
                    break;
                }

                $data['date'] = Date::createIsoDate($row->updated, DATE_ATOM);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->content;
                $data['visits'] = (int) $row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->lang,
                    TitleSlugifier::slug($title),
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $latest[$row->id] = $data;
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
        $query = $this->buildStatisticsQuery(
            selectList: 'fd.id AS id, fd.lang AS language, fd.thema AS question, fd.content AS content, '
            . 'fd.created AS created, fv.visits AS visits',
            sourceTable: 'faqvisits',
            joinCondition: 'fd.id = fv.id AND fd.lang = fv.lang',
            language: $language,
            orderBy: 'fd.created DESC, fv.visits DESC',
        );

        $result = $this->configuration->getDb()->query($query, 0, $count);
        $trending = [];
        $data = [];

        if ($result) {
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);
                if ($row === false || $row === null || $row === []) {
                    break;
                }

                $data['date'] = Filter::filterVar($row->created, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->content;
                $data['visits'] = (int) $row->visits;

                $title = $row->question;
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->language,
                    TitleSlugifier::slug($row->question),
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($title);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $trending[$row->id] = $data;
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
        $query = $this->buildStatisticsQuery(
            selectList: 'fd.id AS id, fd.lang AS lang, fd.thema AS question, fd.content AS answer, '
            . 'fd.updated AS updated, fv.visits AS visits, fv.last_visit AS last_visit',
            sourceTable: 'faqvisits',
            joinCondition: 'fd.id = fv.id AND fd.lang = fv.lang',
            language: $language,
            orderBy: 'fv.visits DESC',
            categoryId: $categoryId,
        );

        $result = $this->configuration->getDb()->query($query, 0, $count);
        $topTen = [];
        $data = [];

        if ($result) {
            while (true) {
                $row = $this->configuration->getDb()->fetchObject($result);

                if ($row === false || $row === null || $row === []) {
                    break;
                }

                $data['visits'] = (int) $row->visits;
                $data['question'] = Filter::filterVar($row->question, FILTER_SANITIZE_SPECIAL_CHARS);
                $data['answer'] = $row->answer;
                $data['date'] = Date::createIsoDate($row->updated, DATE_ATOM);
                $data['last_visit'] = date(format: 'c', timestamp: (int) $row->last_visit);

                $title = $row->question;
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->lang,
                    TitleSlugifier::slug($row->question),
                );
                $oLink = new Link($url, $this->configuration);
                $oLink->setTitle($row->question);
                $oLink->tooltip = $title;
                $data['url'] = $oLink->toString();

                $topTen[$row->id] = $data;
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
        $topten = [];
        $data = [];

        $query = $this->buildStatisticsQuery(
            selectList: 'fd.id AS id, fd.lang AS lang, fd.thema AS thema, fd.updated AS updated, '
            . '(fv.vote/fv.usr) AS avg, fv.usr AS user',
            sourceTable: 'faqvoting',
            joinCondition: 'fd.id = fv.artikel',
            language: $language,
            orderBy: 'avg DESC',
        );

        $result = $this->configuration->getDb()->query($query, 0, $count);

        $i = 1;
        $oldId = 0;
        while (true) {
            $row = $this->configuration->getDb()->fetchObject($result);
            if ($row === false || $row === null || $i > $count) {
                break;
            }

            if ($oldId !== $row->id) {
                $data['avg'] = $row->avg;
                $data['question'] = $row->thema;
                $data['date'] = $row->updated;
                $data['user'] = $row->user;

                $title = $row->thema;
                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $row->category_id,
                    $row->id,
                    $row->lang,
                    TitleSlugifier::slug($row->thema),
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

    /**
     * Builds the shared statistics query: active FAQs within their publication window,
     * joined against a per-FAQ source table (visits or votes), permission-filtered via
     * EXISTS subqueries and enriched with a deterministic category id. Every FAQ maps
     * to exactly one result row, so callers can rely on the driver-level LIMIT.
     */
    private function buildStatisticsQuery(
        string $selectList,
        string $sourceTable,
        string $joinCondition,
        ?string $language,
        string $orderBy,
        int $categoryId = 0,
    ): string {
        $now = date(format: 'YmdHis');
        $queryHelper = new QueryHelper($this->user, $this->groups);
        $prefix = Database::getTablePrefix();

        $categoryFilter = $categoryId !== 0 ? sprintf(' AND fcr.category_id = %d', $categoryId) : '';

        $query = sprintf(
            'SELECT %s, (SELECT MIN(fcr.category_id) FROM %sfaqcategoryrelations fcr '
            . 'WHERE fcr.record_id = fd.id AND fcr.record_lang = fd.lang%s) AS category_id '
            . 'FROM %sfaqdata fd, %s%s fv '
            . "WHERE %s AND fd.active = 'yes' AND fd.date_start <= '%s' AND fd.date_end >= '%s'",
            $selectList,
            $prefix,
            $categoryFilter,
            $prefix,
            $prefix,
            $sourceTable,
            $joinCondition,
            $now,
            $now,
        );

        if ($categoryId !== 0) {
            $query .= sprintf(
                ' AND EXISTS (SELECT 1 FROM %sfaqcategoryrelations fcr '
                . 'WHERE fcr.record_id = fd.id AND fcr.record_lang = fd.lang AND fcr.category_id = %d)',
                $prefix,
                $categoryId,
            );
        }

        if ($language !== null && Language::isASupportedLanguage($language)) {
            $query .= sprintf(" AND fd.lang = '%s'", $this->configuration->getDb()->escape($language));
        }

        return $query . ' ' . $queryHelper->queryPermissionExistsAll($this->groupSupport) . ' ORDER BY ' . $orderBy;
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
