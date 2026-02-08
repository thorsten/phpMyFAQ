<?php

/**
 * Tenant quotas
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Tenant;

readonly class TenantQuotas
{
    public function __construct(
        private ?int $maxFaqs = null,
        private ?int $maxAttachmentSize = null,
        private ?int $maxUsers = null,
        private ?int $maxApiRequests = null,
        private ?int $maxCategories = null,
    ) {
    }

    public function getMaxFaqs(): ?int
    {
        return $this->maxFaqs;
    }

    public function getMaxAttachmentSize(): ?int
    {
        return $this->maxAttachmentSize;
    }

    public function getMaxUsers(): ?int
    {
        return $this->maxUsers;
    }

    public function getMaxApiRequests(): ?int
    {
        return $this->maxApiRequests;
    }

    public function getMaxCategories(): ?int
    {
        return $this->maxCategories;
    }
}
