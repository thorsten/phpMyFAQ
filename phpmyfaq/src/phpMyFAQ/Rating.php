<?php

/**
 * The main Rating class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2007-03-31
 */

namespace phpMyFAQ;

use phpMyFAQ\Language\Plurals;

/**
 * Class Rating
 *
 * @package phpMyFAQ
 */
readonly class Rating
{
    /**
     * Plural form support.
     */
    private Plurals $plr;

    /**
     * Constructor.
     */
    public function __construct(private Configuration $configuration)
    {
        $this->plr = new Plurals();
    }

    /**
     * Returns all ratings of FAQ records.
     *
     * @return array<array<mixed>>
     */
    public function getAllRatings(): array
    {
        $ratings = [];

        $query = match (Database::getType()) {
            'mssql', 'sqlsrv' => sprintf(
                '
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        CAST(fd.thema as char(2000)) AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        CAST(fd.thema as char(2000)),
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix()
            ),
            default => sprintf(
                '
                    SELECT
                        fd.id AS id,
                        fd.lang AS lang,
                        fcr.category_id AS category_id,
                        fd.thema AS question,
                        (fv.vote / fv.usr) AS num,
                        fv.usr AS usr
                    FROM
                        %sfaqvoting fv,
                        %sfaqdata fd
                    LEFT JOIN
                        %sfaqcategoryrelations fcr
                    ON
                        fd.id = fcr.record_id
                    AND
                        fd.lang = fcr.record_lang
                    WHERE
                        fd.id = fv.artikel
                    GROUP BY
                        fd.id,
                        fd.lang,
                        fd.active,
                        fcr.category_id,
                        fd.thema,
                        fv.vote,
                        fv.usr
                    ORDER BY
                        fcr.category_id',
                Database::getTablePrefix(),
                Database::getTablePrefix(),
                Database::getTablePrefix()
            ),
        };

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $question = Strings::htmlspecialchars(trim((string) $row->question));
            $url = sprintf(
                '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                $this->configuration->getDefaultUrl(),
                $row->category_id,
                $row->id,
                $row->lang
            );

            $link = new Link($url, $this->configuration);
            $link->itemTitle = $question;

            $ratings[] = [
                'id' => $row->id,
                'lang' => $row->lang,
                'category_id' => $row->category_id,
                'question' => $question,
                'url' => $link->toString(),
                'number' => $row->num,
                'user' => $row->usr
            ];
        }

        return $ratings;
    }

    /**
     * Calculates the rating of the user voting.
     */
    public function getVotingResult(int $id): string
    {
        $query = sprintf(
            'SELECT (vote/usr) as voting, usr FROM %sfaqvoting WHERE artikel = %d',
            Database::getTablePrefix(),
            $id
        );
        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);

            return sprintf(
                ' <span data-rating="%s">%s</span> (' . $this->plr->GetMsg('plmsgVotes', $row->usr) . ')',
                round($row->voting, 2),
                round($row->voting, 2)
            );
        }
        return ' <span data-rating="0">0</span> (' . $this->plr->GetMsg('plmsgVotes', 0) . ')';
    }

    /**
     * Reload locking for user voting.
     *
     * @param int    $id FAQ record id
     * @param string $ip IP
     */
    public function check(int $id, string $ip): bool
    {
        $check = $_SERVER['REQUEST_TIME'] - 300;
        $query = sprintf(
            "SELECT id FROM %sfaqvoting WHERE artikel = %d AND (ip = '%s' AND datum > '%s')",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($ip),
            $check
        );
        return !$this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));
    }

    /**
     * Returns the number of users from the table "faqvotings".
     */
    public function getNumberOfVotings(int $recordId): int
    {
        $query = sprintf(
            'SELECT usr FROM %sfaqvoting WHERE artikel = %d',
            Database::getTablePrefix(),
            $recordId
        );
        if (!($result = $this->configuration->getDb()->query($query))) {
            return 0;
        }
        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            return $row->usr;
        }

        return 0;
    }

    /**
     * Adds a new voting record.
     *
     * @param int[] $votingData
     */
    public function addVoting(array $votingData): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqvoting VALUES (%d, %d, %d, 1, %d, '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqvoting', 'id'),
            $votingData['record_id'],
            $votingData['vote'],
            $_SERVER['REQUEST_TIME'],
            $votingData['user_ip']
        );
        $this->configuration->getDb()->query($query);

        return true;
    }

    /**
     * Updates an existing voting record.
     *
     * @param int[] $votingData
     */
    public function update(array $votingData): bool
    {
        $query = sprintf(
            "UPDATE %sfaqvoting SET vote = vote + %d, usr = usr + 1, datum = %d, ip = '%s' WHERE artikel = %d",
            Database::getTablePrefix(),
            $votingData['vote'],
            $_SERVER['REQUEST_TIME'],
            $votingData['user_ip'],
            $votingData['record_id']
        );
        $this->configuration->getDb()->query($query);

        return true;
    }

    /**
     * Deletes all votes.
     */
    public function deleteAll(): bool
    {
        return $this->configuration->getDb()->query(
            sprintf('DELETE FROM %sfaqvoting', Database::getTablePrefix())
        );
    }
}
