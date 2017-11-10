<?php

/**
 * AJAX: handling of Ajax search calls.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2011-08-24
 */
<<<<<<< HEAD

use Symfony\Component\HttpFoundation\JsonResponse;

=======
>>>>>>> 2.10
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
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
<<<<<<< HEAD

$response->send();
=======
>>>>>>> 2.10
