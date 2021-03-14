<?php

/**
 * Category entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2014-08-14
 */

namespace phpMyFAQ\Entity;

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
    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return CategoryEntity
     */
    public function setActive($active): CategoryEntity
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return CategoryEntity
     */
    public function setDescription(string $description): CategoryEntity
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return int
     */
    public function getGroupId(): int
    {
        return $this->groupId;
    }

    /**
     * @param int $groupId
     *
     * @return CategoryEntity
     */
    public function setGroupId($groupId): CategoryEntity
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * @param int $id
     *
     * @return CategoryEntity
     */
    public function setId($id): CategoryEntity
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
     * @param string $lang
     * @return CategoryEntity
     */
    public function setLang(string $lang): CategoryEntity
    {
        $this->lang = $lang;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return CategoryEntity
     */
    public function setName(string $name): CategoryEntity
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return int
     */
    public function getParentId(): int
    {
        return (int)$this->parentId;
    }

    /**
     * @param int $parentId
     *
     * @return CategoryEntity
     */
    public function setParentId($parentId): CategoryEntity
    {
        $this->parentId = $parentId;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     * @return CategoryEntity
     */
    public function setUserId($userId): CategoryEntity
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getImage(): ?string
    {
        return $this->image;
    }

    /**
     * @param string|null $image
     * @return CategoryEntity
     */
    public function setImage(?string $image): CategoryEntity
    {
        $this->image = $image;

        return $this;
    }

    /**
     * @return bool
     */
    public function getShowHome(): bool
    {
        return (bool) $this->showHome;
    }

    /**
     * @param bool $showHome
     * @return CategoryEntity
     */
    public function setShowHome(?bool $showHome): CategoryEntity
    {
        $this->showHome = $showHome;

        return $this;
    }
}
