#!/usr/bin/env php
<?php

/**
 * Outputs the current phpMyFAQ version string from System.php.
 * This is the single source of truth bridge: shell scripts and package.json
 * derive their version from the PHP constants defined in System.php.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-03-08
 */

require dirname(__DIR__) . '/phpmyfaq/src/libs/autoload.php';

echo phpMyFAQ\System::getVersion();
