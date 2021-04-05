<?php

/**
 * The Meta class provides CRUD methods for meta data information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-09
 */

namespace phpMyFAQ;

use phpMyFAQ\Entity\MetaEntity;

/**
 * Class Meta
 *
 * @package phpMyFAQ
 */
class Meta
{
    /**
     * Configuration object.
     *
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @param  MetaEntity $data
     * @return int
     */
    public function add(MetaEntity $data): int
    {
        $id = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqmeta', 'id');

        $query = sprintf(
            "
            INSERT INTO
                %sfaqmeta
            (id, lang, page_id, type, content)
                VALUES
            (%d, '%s', '%s', '%s', '%s')",
            Database::getTablePrefix(),
            $id,
            $this->config->getLanguage()->getLanguage(),
            $data->getPageId(),
            $data->getType(),
            $data->getContent()
        );

        $this->config->getDb()->query($query);

        return $id;
    }

    /**
     * @param int $metaId
     * @return MetaEntity
     */
    public function getById(int $metaId): MetaEntity
    {
        $entity = new MetaEntity();
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

    /**
     * @param string $pageId
     * @return MetaEntity
     */
    public function getByPageId(string $pageId): MetaEntity
    {
        $entity = new MetaEntity();
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
     * @return MetaEntity[]
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
                $entity = new MetaEntity();
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

    /**
     * @param int        $metaId
     * @param MetaEntity $data
     * @return bool
     */
    public function update(int $metaId, MetaEntity $data): bool
    {
        $query = sprintf(
            "UPDATE %sfaqmeta SET page_id = '%s', type = '%s', content = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $data->getPageId(),
            $data->getType(),
            $data->getContent(),
            $metaId,
            $this->config->getLanguage()->getLanguage()
        );

        return (bool)$this->config->getDb()->query($query);
    }

    /**
     * @param int $id
     * @return bool
     */
    public function delete($id): bool
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
