#!/usr/bin/php
<?php
/**
 * This scripts iterates recursively through the whole phpMyFAQ project and
 * creates SHA-1 keys for all files
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-11
 */

define('PMF_ROOT_DIR', dirname(__DIR__).'/phpmyfaq');

require PMF_ROOT_DIR . '/src/phpMyFAQ/System.php';

$system = new phpMyFAQ\System();

try {
    echo $system->createHashes();
} catch (Exception $exception) {
    echo 'Something went wrong: ' . $exception->getMessage();
}
