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
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link  https://www.phpmyfaq.de
 * @since 2009-03-08
 */

namespace phpMyFAQ;

/**
 * Class Visits
 *
 * @package phpMyFAQ
 */
class Visits
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Counting the views of a FAQ record.
     *
     * @param int $id FAQ record ID
     */
    public function logViews(int $id): void
    {
        $nVisits = 0;
        $query = sprintf(
            "SELECT visits FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        if ($this->config->getDb()->numRows($result)) {
            $row = $this->config->getDb()->fetchObject($result);
            $nVisits = $row->visits;
        }
        if ($nVisits === 0) {
            $this->add($id);
        } else {
            $this->update($id);
        }
    }

    /**
     * Adds a new entry in the table "faqvisits".
     *
     * @param int $id Record ID
     */
    public function add(int $id): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqvisits VALUES (%d, '%s', %d, %d)",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage(),
            1,
            $_SERVER['REQUEST_TIME']
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Updates an entry in the table "faqvisits".
     *
     * @param int $id FAQ record ID
     */
    private function update(int $id): bool
    {
        $query = sprintf(
            "UPDATE %sfaqvisits SET visits = visits+1, last_visit = %d WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $_SERVER['REQUEST_TIME'],
            $id,
            $this->config->getLanguage()->getLanguage()
        );

        return (bool) $this->config->getDb()->query($query);
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
        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchObject($result)) {
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
     * Resets all visits to current date and one visit per FAQ.
     */
    public function resetAll(): bool
    {
        return (bool) $this->config->getDb()->query(
            sprintf(
                'UPDATE %sfaqvisits SET visits = 1, last_visit = %d ',
                Database::getTablePrefix(),
                $_SERVER['REQUEST_TIME']
            )
        );
    }
}
