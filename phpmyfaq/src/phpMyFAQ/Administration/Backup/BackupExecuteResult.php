<?php

/**
 * Backup execute result DTO.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-12-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Administration\Backup;

final readonly class BackupExecuteResult
{
    public function __construct(
        public int $queriesOk,
        public int $queriesFailed,
        public ?string $lastErrorQuery = null,
        public ?string $lastErrorReason = null,
    ) {
    }
}
