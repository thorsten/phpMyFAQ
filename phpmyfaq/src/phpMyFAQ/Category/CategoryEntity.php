<?php

/**
 * Entity entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2020 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-14
 */

namespace phpMyFAQ\Category;

/**
 * Class CategoryEntity
 *
 * @package phpMyFAQ\Category
 */
class CategoryEntity
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
     * @var int
     */
    private $parentId;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $groupId = -1;

    /**
     * @var bool
     */
    private $active;

    /**
     * @var bool
     */
    private $showHome;

    /**
     * @var string
     */
    private $image = '';

    /**
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return CategoryEntity
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return CategoryEntity
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     *
     * @return CategoryEntity
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     *
     * @return CategoryEntity
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
     *
     * @return CategoryEntity
     */
    public function setLang($lang)
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return CategoryEntity
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return (int)$this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return CategoryEntity
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getUserId()
    {
        return (int)$this->userId;
    }

    /**
     * @param string $userId
     *
     * @return CategoryEntity
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param string $image
     *
     * @return CategoryEntity
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowHome()
    {
        return $this->showHome;
    }

    /**
     *
     * @return CategoryEntity
     */
    public function setShowHome($showHome)
    {
        $this->showHome = $showHome;

        return $this;
    }
}
