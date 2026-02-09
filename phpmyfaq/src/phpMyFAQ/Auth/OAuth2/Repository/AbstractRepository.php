<?php

/**
 * Base repository for OAuth2 persistence.
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;

abstract class AbstractRepository
{
    public function __construct(
        protected readonly Configuration $configuration,
    ) {
    }

    protected function db(): DatabaseDriver
    {
        return $this->configuration->getDb();
    }

    protected function table(string $tableName): string
    {
        return Database::getTablePrefix() . $tableName;
    }
}
