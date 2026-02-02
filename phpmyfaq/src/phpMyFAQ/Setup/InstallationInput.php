<?php

/**
 * Value object holding validated installation input parameters.
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
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

readonly class InstallationInput
{
    /**
     * @param array<string, string|int|null> $dbSetup
     * @param array<string, string|int|null> $ldapSetup
     * @param array<string, string|array<string>>     $esSetup
     * @param array<string, string|array<string>>     $osSetup
     */
    public function __construct(
        public array $dbSetup,
        public array $ldapSetup,
        public array $esSetup,
        public array $osSetup,
        public string $loginName,
        public string $password,
        public string $language,
        public string $realname,
        public string $email,
        public string $permLevel,
        public string $rootDir,
        public bool $ldapEnabled = false,
        public bool $esEnabled = false,
        public bool $osEnabled = false,
    ) {
    }
}
