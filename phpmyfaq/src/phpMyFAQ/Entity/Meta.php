<?php

namespace phpMyFAQ\Entity;

/**
 * The Meta class provides CRUD methods for meta data information.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ\Entity
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-09
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * Class Meta
 * @package phpMyFAQ\Entity
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2018 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-08-09
 */
class Meta
{
    /** @var int */
    private $id;

    /** @var string */
    private $lang;

    /** @var string */
    private $pageId;

    /** @var string */
    private $type;

    /** @var string */
    private $content;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Meta
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang()
    {
        return $this->lang;
    }

    /**
     * @param string $lang
     * @return Meta
     */
    public function setLang($lang)
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param string $pageId
     * @return Meta
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return Meta
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return Meta
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
}