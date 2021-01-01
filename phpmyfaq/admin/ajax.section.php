<?php

/**
 * AJAX: handling of Ajax section calls.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2018-09-21
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Permission\LargePermission;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$sectionId = Filter::filterInput(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);

$http = new HttpHelper();
$http->setContentType('application/json');
$http->addHeader();

if (
    $user->perm->hasPermission($user->getUserId(), 'add_section') ||
    $user->perm->hasPermission($user->getUserId(), 'edit_section') ||
    $user->perm->hasPermission($user->getUserId(), 'del_section')
) {
    $sectionList = ($user->perm instanceof LargePermission) ? $user->perm->getAllSections() : [];

    // Returns all sections
    if ('get_all_sections' == $ajaxAction) {
        $sections = [];
        foreach ($sectionList as $sectionId) {
            $data = $user->perm->getSectionData($sectionId);
            $sections[] = [
                'section_id' => $data['id'],
                'name' => $data['name'],
            ];
        }
        $http->sendJsonWithHeaders($sections);
    }

    // Return the section data
    if ('get_section_data' == $ajaxAction) {
        $http->sendJsonWithHeaders($user->perm->getSectionData($sectionId));
    }

    // Returns all section members
    if ('get_all_members' == $ajaxAction) {
        $memberList = $user->perm->getSectionGroups($sectionId);
        $members = [];
        foreach ($memberList as $single_member) {
            $group = $user->perm->getGroupData($single_member);
            $members[] = array('group_id' => $group['group_id'],
                                'name' => $group['name'] );
        }
        $http->sendJsonWithHeaders($members);
    }
}
