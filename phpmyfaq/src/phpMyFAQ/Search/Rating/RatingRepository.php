<?php

/**
 * Rating Repository.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Search\Rating;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\Vote;
use Symfony\Component\HttpFoundation\Request;

readonly class RatingRepository implements RatingRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function fetchByRecordId(int $id): ?object
    {
        $sql = <<<SQL
                SELECT
                    (vote/usr) as voting,
                    usr
                FROM
                    %sfaqvoting
                WHERE
                    artikel = %d
            SQL;

        $query = sprintf($sql, Database::getTablePrefix(), $id);
        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            return $this->configuration->getDb()->fetchObject($result);
        }

        return null;
    }

    public function isVoteAllowed(int $id, string $ip): bool
    {
        $check = Request::createFromGlobals()->server->get('REQUEST_TIME') - 300;

        $sql = <<<SQL
                SELECT
                    id
                FROM
                    %sfaqvoting
                WHERE
                    artikel = %d
                AND
                    (ip = '%s' AND datum > '%s')
            SQL;

        $query = sprintf($sql, Database::getTablePrefix(), $id, $this->configuration->getDb()->escape($ip), $check);

        $result = $this->configuration->getDb()->query($query);
        return !$this->configuration->getDb()->numRows($result);
    }

    public function getNumberOfVotings(int $recordId): int
    {
        $sql = <<<SQL
                SELECT
                    usr
                FROM
                    %sfaqvoting
                WHERE
                    artikel = %d
            SQL;

        $query = sprintf($sql, Database::getTablePrefix(), $recordId);
        $result = $this->configuration->getDb()->query($query);

        if (!$result) {
            return 0;
        }

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            return (int) $row->usr;
        }

        return 0;
    }

    public function create(Vote $vote): bool
    {
        $sql = <<<SQL
                INSERT INTO
                    %sfaqvoting
                VALUES
                    (%d, %d, %d, 1, %d, '%s')
            SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqvoting', 'id'),
            $vote->getFaqId(),
            $vote->getVote(),
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $this->configuration->getDb()->escape($vote->getIp()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function update(Vote $vote): bool
    {
        $sql = <<<SQL
                UPDATE
                    %sfaqvoting
                SET
                    vote = vote + %d,
                    usr = usr + 1,
                    datum = %d,
                    ip = '%s'
                WHERE
                    artikel = %d
            SQL;

        $query = sprintf(
            $sql,
            Database::getTablePrefix(),
            $vote->getVote(),
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $this->configuration->getDb()->escape($vote->getIp()),
            $vote->getFaqId(),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function deleteAll(): bool
    {
        $sql = <<<SQL
                DELETE FROM
                    %sfaqvoting
            SQL;

        $query = sprintf($sql, Database::getTablePrefix());
        return (bool) $this->configuration->getDb()->query($query);
    }
}
