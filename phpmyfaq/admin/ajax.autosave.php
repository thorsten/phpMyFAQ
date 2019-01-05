<?php

/**
 * Autosave handler.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-07-07
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$do = Filter::filterInput(INPUT_GET, 'do', FILTER_SANITIZE_STRING);

if ('insertentry' === $do &&
    ($user->perm->checkRight($user->getUserId(), 'edit_faq') || $user->perm->checkRight($user->getUserId(), 'add_faq')) ||
    'saveentry' === $do && $user->perm->checkRight($user->getUserId(), 'edit_faq')) {
    $user = CurrentUser::getFromCookie($faqConfig);
    if (!$user instanceof CurrentUser) {
        $user = CurrentUser::getFromSession($faqConfig);
    }

    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories = Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags' => FILTER_REQUIRE_ARRAY, )));
    $record_lang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags = Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky = Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $record_id = Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solution_id = Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision_id = Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed = Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);

    $user_permission = Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restricted_users = ('all' == $user_permission) ? -1 : Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
    $group_permission = Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restricted_groups = ('all' == $group_permission) ? -1 : Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);

    if (!is_null($question) && !is_null($categories)) {
        $tagging = new Tags($faqConfig);
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        if (!isset($categories['rubrik'])) {
            $categories['rubrik'] = [];
        }

        $recordData = array(
            'id' => $record_id,
            'lang' => $record_lang,
            'revision_id' => $revision_id,
            'active' => $active,
            'sticky' => (!is_null($sticky) ? 1 : 0),
            'thema' => html_entity_decode($question),
            'content' => html_entity_decode($content),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => empty($date) ? date('YmdHis') : str_replace(array('-', ':', ' '), '', $date),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart).'000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd).'235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
        );

        if ('saveentry' == $do || $record_id) {
            /* Create a revision anyway, it's autosaving */
            $faq->addNewRevision($record_id, $record_lang);
            ++$revision_id;

            $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $record_lang, $revision_id);

            $visits = new Visits($faqConfig);
            $visits->logViews($record_id);

            if ($faq->isAlreadyTranslated($record_id, $record_lang)) {
                $faq->updateRecord($recordData);
            } else {
                $record_id = $faq->addRecord($recordData, false);
            }

            $faq->deleteCategoryRelations($record_id, $record_lang);
            $faq->addCategoryRelations($categories['rubrik'], $record_id, $record_lang);

            if ($tags != '') {
                $tagging->saveTags($record_id, explode(',', $tags));
            } else {
                $tagging->deleteTagsFromRecordId($record_id);
            }

            $faq->deletePermission('user', $record_id);
            $faq->addPermission('user', $record_id, $restricted_users);
            $category->deletePermission('user', $categories['rubrik']);
            $category->addPermission('user', $categories['rubrik'], $restricted_users);
            if ($faqConfig->get('security.permLevel') != 'basic') {
                $faq->deletePermission('group', $record_id);
                $faq->addPermission('group', $record_id, $restricted_groups);
                $category->deletePermission('group', $categories['rubrik']);
                $category->addPermission('group', $categories['rubrik'], $restricted_groups);
            }
        } elseif ('insertentry' == $do) {
            unset($recordData['id']);
            unset($recordData['revision_id']);
            $revision_id = 1;
            $record_id = $faq->addRecord($recordData);
            if ($record_id) {
                $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $recordData['lang']);
                $visits = new Visits($faqConfig);
                $visits->add($record_id);

                $faq->addCategoryRelations($categories['rubrik'], $record_id, $recordData['lang']);

                if ($tags != '') {
                    $tagging->saveTags($record_id, explode(',', $tags));
                }

                $faq->addPermission('user', $record_id, $restricted_users);
                $category->addPermission('user', $categories['rubrik'], $restricted_users);

                if ($faqConfig->get('security.permLevel') != 'basic') {
                    $faq->addPermission('group', $record_id, $restricted_groups);
                    $category->addPermission('group', $categories['rubrik'], $restricted_groups);
                }
            }
        }

        $out = array(
            'msg' => sprintf('Item auto-saved at revision %d', $revision_id),
            'revision_id' => $revision_id,
            'record_id' => $record_id,
        );

        print json_encode($out);
    }
} else {
    print json_encode(array('msg' => 'Missing article rights'));
}
