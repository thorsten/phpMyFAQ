<?php

/**
 * The main changelog class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Changelog
 *
 * @package phpMyFAQ
 */
readonly class Changelog
{
    /**
     * Changelog constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Adds a new changelog entry in the table "faqchanges".
     */
    public function add(int $id, int $userId, string $text, string $lang, int $revisionId = 0): bool
    {
        $query = sprintf(
            "INSERT INTO
                %sfaqchanges
            (id, beitrag, lang, revision_id, usr, datum, what)
                VALUES
            (%d, %d, '%s', %d, %d, %d, '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqchanges', 'id'),
            $id,
            $lang,
            $revisionId,
            $userId,
            Request::createFromGlobals()->server->get('REQUEST_TIME'),
            $text
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * Returns the changelog of a FAQ record.
     */
    public function getByFaqId(int $recordId): array
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

        if ($result = $this->configuration->getDb()->query($query)) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
