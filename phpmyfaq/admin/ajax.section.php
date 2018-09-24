<?php

/**
 * AJAX: handling of Ajax section calls.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Timo Wolf <amna.wolf@gmail.com>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2018-09-21
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$ajaxAction = Filter::filterInput(INPUT_GET, 'ajaxaction', FILTER_SANITIZE_STRING);
$sectionId = Filter::filterInput(INPUT_GET, 'section_id', FILTER_VALIDATE_INT);

if ($user->perm->checkRight($user->getUserId(), 'add_section') ||
    $user->perm->checkRight($user->getUserId(), 'edit_section') ||
    $user->perm->checkRight($user->getUserId(), 'del_section')) {

    $sectionList = ($user->perm instanceof LargePermission) ? $user->perm->getAllSections() : [];
    // Returns all sections
    if ('get_all_sections' == $ajaxAction) {
        $sections = [];
        foreach ($sectionList as $sectionId) {
            $data = $user->perm->getSectionData($sectionId);
            $sections[] = array(
                'section_id' => $data['id'],
                'name' => $data['name'],
            );
        }
        echo json_encode($sections);
    }

}