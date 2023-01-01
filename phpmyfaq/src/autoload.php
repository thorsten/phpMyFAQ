<?php

/**
 * phpMyFAQ autoloader
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
 * @since     2021-04-05
 */

use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require __DIR__ . '/libs/autoload.php';
$loader->addPsr4('phpMyFAQ\\', __DIR__ . '/phpMyFAQ');
