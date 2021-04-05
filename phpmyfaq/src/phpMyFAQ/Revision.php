<?php

/**
 * The FAQ revisions class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-01
 */

namespace phpMyFAQ;

/**
 * Class Revision
 *
 * @package phpMyFAQ
 */
class Revision
{
    /** @var Configuration */
    private $config;

    /**
     * Revision constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Adds a new revision from a given FAQ ID and FAQ language
     *
     * @param int    $faqId
     * @param string $faqLanguage
     * @return bool
     */
    public function create(int $faqId, string $faqLanguage): bool
    {
        $query = sprintf(
            "
            INSERT INTO
                %sfaqdata_revisions
            SELECT * FROM
                %sfaqdata
            WHERE
                id = %d
            AND
                lang = '%s'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $faqLanguage
        );

        $this->config->getDb()->query($query);

        return true;
    }


    /**
     * Gets all revisions from a given FAQ ID and FAQ language
     *
     * @param int    $faqId
     * @param string $faqLanguage
     * @param string $faqAuthor
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
            $faqLanguage
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
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
     * @param int    $faqId
     * @param string $faqLanguage
     * @return mixed
     */
    public function delete(int $faqId, string $faqLanguage)
    {
        $query = sprintf(
            "DELETE FROM %sfaqdata_revisions WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $faqId,
            $faqLanguage
        );

        return $this->config->getDb()->query($query);
    }
}
