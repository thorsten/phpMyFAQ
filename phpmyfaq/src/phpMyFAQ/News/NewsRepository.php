<?php

/**
 * News repository class
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
 * @since     2025-11-09
 */

declare(strict_types=1);

namespace phpMyFAQ\News;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\NewsMessage;
use stdClass;

/**
 * Repository for faqnews table access.
 */
final readonly class NewsRepository implements NewsRepositoryInterface
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * Fetch list of news rows for a language, optionally filtered by active flag and limited.
     * Rows are ordered by datum DESC.
     *
     * @return iterable<stdClass>
     */
    public function getLatest(string $language, bool $active = true, ?int $limit = null): iterable
    {
        $whereActive = $active ? "AND active = 'y'" : '';
        $query = sprintf(
            "SELECT * FROM %sfaqnews WHERE lang = '%s' %s ORDER BY datum DESC",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($language),
            $whereActive,
        );
        if ($limit !== null) {
            $query .= sprintf(' LIMIT %d', $limit);
        }

        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            yield $row;
        }
    }

    /**
     * Fetch headers for a language.
     *
     * @return iterable<stdClass>
     */
    public function getHeaders(string $language): iterable
    {
        $query = sprintf(
            "SELECT id, datum, lang, header, active FROM %sfaqnews WHERE lang = '%s' ORDER BY datum DESC",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($language),
        );
        $result = $this->configuration->getDb()->query($query);
        while ($row = $this->configuration->getDb()->fetchObject($result)) {
            yield $row;
        }
    }

    public function getById(int $newsId, string $language): ?stdClass
    {
        $query = sprintf(
            "SELECT * FROM %sfaqnews WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $newsId,
            $this->configuration->getDb()->escape($language),
        );
        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);
        return $row ?: null;
    }

    public function insert(NewsMessage $newsMessage): bool
    {
        $id = $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqnews', column: 'id');
        $query = sprintf(
            "\n            INSERT INTO %sfaqnews\n            (id, datum, lang, header, artikel, author_name, author_email, active, comment, link, linktitel, target)\n            VALUES\n            (%d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $id,
            $newsMessage->getCreated()->format(format: 'YmdHis'),
            $this->configuration->getDb()->escape($newsMessage->getLanguage()),
            $this->configuration->getDb()->escape($newsMessage->getHeader()),
            $this->configuration->getDb()->escape($newsMessage->getMessage()),
            $this->configuration->getDb()->escape($newsMessage->getAuthor()),
            $this->configuration->getDb()->escape($newsMessage->getEmail()),
            $newsMessage->isActive() ? 'y' : 'n',
            $newsMessage->isComment() ? 'y' : 'n',
            $this->configuration->getDb()->escape($newsMessage->getLink() ?? ''),
            $this->configuration->getDb()->escape($newsMessage->getLinkTitle() ?? ''),
            $this->configuration->getDb()->escape($newsMessage->getLinkTarget() ?? ''),
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    public function update(NewsMessage $newsMessage): bool
    {
        $query = sprintf(
            "\n            UPDATE %sfaqnews SET\n                datum = '%s',\n                lang = '%s',\n                header = '%s',\n                artikel = '%s',\n                author_name = '%s',\n                author_email = '%s',\n                active = '%s',\n                comment = '%s',\n                link = '%s',\n                linktitel = '%s',\n                target = '%s'\n            WHERE id = %d",
            Database::getTablePrefix(),
            $newsMessage->getCreated()->format(format: 'YmdHis'),
            $this->configuration->getDb()->escape($newsMessage->getLanguage()),
            $this->configuration->getDb()->escape($newsMessage->getHeader()),
            $this->configuration->getDb()->escape($newsMessage->getMessage()),
            $this->configuration->getDb()->escape($newsMessage->getAuthor()),
            $this->configuration->getDb()->escape($newsMessage->getEmail()),
            $newsMessage->isActive() ? 'y' : 'n',
            $newsMessage->isComment() ? 'y' : 'n',
            $this->configuration->getDb()->escape($newsMessage->getLink() ?? ''),
            $this->configuration->getDb()->escape($newsMessage->getLinkTitle() ?? ''),
            $this->configuration->getDb()->escape($newsMessage->getLinkTarget() ?? ''),
            $newsMessage->getId(),
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    public function delete(int $newsId, string $language): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqnews WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $newsId,
            $this->configuration->getDb()->escape($language),
        );
        return (bool) $this->configuration->getDb()->query($query);
    }

    public function activate(int $newsId, bool $status): bool
    {
        $query = sprintf(
            "UPDATE %sfaqnews SET active = '%s' WHERE id = %d",
            Database::getTablePrefix(),
            $status ? 'y' : 'n',
            $newsId,
        );
        return (bool) $this->configuration->getDb()->query($query);
    }
}
