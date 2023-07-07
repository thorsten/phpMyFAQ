<?php

/**
 * Private phpMyFAQ Admin API: everything for the online update
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
 * @since     2023-07-02
 */

use phpMyFAQ\Filter;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

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

/*
 * API
 * Retrieve Available Updates
 *  - GET /updates: Returns a list of available updates.
 *  - GET /updates/{version}: Retrieves information about a specific update.
 *
 * Apply Updates
 *  - POST /updates/{version}/apply: Applies a specific update identified by its ID.
 *
 * Progress
 *  - GET /updates/{version}/progress: Retrieves information about the progress of the update
 */

switch ($ajaxAction) {
    // GET /updates: Returns a list of available updates.
    default:
        $client = HttpClient::create();
        try {
            $versions = $client->request(
                'GET',
                'https://api.phpmyfaq.de/versions'
            );
            $response->setStatusCode(Response::HTTP_OK);
            $response->setContent($versions->getContent());
            $response->send();
        } catch (TransportExceptionInterface $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData($e->getMessage());
            $response->send();
        }
        break;
}
