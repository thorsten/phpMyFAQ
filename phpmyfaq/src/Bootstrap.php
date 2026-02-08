<?php

/**
 * Bootstraps a phpMyFAQ instance
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-03-07
 */

declare(strict_types=1);

use phpMyFAQ\Bootstrapper;

require __DIR__ . '/constants.php';
require __DIR__ . '/autoload.php';

$bootstrapper = new Bootstrapper();
$bootstrapper->run();

$faqConfig = $bootstrapper->getFaqConfig();
$db = $bootstrapper->getDb();
$request = $bootstrapper->getRequest();
