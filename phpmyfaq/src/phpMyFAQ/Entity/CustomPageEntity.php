<?php

/**
 * The Custom Page entity class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-12
 */

declare(strict_types=1);

namespace phpMyFAQ\Entity;

use DateTime;

class CustomPageEntity
{
    private int $id;

    private string $language;

    private string $pageTitle;

    private string $slug;

    private string $content;

    private string $authorName;

    private string $authorEmail;

    private bool $active;

    private DateTime $created;

    private ?DateTime $updated = null;

    private ?string $seoTitle = null;

    private ?string $seoDescription = null;

    private string $seoRobots = 'index,follow';

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): CustomPageEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): CustomPageEntity
    {
        $this->language = $language;
        return $this;
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }

    public function setPageTitle(string $pageTitle): CustomPageEntity
    {
        $this->pageTitle = $pageTitle;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): CustomPageEntity
    {
        $this->slug = $slug;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): CustomPageEntity
    {
        $this->content = $content;
        return $this;
    }

    public function getAuthorName(): string
    {
        return $this->authorName;
    }

    public function setAuthorName(string $authorName): CustomPageEntity
    {
        $this->authorName = $authorName;
        return $this;
    }

    public function getAuthorEmail(): string
    {
        return $this->authorEmail;
    }

    public function setAuthorEmail(string $authorEmail): CustomPageEntity
    {
        $this->authorEmail = $authorEmail;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): CustomPageEntity
    {
        $this->active = $active;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): CustomPageEntity
    {
        $this->created = $created;
        return $this;
    }

    public function getUpdated(): ?DateTime
    {
        return $this->updated ?? null;
    }

    public function setUpdated(DateTime $updated): CustomPageEntity
    {
        $this->updated = $updated;
        return $this;
    }

    public function getSeoTitle(): ?string
    {
        return $this->seoTitle;
    }

    public function setSeoTitle(?string $seoTitle): CustomPageEntity
    {
        $this->seoTitle = $seoTitle;
        return $this;
    }

    public function getSeoDescription(): ?string
    {
        return $this->seoDescription;
    }

    public function setSeoDescription(?string $seoDescription): CustomPageEntity
    {
        $this->seoDescription = $seoDescription;
        return $this;
    }

    public function getSeoRobots(): string
    {
        return $this->seoRobots;
    }

    public function setSeoRobots(string $seoRobots): CustomPageEntity
    {
        $this->seoRobots = $seoRobots;
        return $this;
    }
}
