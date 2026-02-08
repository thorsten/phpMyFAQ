<?php

/**
 * Tenant lifecycle event
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

use Symfony\Contracts\EventDispatcher\Event;

class TenantLifecycleEvent extends Event
{
    public function __construct(
        private readonly int $tenantId,
        private readonly array $context = [],
    ) {
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
