<?php

/**
 * The MetaEntity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-09
 */

namespace phpMyFAQ\Entity;

/**
 * Class MetaEntity
 *
 * @package phpMyFAQ\Entity
 */
class TemplateMetaDataEntity
{
    /**
     * @var int
     */
    private int $id;

    /**
     * @var string
     */
    private string $lang;

    /**
     * @var string
     */
    private string $pageId;

    /**
     * @var string
     */
    private string $type;

    /**
     * @var string
     */
    private string $content;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int $id
     * @return TemplateMetaDataEntity
     */
    public function setId(int $id): TemplateMetaDataEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param  string $lang
     * @return TemplateMetaDataEntity
     */
    public function setLang(string $lang): TemplateMetaDataEntity
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageId(): string
    {
        return $this->pageId;
    }

    /**
     * @param  string $pageId
     * @return TemplateMetaDataEntity
     */
    public function setPageId(string $pageId): TemplateMetaDataEntity
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param  string $type
     * @return TemplateMetaDataEntity
     */
    public function setType(string $type): TemplateMetaDataEntity
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param  string $content
     * @return TemplateMetaDataEntity
     */
    public function setContent(string $content): TemplateMetaDataEntity
    {
        $this->content = $content;
        return $this;
    }
}
