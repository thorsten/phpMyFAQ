<?php

namespace phpMyFAQ;

/**
 * The Meta class provides CRUD methods for meta data information.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
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
 * @copyright 2018 phpMyFAQ Team
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
     * @param $pageId
     * @return array
     */
    public function getByPageId($pageId)
    {
        $query = sprintf(
            "SELECT id, lang, page_id, type, content FROM %sfaqmeta WHERE id = %d AND lang = '%s'",
            Db::getTablePrefix(),
            $pageId,
            $this->config->getLanguage()->getLanguage()
        );

        $result = $this->config->getDb()->query($query);

        if ($result) {
            return $this->config->getDb()->fetchAll($result);
        }

        return [];
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
     * @param $id
     * @param MetaEntity $data
     */
    public function update($id, MetaEntity $data)
    {

    }

    /**
     * @param $id
     */
    public function delete($id)
    {

    }
}