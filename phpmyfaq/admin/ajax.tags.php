<?php

/**
 * AJAX: Search for tags.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-12-15
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Tags;
use phpMyFAQ\Entity\TagEntity as TagEntity;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

// Send headers
$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);

$oTag = new Tags($faqConfig);

switch ($ajaxAction) {
    case 'list':
        $autoCompleteValue = Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);

        if (!is_null($autoCompleteValue)) {
            if (strpos($autoCompleteValue, ',')) {
                $arrayOfValues = explode(',', $autoCompleteValue);
                $autoCompleteValue = end($arrayOfValues);
            }
            $tags = $oTag->getAllTags(strtolower(trim($autoCompleteValue)), PMF_TAGS_CLOUD_RESULT_SET_SIZE, true);
        } else {
            $tags = $oTag->getAllTags();
        }

        if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
            $i = 0;
            $tagNames = [];
            foreach ($tags as $tagName) {
                ++$i;
                if ($i <= PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE) {
                    $currentTag = new stdClass();
                    $currentTag->tagName = $tagName;
                    $tagNames[] = $currentTag;
                }
            }

            $http->sendJsonWithHeaders($tagNames);
        }
        break;

    case 'update':
        $id = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $tag = Filter::filterInput(INPUT_POST, 'tag', FILTER_SANITIZE_STRING);
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
            $http->setStatus(400);
            $http->sendJsonWithHeaders($PMF_LANG['err_NotAuth']);
            exit(1);
        }

        $entity = new TagEntity();
        $entity->setId($id);
        $entity->setName($tag);

        if ($oTag->updateTag($entity)) {
            $http->sendJsonWithHeaders($PMF_LANG['ad_entryins_suc']);
        } else {
            $http->setStatus(400);
            $http->sendJsonWithHeaders($PMF_LANG['ad_entryins_fail']);
        }
        break;
}
