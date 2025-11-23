<?php

/**
 * Visits repository class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Visits;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Repository for faqvisits table access.
 */
final readonly class VisitsRepository implements VisitsRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function getVisitCount(int $faqId, string $language): int
    {
        $query = sprintf(
            "SELECT visits FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) !== 0) {
            $row = $this->configuration->getDb()->fetchObject($result);
            return (int) $row->visits;
        }

        return 0;
    }

    public function exists(int $faqId, string $language): bool
    {
        $query = sprintf(
            "SELECT COUNT(*) AS cnt FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchObject($result);
            return (int) ($row->cnt ?? 0) > 0;
        }

        return false;
    }

    public function insert(int $faqId, string $language, int $timestamp): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqvisits VALUES (%d, '%s', %d, %d)",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
            1,
            $timestamp,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function update(int $faqId, string $language, int $timestamp): bool
    {
        $query = sprintf(
            "UPDATE %sfaqvisits SET visits = visits+1, last_visit = %d WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $timestamp,
            $faqId,
            $this->configuration->getDb()->escape($language),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function getAll(): array
    {
        $data = [];

        $query = sprintf('SELECT * FROM %sfaqvisits ORDER BY visits DESC', Database::getTablePrefix());
        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            $data[] = [
                'id' => $row->id,
                'lang' => $row->lang,
                'visits' => $row->visits,
                'last_visit' => $row->last_visit,
            ];
        }

        return $data;
    }

    public function resetAll(int $timestamp): bool
    {
        return (bool) $this->configuration
            ->getDb()
            ->query(sprintf(
                'UPDATE %sfaqvisits SET visits = 1, last_visit = %d ',
                Database::getTablePrefix(),
                $timestamp,
            ));
    }
}
