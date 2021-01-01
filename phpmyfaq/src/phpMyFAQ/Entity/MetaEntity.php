<?php

/**
 * The MetaEntity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-08-09
 */

namespace phpMyFAQ\Entity;

/**
 * Class MetaEntity
 *
 * @package phpMyFAQ\Entity
 */
class MetaEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $lang;

    /**
     * @var string
     */
    private $pageId;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $content;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int $id
     * @return MetaEntity
     */
    public function setId(int $id): MetaEntity
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
     * @return MetaEntity
     */
    public function setLang(string $lang): MetaEntity
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
     * @return MetaEntity
     */
    public function setPageId(string $pageId): MetaEntity
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
     * @return MetaEntity
     */
    public function setType(string $type): MetaEntity
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
     * @return MetaEntity
     */
    public function setContent(string $content): MetaEntity
    {
        $this->content = $content;
        return $this;
    }
}
