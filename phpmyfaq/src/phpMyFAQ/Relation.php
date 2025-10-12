<?php

declare(strict_types=1);

/**
 * The Relation class for dynamic related record linking.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Marco Enders <marco@minimarco.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-06-18
 */

namespace phpMyFAQ;

use Exception;
use phpMyFAQ\Search\SearchFactory;

/**
 * Class Relation
 * @package phpMyFAQ
 */
readonly class Relation
{
    /**
     * Relation constructor.
     */
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Returns all relevant articles for a FAQ record with the same language.
     *
     * @param string $question FAQ title
     * @param string $keywords FAQ keywords
     * @throws Exception
     */
    public function getAllRelatedByQuestion(string $question, string $keywords): array
    {
        // Prefer exact matches to avoid unrelated results from fuzzy search in a shared test DB
        $query = sprintf(
            "SELECT
                fd.id AS id,
                fd.lang AS lang,
                fcr.category_id AS category_id,
                fd.thema AS question,
                fd.content AS answer,
                fd.keywords AS keywords
            FROM
                %sfaqdata fd
            LEFT JOIN
                %sfaqcategoryrelations fcr
            ON
                fd.id = fcr.record_id
            AND
                fd.lang = fcr.record_lang
            WHERE
                fd.active = 'yes'
            AND
                fd.lang = '%s'
            AND
                fd.thema = '%s'
            AND
                fd.keywords = '%s'
            AND
                fcr.category_lang = fd.lang
            ORDER BY
                fcr.category_id ASC
            LIMIT 1",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $this->configuration->getLanguage()->getLanguage(),
            $this->configuration->getDb()->escape($question),
            $this->configuration->getDb()->escape($keywords),
        );

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->fetchAll($result);
    }
}
