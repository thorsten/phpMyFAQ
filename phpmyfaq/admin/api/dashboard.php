<?php

/**
 * Private phpMyFAQ Admin API: handling of REST calls for the dashboard
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-10-24
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Session;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_UNSAFE_RAW);

// Send headers
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

switch ($ajaxAction) {
    case 'user-visits-last-30-days':
        if ($faqConfig->get('main.enableUserTracking')) {
            $session = new Session($faqConfig);
            $http->setStatus(200);
            $http->sendJsonWithHeaders($session->getLast30DaysVisits());
        }
        break;
}
