<?php

/**
 * Autosave handler.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2003-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-07-07
 */

use phpMyFAQ\Category;
use phpMyFAQ\Changelog;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$do = Filter::filterInput(INPUT_GET, 'do', FILTER_SANITIZE_STRING);

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if ('insertentry' === $do &&
    ($user->perm->checkRight($user->getUserId(), 'edit_faq') || $user->perm->checkRight($user->getUserId(),
            'add_faq')) ||
    'saveentry' === $do && $user->perm->checkRight($user->getUserId(), 'edit_faq')) {
    $user = CurrentUser::getFromCookie($faqConfig);
    if (!$user instanceof CurrentUser) {
        $user = CurrentUser::getFromSession($faqConfig);
    }

    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories = Filter::filterInputArray(INPUT_POST, [
        'rubrik' => [
            'filter' => FILTER_VALIDATE_INT,
            'flags' => FILTER_REQUIRE_ARRAY,
        ]
    ]
    );
    $recordLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags = Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky = Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $recordId = Filter::filterInput(INPUT_POST, 'recordId', FILTER_VALIDATE_INT);
    $solutionId = Filter::filterInput(INPUT_POST, 'solutionId', FILTER_VALIDATE_INT);
    $revisionId = Filter::filterInput(INPUT_POST, 'revisionId', FILTER_VALIDATE_INT);
    $changed = '';

    $user_permission = Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restrictedUsers = ('all' == $user_permission) ? -1 : Filter::filterInput(INPUT_POST, 'restrictedUsers',
                                                                              FILTER_VALIDATE_INT);
    $group_permission = Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restrictedGroups = ('all' == $group_permission) ? -1 : Filter::filterInput(INPUT_POST, 'restrictedGroups',
                                                                                FILTER_VALIDATE_INT);

    if (!is_null($question) && !is_null($categories)) {
        $tagging = new Tags($faqConfig);
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        if (!isset($categories['rubrik'])) {
            $categories['rubrik'] = [];
        }

        $recordData = [
            'id' => $recordId,
            'lang' => $recordLang,
            'revisionId' => $revisionId,
            'active' => $active,
            'sticky' => (!is_null($sticky) ? 1 : 0),
            'thema' => html_entity_decode($question),
            'content' => html_entity_decode($content),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => empty($date) ? date('YmdHis') : str_replace(['-', ':', ' '], '', $date),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
            'notes' => ''
        ];

        if ('saveentry' == $do || $recordId) {
            /* Create a revision anyway, it's autosaving */
            $faq->addNewRevision($recordId, $recordLang);
            ++$revisionId;

            $changelog = new Changelog($faqConfig);
            $changelog->addEntry($recordId, $user->getUserId(), nl2br($changed), $recordLang, $revisionId);

            $visits = new Visits($faqConfig);
            $visits->logViews($recordId);

            if ($faq->hasTranslation($recordId, $recordLang)) {
                $faq->updateRecord($recordData);
            } else {
                $recordId = $faq->addRecord($recordData, false);
            }

            $faq->deleteCategoryRelations($recordId, $recordLang);
            $faq->addCategoryRelations($categories['rubrik'], $recordId, $recordLang);

            if ($tags != '') {
                $tagging->saveTags($recordId, explode(',', $tags));
            } else {
                $tagging->deleteTagsFromRecordId($recordId);
            }

            $faq->deletePermission('user', $recordId);
            $faq->addPermission('user', $recordId, [$restrictedUsers]);
            $category->deletePermission('user', $categories['rubrik']);
            $category->addPermission('user', $categories['rubrik'], [$restrictedUsers]);
            if ($faqConfig->get('security.permLevel') !== 'basic') {
                $faq->deletePermission('group', $recordId);
                $faq->addPermission('group', $recordId, [$restrictedGroups]);
                $category->deletePermission('group', $categories['rubrik']);
                $category->addPermission('group', $categories['rubrik'], [$restrictedGroups]);
            }
        } elseif ('insertentry' == $do) {
            unset($recordData['id']);
            unset($recordData['revisionId']);
            $revisionId = 1;
            $recordId = $faq->addRecord($recordData);
            if ($recordId) {
                $changelog = new Changelog($faqConfig);
                $changelog->addEntry($recordId, $user->getUserId(), nl2br($changed), $recordData['lang']);
                $visits = new Visits($faqConfig);
                $visits->add($recordId);

                $faq->addCategoryRelations($categories['rubrik'], $recordId, $recordData['lang']);

                if ($tags != '') {
                    $tagging->saveTags($recordId, explode(',', $tags));
                }

                $faq->addPermission('user', $recordId, [$restrictedUsers]);
                $category->addPermission('user', $categories['rubrik'], [$restrictedUsers]);

                if ($faqConfig->get('security.permLevel') !== 'basic') {
                    $faq->addPermission('group', $recordId, [$restrictedGroups]);
                    $category->addPermission('group', $categories['rubrik'], [$restrictedGroups]);
                }
            }
        }

        $out = [
            'msg' => sprintf('Item auto-saved at revision %d', $revisionId),
            'revisionId' => $revisionId,
            'recordId' => $recordId,
        ];

        $http->sendJsonWithHeaders($out);
    }
} else {
    $http->setStatus(401);
    $http->sendJsonWithHeaders(['msg' => 'Missing article rights']);
}
