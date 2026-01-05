<?php

/**
 * SEO repository class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Seo;

use DateTime;
use Exception;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\SeoEntity;

/**
 * Repository for persisting and retrieving SEO entities.
 */
class SeoRepository implements SeoRepositoryInterface
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * @return bool True if the SEO entity was created
     */
    public function create(SeoEntity $seoEntity): bool
    {
        $databaseDriver = $this->configuration->getDb();

        $query = sprintf(
            'INSERT INTO %sfaqseo (id, type, reference_id, reference_language, title, description) '
            . "VALUES (%d, '%s', %d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $databaseDriver->nextId(Database::getTablePrefix() . 'faqseo', 'id'),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $databaseDriver->escape($seoEntity->getReferenceLanguage()),
            $databaseDriver->escape($seoEntity->getTitle()),
            $databaseDriver->escape($seoEntity->getDescription()),
        );

        return (bool) $databaseDriver->query($query);
    }

    /**
     * @throws Exception
     */
    public function get(SeoEntity $seoEntity): SeoEntity
    {
        $databaseDriver = $this->configuration->getDb();

        $query = sprintf(
            "SELECT * FROM %sfaqseo WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $databaseDriver->escape($seoEntity->getReferenceLanguage()),
        );

        $result = $databaseDriver->query($query);

        if ($databaseDriver->numRows($result) > 0) {
            while ($row = $databaseDriver->fetchObject($result)) {
                $seoEntity
                    ->setId((int) $row->id)
                    ->setTitle($row->title)
                    ->setDescription($row->description)
                    ->setCreated(new DateTime($row->created));
            }
        }

        return $seoEntity;
    }

    /**
     * @return bool True if the SEO entity was updated
     */
    public function update(SeoEntity $seoEntity): bool
    {
        $databaseDriver = $this->configuration->getDb();

        $query = sprintf(
            "UPDATE %sfaqseo SET title = '%s', description = '%s' "
            . "WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $databaseDriver->escape($seoEntity->getTitle()),
            $databaseDriver->escape($seoEntity->getDescription()),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $databaseDriver->escape($seoEntity->getReferenceLanguage()),
        );

        return (bool) $databaseDriver->query($query);
    }

    /**
     * @return bool True if the SEO entity was deleted
     */
    public function delete(SeoEntity $seoEntity): bool
    {
        $databaseDriver = $this->configuration->getDb();

        $query = sprintf(
            "DELETE FROM %sfaqseo WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $databaseDriver->escape($seoEntity->getReferenceLanguage()),
        );

        return (bool) $databaseDriver->query($query);
    }
}
