<?php

/**
 * Category entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2014-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    private ?int $id = null;

    private ?string $lang = null;

    private ?int $parentId = null;

    private ?string $name = null;

    private ?string $description = null;

    private ?int $userId = null;

    private int $groupId = -1;

    private ?bool $active = null;

    private ?bool $showHome = null;

    private ?string $image = '';

    public function getActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active): CategoryEntity
    {
        $this->active = $active;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): CategoryEntity
    {
        $this->description = $description;

        return $this;
    }

    public function getGroupId(): int
    {
        return $this->groupId;
    }

    public function setGroupId(int $groupId): CategoryEntity
    {
        $this->groupId = $groupId;

        return $this;
    }

    public function getId(): int
    {
        return (int)$this->id;
    }

    public function setId(int $id): CategoryEntity
    {
        $this->id = $id;

        return $this;
    }

    public function getLang(): string
    {
        return $this->lang;
    }

    public function setLang(string $lang): CategoryEntity
    {
        $this->lang = $lang;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): CategoryEntity
    {
        $this->name = $name;

        return $this;
    }

    public function getParentId(): int
    {
        return (int)$this->parentId;
    }

    /**
     * @param int $parentId
     */
    public function setParentId($parentId): CategoryEntity
    {
        $this->parentId = $parentId;

        return $this;
    }

    public function getUserId(): int
    {
        return (int)$this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId): CategoryEntity
    {
        $this->userId = $userId;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): CategoryEntity
    {
        $this->image = $image;

        return $this;
    }

    public function getShowHome(): bool
    {
        return (bool) $this->showHome;
    }

    /**
     * @param bool $showHome
     */
    public function setShowHome(?bool $showHome): CategoryEntity
    {
        $this->showHome = $showHome;

        return $this;
    }
}
