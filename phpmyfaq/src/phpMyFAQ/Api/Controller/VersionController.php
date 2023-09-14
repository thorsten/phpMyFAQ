<?php

/**
 * The Version Controller for the REST API
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
 * @since     2023-07-29
 */

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\JsonResponse;

class VersionController
{
    public function index(): JsonResponse
    {
        $response = new JsonResponse();
        $response->setData(Configuration::getConfigurationInstance()->getVersion());

        return $response;
    }
}
