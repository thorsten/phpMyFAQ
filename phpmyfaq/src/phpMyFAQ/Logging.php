<?php

/**
 * The main Logging class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-08-15
 */

namespace phpMyFAQ;

/**
 * Class Logging
 *
 * @package phpMyFAQ
 */
class Logging
{
    /**
     * @var Configuration
     */
    private $config = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Returns the number of entries.
     *
     * @return int
     */
    public function getNumberOfEntries()
    {
        $query = sprintf(
            '
            SELECT
                id
            FROM
                %sfaqadminlog',
            Database::getTablePrefix()
        );

        return $this->config->getDb()->numRows(
            $this->config->getDb()->query($query)
        );
    }

    /**
     * Returns all data from the admin log.
     *
     * @return array
     */
    public function getAll()
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
            $data[$row->id] = array(
                'time' => $row->time,
                'usr' => $row->usr,
                'text' => $row->text,
                'ip' => $row->ip,
            );
        }

        return $data;
    }

    /**
     * Adds a new admin log entry.
     *
     * @param User   $user    User object
     * @param string $logText Logged string
     *
     * @return bool
     */
    public function logAdmin(User $user, $logText = '')
    {
        if ($this->config->get('main.enableAdminLog')) {
            $query = sprintf(
                "
                INSERT INTO
                    %sfaqadminlog
                (id, time, usr, text, ip)
                    VALUES 
                (%d, %d, %d, '%s', '%s')",
                Database::getTablePrefix(),
                $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqadminlog', 'id'),
                $_SERVER['REQUEST_TIME'],
                $user->userdata->get('user_id'),
                $this->config->getDb()->escape(nl2br($logText)),
                $_SERVER['REMOTE_ADDR']
            );

            return $this->config->getDb()->query($query);
        } else {
            return false;
        }
    }

    /**
     * Deletes logging data older than 30 days.
     *
     * @return bool
     */
    public function delete(): bool
    {
        $query = sprintf(
            'DELETE FROM %sfaqadminlog WHERE time < %d',
            Database::getTablePrefix(),
            $_SERVER['REQUEST_TIME'] - 30 * 86400
        );

        return $this->config->getDb()->query($query);
    }
}
