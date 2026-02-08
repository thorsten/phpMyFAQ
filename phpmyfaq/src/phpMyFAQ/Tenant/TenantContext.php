<?php

/**
 * Tenant context
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

readonly class TenantContext
{
    public function __construct(
        private int $tenantId,
        private string $hostname,
        private string $tablePrefix,
        private string $configDir,
        private string $plan,
        private TenantQuotas $quotas,
    ) {
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getHostname(): string
    {
        return $this->hostname;
    }

    public function getTablePrefix(): string
    {
        return $this->tablePrefix;
    }

    public function getConfigDir(): string
    {
        return $this->configDir;
    }

    public function getPlan(): string
    {
        return $this->plan;
    }

    public function getQuotas(): TenantQuotas
    {
        return $this->quotas;
    }
}
