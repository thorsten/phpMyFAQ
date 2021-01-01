<?php

/**
 * The main changelog class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

namespace phpMyFAQ;

/**
 * Class Changelog
 *
 * @package phpMyFAQ
 */
class Changelog
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Changelog constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new changelog entry in the table "faqchanges".
     *
     * @param  int    $id
     * @param  int    $userId
     * @param  string $text
     * @param  string $lang
     * @param  int    $revisionId
     * @return bool
     */
    public function addEntry(int $id, int $userId, string $text, string $lang, int $revisionId = 0): bool
    {
        $query = sprintf(
            "INSERT INTO
                %sfaqchanges
            (id, beitrag, lang, revision_id, usr, datum, what)
                VALUES
            (%d, %d, '%s', %d, %d, %d, '%s')",
            Database::getTablePrefix(),
            $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqchanges', 'id'),
            $id,
            $lang,
            $revisionId,
            $userId,
            $_SERVER['REQUEST_TIME'],
            $text
        );

        return (bool) $this->config->getDb()->query($query);
    }

    /**
     * Returns the changelog of a FAQ record.
     *
     * @param  int $recordId
     * @return array
     */
    public function getChangeEntries(int $recordId): array
    {
        $entries = [];

        $query = sprintf(
            '
            SELECT
                DISTINCT revision_id, usr, datum, what
            FROM
                %sfaqchanges
            WHERE
                beitrag = %d
            ORDER BY revision_id, datum DESC',
            Database::getTablePrefix(),
            $recordId
        );

        if ($result = $this->config->getDb()->query($query)) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $entries[] = [
                    'revision_id' => $row->revision_id,
                    'user' => $row->usr,
                    'date' => $row->datum,
                    'changelog' => $row->what,
                ];
            }
        }

        return $entries;
    }
}
