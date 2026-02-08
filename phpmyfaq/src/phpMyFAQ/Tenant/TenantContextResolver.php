<?php

/**
 * Tenant context resolver
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

use phpMyFAQ\Database;
use Symfony\Component\HttpFoundation\Request;

class TenantContextResolver
{
    public function resolve(?Request $request = null): TenantContext
    {
        $request ??= Request::createFromGlobals();
        $hostname = $request->getHost();

        if ($hostname === '') {
            $hostname = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        }

        $configDir = defined('PMF_CONFIG_DIR') ? PMF_CONFIG_DIR : '';
        $tablePrefix = Database::getTablePrefix() ?? '';

        $tenantId = $this->readIntEnv('PMF_TENANT_ID') ?? 0;
        $plan = $this->readStringEnv('PMF_TENANT_PLAN') ?? 'free';

        $quotas = new TenantQuotas(
            $this->readIntEnv('PMF_TENANT_QUOTA_MAX_FAQS'),
            $this->readIntEnv('PMF_TENANT_QUOTA_MAX_ATTACHMENT_SIZE'),
            $this->readIntEnv('PMF_TENANT_QUOTA_MAX_USERS'),
            $this->readIntEnv('PMF_TENANT_QUOTA_MAX_API_REQUESTS'),
            $this->readIntEnv('PMF_TENANT_QUOTA_MAX_CATEGORIES'),
        );

        return new TenantContext($tenantId, $hostname, $tablePrefix, $configDir, $plan, $quotas);
    }

    private function readIntEnv(string $key): ?int
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private function readStringEnv(string $key): ?string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
