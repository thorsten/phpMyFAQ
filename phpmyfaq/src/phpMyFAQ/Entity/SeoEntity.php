<?php

/**
 * SEO entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-23
 */

namespace phpMyFAQ\Entity;

use DateTime;
use phpMyFAQ\Enums\SeoType;

class SeoEntity
{
    private ?int $id;
    private SeoType $type;
    private int $referenceId;
    private string $referenceLanguage;
    private ?string $title;
    private ?string $description;
    private ?string $slug;
    private DateTime $created;

    public function getId(): ?int
    {
        return $this->id ?? null;
    }

    public function setId(int $id): SeoEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): SeoType
    {
        return $this->type;
    }

    public function setType(SeoType $type): SeoEntity
    {
        $this->type = $type;
        return $this;
    }

    public function getReferenceId(): int
    {
        return $this->referenceId;
    }

    public function setReferenceId(int $referenceId): SeoEntity
    {
        $this->referenceId = $referenceId;
        return $this;
    }

    public function getReferenceLanguage(): string
    {
        return $this->referenceLanguage;
    }

    public function setReferenceLanguage(string $referenceLanguage): SeoEntity
    {
        $this->referenceLanguage = $referenceLanguage;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title ?? null;
    }

    public function setTitle(string $title): SeoEntity
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description ?? null;
    }

    public function setDescription(string $description): SeoEntity
    {
        $this->description = $description;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug ?? null;
    }

    public function setSlug(string $slug): SeoEntity
    {
        $this->slug = $slug;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setCreated(DateTime $created): SeoEntity
    {
        $this->created = $created;
        return $this;
    }
}
