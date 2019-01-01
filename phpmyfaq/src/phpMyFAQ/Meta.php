<?php

namespace phpMyFAQ;

/**
 * The Meta class provides CRUD methods for meta data information.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-09
 */

use phpMyFAQ\Entity\Meta as MetaEntity;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Meta
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-09
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
     * @param MetaEntity $data
     * @return int
     */
    public function add(MetaEntity $data)
    {
        $id = $this->config->getDb()->nextId(Db::getTablePrefix() . 'faqmeta', 'id');

        $query = sprintf("
            INSERT INTO
                %sfaqmeta
            (id, lang, page_id, type, content)
                VALUES
            (%d, '%s', '%s', '%s', '%s')",
            Db::getTablePrefix(),
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
     * @param $metaId
     * @return MetaEntity
     */
    public function getById($metaId)
    {
        $entity = new MetaEntity();
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE id = %d AND lang = '%s'",
            Db::getTablePrefix(),
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
     * @param $pageId
     * @return MetaEntity
     */
    public function getByPageId($pageId)
    {
        $entity = new MetaEntity();
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE page_id = '%s' AND lang = '%s'",
            Db::getTablePrefix(),
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
    public function getAll()
    {
        $metaData = [];
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE lang = '%s'",
            Db::getTablePrefix(),
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
     * @param $metaId
     * @param MetaEntity $data
     * @return bool
     */
    public function update($metaId, MetaEntity $data)
    {
        $query = sprintf(
            "UPDATE %sfaqmeta SET page_id = '%s', type = '%s', content = '%s' WHERE id = %d AND lang = '%s'",
            Db::getTablePrefix(),
            $data->getPageId(),
            $data->getType(),
            $data->getContent(),
            $metaId,
            $this->config->getLanguage()->getLanguage()
        );

        return (boolean) $this->config->getDb()->query($query);
    }

    /**
     * @param $id
     * @return bool
     */
    public function delete($id)
    {
        $query = sprintf(
            "DELETE FROM %sfaqmeta WHERE lang = '%s' AND id = %d",
            Db::getTablePrefix(),
            $this->config->getLanguage()->getLanguage(),
            $id
        );

        return (boolean) $this->config->getDb()->query($query);

    }
}
