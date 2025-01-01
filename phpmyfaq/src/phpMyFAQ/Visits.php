<?php

/**
 * Handles all the stuff for visits.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2009-03-08
 */

namespace phpMyFAQ;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class Visits
 *
 * @package phpMyFAQ
 */
readonly class Visits
{
    /**
     * Constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Counting the views of a FAQ record.
     *
     * @param int $faqId FAQ record ID
     */
    public function logViews(int $faqId): void
    {
        $nVisits = 0;
        $query = sprintf(
            "SELECT visits FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getLanguage()->getLanguage()
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result)) {
            $row = $this->configuration->getDb()->fetchObject($result);
            $nVisits = $row->visits;
        }

        if ($nVisits === 0) {
            $this->add($faqId);
            return;
        }

        $this->update($faqId);
    }

    /**
     * Adds a new entry in the table "faqvisits".
     *
     * @param int $faqId Record ID
     */
    public function add(int $faqId): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqvisits VALUES (%d, '%s', %d, %d)",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getLanguage()->getLanguage(),
            1,
            Request::createFromGlobals()->server->get('REQUEST_TIME')
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Updates an entry in the table "faqvisits".
     *
     * @param int $faqId FAQ record ID
     */
    private function update(int $faqId): void
    {
        $query = sprintf(
            "UPDATE %sfaqvisits SET visits = visits+1, last_visit = %d WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $faqId,
            $this->configuration->getLanguage()->getLanguage()
        );

        $this->configuration->getDb()->query($query);
    }

    /**
     * Get all the entries from the table "faqvisits".
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllData(): array
    {
        $data = [];

        $query = sprintf(
            'SELECT * FROM %sfaqvisits ORDER BY visits DESC',
            Database::getTablePrefix()
        );
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

    /**
     * Resets all visits to the current date and one visit per FAQ.
     */
    public function resetAll(): bool
    {
        return (bool) $this->configuration->getDb()->query(
            sprintf(
                'UPDATE %sfaqvisits SET visits = 1, last_visit = %d ',
                Database::getTablePrefix(),
                Request::createFromGlobals()->server->get('REQUEST_TIME')
            )
        );
    }
}
