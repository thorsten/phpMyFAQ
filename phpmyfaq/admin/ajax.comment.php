<?php

/**
 * AJAX: deletes comments with the given id.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-03-20
 */

use phpMyFAQ\Comment;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_POST, 'ajaxaction', FILTER_SANITIZE_STRING);
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if ('delete' === $ajaxAction && $user->perm->checkRight($user->getUserId(), 'delcomment')) {
    $comment = new Comment($faqConfig);
    $checkFaqs = [
        'filter' => FILTER_VALIDATE_INT,
        'flags' => FILTER_REQUIRE_ARRAY,
    ];
    $checkNews = [
        'filter' => FILTER_VALIDATE_INT,
        'flags' => FILTER_REQUIRE_ARRAY,
    ];
    $success = false;

    $faqComments = Filter::filterInputArray(INPUT_POST, ['faq_comments' => $checkFaqs]);
    $newsComments = Filter::filterInputArray(INPUT_POST, ['news_comments' => $checkNews]);

    if (!is_null($faqComments['faq_comments'])) {
        foreach ($faqComments['faq_comments'] as $commentId => $recordId) {
            $success = $comment->deleteComment($recordId, $commentId);
        }
    }

    if (!is_null($newsComments['news_comments'])) {
        foreach ($newsComments['news_comments'] as $commentId => $recordId) {
            $success = $comment->deleteComment($recordId, $commentId);
        }
    }

    $http->sendWithHeaders($success);
} else {
    $http->sendStatus(401);
    $http->sendWithHeaders(false);
}
