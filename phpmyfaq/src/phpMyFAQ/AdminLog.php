<?php

/**
 * The main Logging class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-08-15
 */

namespace phpMyFAQ;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class Logging
 *
 * @package phpMyFAQ
 */
class AdminLog
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Returns the number of entries.
     */
    public function getNumberOfEntries(): int
    {
        $query = sprintf('SELECT id FROM %sfaqadminlog', Database::getTablePrefix());

        return $this->config->getDb()->numRows(
            $this->config->getDb()->query($query)
        );
    }

    /**
     * Returns all data from the admin log.
     */
    public function getAll(): array
    {
        $data = [];

        $query = sprintf(
            '
            SELECT
                id, time, usr, text, ip
            FROM
                %sfaqadminlog
            ORDER BY id DESC',
            Database::getTablePrefix()
        );

        $result = $this->config->getDb()->query($query);

        while ($row = $this->config->getDb()->fetchObject($result)) {
            $data[$row->id] = ['time' => $row->time, 'usr' => $row->usr, 'text' => $row->text, 'ip' => $row->ip];
        }

        return $data;
    }

    /**
     * Adds a new admin log entry.
     *
     * @param User   $user    User object
     * @param string $logText Logged string
     */
    public function log(User $user, string $logText = ''): bool
    {
        if ($this->config->get('main.enableAdminLog')) {
            $query = sprintf(
                "INSERT INTO %sfaqadminlog (id, time, usr, text, ip) VALUES (%d, %d, %d, '%s', '%s')",
                Database::getTablePrefix(),
                $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqadminlog', 'id'),
                $this->config->getDb()->escape($_SERVER['REQUEST_TIME']),
                $user->getUserId(),
                $this->config->getDb()->escape(nl2br($logText)),
                $this->config->getDb()->escape(Request::createFromGlobals()->getClientIp())
            );

            return (bool) $this->config->getDb()->query($query);
        } else {
            return false;
        }
    }

    /**
     * Deletes logging data older than 30 days.
     */
    public function delete(): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqadminlog WHERE time < %d',
            Database::getTablePrefix(),
            $_SERVER['REQUEST_TIME'] - 30 * 86400
        );

        return (bool) $this->config->getDb()->query($query);
    }
}
