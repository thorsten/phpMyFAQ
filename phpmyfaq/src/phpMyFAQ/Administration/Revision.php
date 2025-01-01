<?php

/**
 * The FAQ revisions class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-01
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Strings;

/**
 * Class Revision
 *
 * @package phpMyFAQ
 */
readonly class Revision
{
    /**
     * Revision constructor.
     */
    public function __construct(private Configuration $configuration)
    {
    }

    /**
     * Adds a new revision from a given FAQ ID and FAQ language
     */
    public function create(int $faqId, string $faqLanguage): bool
    {
        $query = sprintf(
            "
            INSERT INTO 
                %sfaqdata_revisions 
            SELECT 
                id, lang, solution_id, revision_id + 1, active, sticky, keywords, thema, content, author, email, 
                comment, updated, date_start, date_end, created, notes, sticky_order 
            FROM 
                %sfaqdata 
            WHERE 
                id = %d 
              AND 
                lang = '%s'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLanguage)
        );

        $this->configuration->getDb()->query($query);

        return true;
    }


    /**
     * Gets all revisions from a given FAQ ID and FAQ language
     *
     * @return array<string[]>
     */
    public function get(int $faqId, string $faqLanguage, string $faqAuthor): array
    {
        $revisionData = [];

        $query = sprintf(
            "SELECT 
                revision_id, updated, author FROM %sfaqdata_revisions
            WHERE
                id = %d
            AND
                lang = '%s'
            ORDER BY 
                revision_id",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLanguage)
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
                $revisionData[] = [
                    'revision_id' => $row->revision_id,
                    'updated' => $faqId === 0 ? date('YmdHis') : $row->updated,
                    'author' => $faqId === 0 ? ucfirst($faqAuthor) : $row->author,
                ];
            }
        }

        return $revisionData;
    }

    /**
     * Deletes all revisions for a given FAQ ID and FAQ language
     *
     * @param int    $faqId
     * @param string $faqLanguage
     * @return bool
     */
    public function delete(int $faqId, string $faqLanguage): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($faqLanguage)
        );

        return (bool) $this->configuration->getDb()->query($query);
    }
}
