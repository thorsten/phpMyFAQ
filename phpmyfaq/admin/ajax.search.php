<?php

/**
 * AJAX: handling of Ajax search calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2011-08-24
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Search;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$searchTerm = Filter::filterInput(INPUT_GET, 'searchterm', FILTER_SANITIZE_STRING);

$search = new Search($faqConfig);
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

switch ($ajaxAction) {
    case 'delete_searchterm':
        if ($search->deleteSearchTerm($searchTerm)) {
            $http->sendWithHeaders(true);
        } else {
            $http->setStatus(400);
            $http->sendWithHeaders(false);
        }

        break;
}
