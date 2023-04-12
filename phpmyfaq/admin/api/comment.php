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
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$deleteData = json_decode(file_get_contents('php://input', true));

if ('delete' === $deleteData->data->ajaxaction && $user->perm->hasPermission($user->getUserId(), 'delcomment')) {
    if (!Token::getInstance()->verifyToken('delete-comment', $deleteData->data->{'pmf-csrf-token'})) {
        $http->setStatus(401);
        $result = ['error' => Translation::get('err_NotAuth')];
        exit(1);
    }

    $comment = new Comments($faqConfig);
    $success = false;

    $commentIds = $deleteData->data->{'comments[]'};

    if (!is_null($commentIds)) {
        if (!is_array($commentIds)) {
            $commentIds = [$commentIds];
        }
        foreach ($commentIds as $commentId) {
            $success = $comment->delete($deleteData->type, $commentId);
        }

        $http->setStatus(200);
        $http->sendJsonWithHeaders(['success' => $success]);
    } else {
        $http->setStatus(401);
        $http->sendJsonWithHeaders(['error' => false]);
    }
}
