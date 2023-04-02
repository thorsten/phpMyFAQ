<?php

/**
 * The Meta class provides CRUD methods for metadata information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-09
 */

namespace phpMyFAQ\Template;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\TemplateMetaDataEntity;

/**
 * Class Meta
 *
 * @package phpMyFAQ
 */
class TemplateMetaData
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    public function add(TemplateMetaDataEntity $data): int
    {
        $id = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqmeta', 'id');

        $query = sprintf(
            "INSERT INTO %sfaqmeta (id, lang, page_id, type, content) VALUES (%d, '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage(),
            $this->config->getDb()->escape($data->getPageId()),
            $this->config->getDb()->escape($data->getType()),
            $this->config->getDb()->escape($data->getContent())
        );

        $this->config->getDb()->query($query);

        return $id;
    }

    public function getById(int $metaId): TemplateMetaDataEntity
    {
        $entity = new TemplateMetaDataEntity();
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $metaId,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);

        if ($result) {
            $row = $this->config->getDb()->fetchObject($result);
            return $entity
                ->setId($row->id)
                ->setLang($row->lang)
                ->setPageId($row->page_id)
                ->setType($row->type)
                ->setContent($row->content);
        }

        return $entity;
    }

    public function getByPageId(string $pageId): TemplateMetaDataEntity
    {
        $entity = new TemplateMetaDataEntity();
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE page_id = '%s' AND lang = '%s'",
            Database::getTablePrefix(),
            $pageId,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);
        if ($result) {
            if ($row = $this->config->getDb()->fetchObject($result)) {
                $entity
                    ->setId($row->id)
                    ->setLang($row->lang)
                    ->setPageId($row->page_id)
                    ->setType($row->type)
                    ->setContent($row->content);
            }
        }

        return $entity;
    }

    /**
     * @return TemplateMetaDataEntity[]
     */
    public function getAll(): array
    {
        $metaData = [];
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE lang = '%s'",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);

        if ($this->config->getDb()->numRows($result) > 0) {
            while ($row = $this->config->getDb()->fetchObject($result)) {
                $entity = new TemplateMetaDataEntity();
                $entity
                    ->setId($row->id)
                    ->setLang($row->lang)
                    ->setPageId($row->page_id)
                    ->setType($row->type)
                    ->setContent($row->content);
                $metaData[] = $entity;
            }
        }

        return $metaData;
    }

    public function update(int $metaId, TemplateMetaDataEntity $data): bool
    {
        $query = sprintf(
            "UPDATE %sfaqmeta SET page_id = '%s', type = '%s', content = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $this->config->getDb()->escape($data->getPageId()),
            $this->config->getDb()->escape($data->getType()),
            $this->config->getDb()->escape($data->getContent()),
            $metaId,
            $this->config->getLanguage()->getLanguage()
        );

        return (bool)$this->config->getDb()->query($query);
    }

    public function delete(int $id): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqmeta WHERE lang = '%s' AND id = %d",
            Database::getTablePrefix(),
            $this->config->getLanguage()->getLanguage(),
            $id
        );

        return (bool)$this->config->getDb()->query($query);
    }
}
