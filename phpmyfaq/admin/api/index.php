<?php

/**
 * Private phpMyFAQ Admin API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-02
 */

use phpMyFAQ\Application;
use phpMyFAQ\Configuration;

require '../../src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

$routes = include PMF_SRC_DIR  . '/admin-routes.php';

$app = new Application($faqConfig);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
