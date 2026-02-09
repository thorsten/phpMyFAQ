<?php

/**
 * Tenant isolation mode enum
 *
 * Defines the isolation strategy for multi-tenant instances:
 * - prefix: Table prefix isolation in a shared database (default)
 * - schema: Schema-per-tenant isolation in a shared database
 * - database: Separate database per tenant
 *
 * Configurable via the PMF_TENANT_ISOLATION_MODE environment variable.
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

namespace phpMyFAQ\Enums;

enum TenantIsolationMode: string
{
    case PREFIX = 'prefix';
    case SCHEMA = 'schema';
    case DATABASE = 'database';
}
