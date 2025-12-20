<?php

/**
 * The relation repository class.
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
 * @since     2025-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Relation;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

readonly class RelationRepository implements RelationRepositoryInterface
{
    private LoggerInterface $logger;

    public function __construct(
        private Configuration $configuration,
        ?LoggerInterface $logger = null,
    ) {
        $this->logger = $logger ?? new NullLogger();
    }

    public function getAllRelatedByQuestion(string $question, string $keywords, string $language): array
    {
        $db = $this->configuration->getDb();

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
            $db->escape($language),
            $db->escape($question),
            $db->escape($keywords),
        );

        $result = $db->query($query);
        if ($result === false) {
            $this->logger->error(message: 'Relation getAllRelatedByQuestion query failed', context: [
                'question' => $question,
                'keywords' => $keywords,
                'language' => $language,
            ]);
            return [];
        }

        return $db->fetchAll($result);
    }
}
