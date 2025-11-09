<?php

declare(strict_types=1);

/**
 * All SEO relevant stuff.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @copyright 2014-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-31
 */

namespace phpMyFAQ;

use DateTime;
use Exception;
use phpMyFAQ\Entity\SeoEntity;

/**
 * Class Seo
 *
 * @package phpMyFAQ
 */
readonly class Seo
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * @return bool True if the SEO entity was created
     */
    public function create(SeoEntity $seoEntity): bool
    {
        $query = sprintf(
            "INSERT INTO %sfaqseo (id, type, reference_id, reference_language, title, description) 
                VALUES (%d, '%s', %d, '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqseo', 'id'),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $this->configuration->getDb()->escape($seoEntity->getReferenceLanguage()),
            $this->configuration->getDb()->escape($seoEntity->getTitle()),
            $this->configuration->getDb()->escape($seoEntity->getDescription()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * @throws Exception
     */
    public function get(SeoEntity $seoEntity): SeoEntity
    {
        $query = sprintf(
            "SELECT * FROM %sfaqseo WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $this->configuration->getDb()->escape($seoEntity->getReferenceLanguage()),
        );

        $result = $this->configuration->getDb()->query($query);

        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchObject($result)) {
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
        $query = sprintf(
            "UPDATE %sfaqseo SET title = '%s', description = '%s' 
                WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($seoEntity->getTitle()),
            $this->configuration->getDb()->escape($seoEntity->getDescription()),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $this->configuration->getDb()->escape($seoEntity->getReferenceLanguage()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * @return bool True if the SEO entity was deleted
     */
    public function delete(SeoEntity $seoEntity): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqseo WHERE type = '%s' AND reference_id = %d AND reference_language = '%s'",
            Database::getTablePrefix(),
            $seoEntity->getSeoType()->value,
            $seoEntity->getReferenceId(),
            $this->configuration->getDb()->escape($seoEntity->getReferenceLanguage()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function getMetaRobots(string $action): string
    {
        return match ($action) {
            'main' => $this->configuration->get(item: 'seo.metaTagsHome'),
            'faq' => $this->configuration->get(item: 'seo.metaTagsFaqs'),
            'show' => $this->configuration->get(item: 'seo.metaTagsCategories'),
            default => $this->configuration->get(item: 'seo.metaTagsPages'),
        };
    }
}
