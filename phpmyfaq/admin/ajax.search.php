<?php
/**
 * AJAX: handling of Ajax search calls
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-08-24
 */

use Symfony\Component\HttpFoundation\JsonResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = PMF_Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$searchTerm = PMF_Filter::filterInput(INPUT_GET, 'searchterm', FILTER_SANITIZE_STRING);

$search = new PMF_Search($faqConfig);
$response = new JsonResponse;

switch ($ajaxAction) {

    case 'delete_searchterm':
        $response->setData(
            $search->deleteSearchTerm($searchTerm)
        );

        break;
}

$response->send();
