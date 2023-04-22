<?php

/**
 * Private phpMyFAQ Admin API: handling of Ajax search calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-08-24
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Search;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$ajaxAction = Filter::filterVar($request->query->get('ajaxaction'), FILTER_SANITIZE_SPECIAL_CHARS);

$search = new Search($faqConfig);

if ($ajaxAction === 'delete_searchterm') {
    $deleteData = json_decode(file_get_contents('php://input', true));

    if (!Token::getInstance()->verifyToken('delete-searchterms', $deleteData->csrf)) {
        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $response->setData(['error' => Translation::get('err_NotAuth')]);
        $response->send();
        exit(1);
    }

    $searchId = Filter::filterVar($deleteData->searchTermId, FILTER_VALIDATE_INT);

    if ($search->deleteSearchTermById($searchId)) {
        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['deleted' => $searchId]);
    } else {
        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $response->setData(['error' => $searchId]);
    }
    $response->send();
}
