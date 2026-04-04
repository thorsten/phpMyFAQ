<?php

/**
 * Bootstrap for phpbench benchmarks.
 *
 * Lighter than tests/bootstrap.php — only sets up the autoloader and the
 * bare minimum constants that phpMyFAQ classes reference at load time.
 * Database, session, and full Symfony kernel setup are intentionally omitted
 * because benchmarks only exercise pure-PHP, in-memory code paths.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author  Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link    https://www.phpmyfaq.de
 * @since   2026-04-04
 */

declare(strict_types=1);

date_default_timezone_set('Europe/Berlin');

define('PMF_ROOT_DIR', dirname(__DIR__) . '/phpmyfaq');
define('PMF_SRC_DIR', dirname(__DIR__) . '/phpmyfaq/src');
define('PMF_CONFIG_DIR', dirname(__DIR__) . '/tests/content/core/config');
define('PMF_CONTENT_DIR', dirname(__DIR__) . '/tests/content');
define('PMF_TRANSLATION_DIR', dirname(__DIR__) . '/phpmyfaq/translations');

const IS_VALID_PHPMYFAQ = true;

require_once dirname(__DIR__) . '/phpmyfaq/src/libs/autoload.php';
