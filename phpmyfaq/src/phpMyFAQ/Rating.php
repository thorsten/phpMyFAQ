<?php

declare(strict_types=1);

/**
 * The main Rating class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2007-03-31
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Language\Plurals;
use Symfony\Component\HttpFoundation\Request;

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
    private Plurals $plurals;

    /**
     * Constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
        $this->plurals = new Plurals();
    }

    /**
     * Calculates the rating of the user voting.
     */
    public function get(int $id): string
    {
        $query = sprintf(
            'SELECT (vote/usr) as voting, usr FROM %sfaqvoting WHERE artikel = %d',
            Database::getTablePrefix(),
            $id,
        );
        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            $row = $this->configuration->getDb()->fetchObject($result);

            return sprintf(
                ' <span data-rating="%s">%s</span> (' . $this->plurals->GetMsg('plmsgVotes', (int) $row->usr) . ')',
                round((int) $row->voting, 2),
                round((int) $row->voting, 2),
            );
        }

        return ' <span data-rating="0">0</span> (' . $this->plurals->GetMsg('plmsgVotes', 0) . ')';
    }

    /**
     * Reload locking for user voting.
     *
     * @param int    $id FAQ record id
     * @param string $ip IP
     */
    public function check(int $id, string $ip): bool
    {
        $check = Request::createFromGlobals()->server->get('REQUEST_TIME') - 300;
        $query = sprintf(
            "SELECT id FROM %sfaqvoting WHERE artikel = %d AND (ip = '%s' AND datum > '%s')",
            Database::getTablePrefix(),
            $id,
            $this->configuration->getDb()->escape($ip),
            $check,
        );
        return !$this->configuration->getDb()->numRows($this->configuration->getDb()->query($query));
    }

    /**
     * Returns the number of users from the table "faqvotings".
     */
    public function getNumberOfVotings(int $recordId): int
    {
        $query = sprintf('SELECT usr FROM %sfaqvoting WHERE artikel = %d', Database::getTablePrefix(), $recordId);
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
     */
    public function create(Vote $vote): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqvoting VALUES (%d, %d, %d, 1, %d, '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqvoting', 'id'),
            $vote->getFaqId(),
            $vote->getVote(),
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $this->configuration->getDb()->escape($vote->getIp()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates an existing voting record.
     */
    public function update(Vote $vote): bool
    {
        $query = sprintf(
            "UPDATE %sfaqvoting SET vote = vote + %d, usr = usr + 1, datum = %d, ip = '%s' WHERE artikel = %d",
            Database::getTablePrefix(),
            $vote->getVote(),
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $this->configuration->getDb()->escape($vote->getIp()),
            $vote->getFaqId(),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Deletes all votes.
     */
    public function deleteAll(): bool
    {
        return (bool) $this->configuration
            ->getDb()
            ->query(sprintf('DELETE FROM %sfaqvoting', Database::getTablePrefix()));
    }
}
