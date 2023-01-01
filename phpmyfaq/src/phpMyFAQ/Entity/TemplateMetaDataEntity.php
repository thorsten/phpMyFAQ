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
 * @copyright 2018-2023 phpMyFAQ Team
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
    private int $id;

    private string $lang;

    private string $pageId;

    private string $type;

    private string $content;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): TemplateMetaDataEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): TemplateMetaDataEntity
    {
        $this->lang = $lang;
        return $this;
    }

    public function getPageId(): string
    {
        return $this->pageId;
    }

    public function setPageId(string $pageId): TemplateMetaDataEntity
    {
        $this->pageId = $pageId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): TemplateMetaDataEntity
    {
        $this->type = $type;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): TemplateMetaDataEntity
    {
        $this->content = $content;
        return $this;
    }
}
