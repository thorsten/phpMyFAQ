<?php

/**
 * phpMyFAQ admin routes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-08
 */

use Symfony\Component\Routing\Route;

$routes->add(
    'admin.api.updates',
    new Route('/updates', ['_class_and_method' => 'phpMyFAQ\Administration\Api\UpdateApi::updates'])
);

$routes->add(
    'admin.api.update-check',
    new Route('/update-check', ['_class_and_method' => 'phpMyFAQ\Administration\Api\UpdateApi::updateCheck'])
);
