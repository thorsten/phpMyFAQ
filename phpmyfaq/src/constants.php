<?php

/**
 * phpMyFAQ global constants
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2021-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2021-03-22
 */

//
// The phpMyFAQ root directory
//
if (!defined('PMF_ROOT_DIR')) {
    define('PMF_ROOT_DIR', dirname(__DIR__));
}

//
// The /src directory
//
if (!defined('PMF_SRC_DIR')) {
    define('PMF_SRC_DIR', __DIR__);
}

//
// The path to the logs
//
if (!defined('PMF_LOG_DIR')) {
    define('PMF_LOG_DIR', dirname(__DIR__) . '/logs/phpmyfaq.log');
}
