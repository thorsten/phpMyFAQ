<?php

/**
 * Private phpMyFAQ Admin API: deletes comments with the given id.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-20
 */

use phpMyFAQ\Comments;
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

$deleteData = json_decode(file_get_contents('php://input', true));

if ('delete' === $deleteData->data->ajaxaction && $user->perm->hasPermission($user->getUserId(), 'delcomment')) {
    if (!Token::getInstance()->verifyToken('delete-comment', $deleteData->data->{'pmf-csrf-token'})) {
        $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
        $response->setData(['error' => Translation::get('err_NotAuth')]);
        $response->send();
        exit();
    }

    $comment = new Comments($faqConfig);
    $success = false;

    $commentIds = $deleteData->data->{'comments[]'} ?? [];

    if (!is_null($commentIds)) {
        if (!is_array($commentIds)) {
            $commentIds = [$commentIds];
        }
        foreach ($commentIds as $commentId) {
            $success = $comment->delete($deleteData->type, $commentId);
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => $success]);
    } else {
        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
        $response->setData(['error' => false]);
    }
    $response->send();
}
