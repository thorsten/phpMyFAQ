<?php
/**
 * Handle attachment downloads
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-06-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

set_time_limit(0);

if (headers_sent()) {
    die();
}

$attachmentErrors = array();

// authenticate with session information
$user = PMF_User_CurrentUser::getFromSession($faqConfig->get('security.ipCheck'));
if (!$user instanceof PMF_User_CurrentUser) {
    $user = new PMF_User_CurrentUser(); // user not logged in -> empty user object
}

$id         = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$attachment = PMF_Attachment_Factory::create($id);

$userPermission  = $faq->getPermission('user', $attachment->getRecordId());
$groupPermission = $faq->getPermission('group', $attachment->getRecordId());

// Check on group permissions
if ($user->perm instanceof PMF_Perm_PermMedium) {
    if (count($groupPermission) && in_array($groupPermission[0], $user->perm->getUserGroups($user->getUserId()))) {
        $groupPermission = true;
    } else {
        $groupPermission = false;
    }
} else {
    $groupPermission = true;
}

// Check in user's permissions
if (in_array($user->getUserId(), $userPermission)) {
    $userPermission = true;
} else {
    $userPermission = false;
}

if ($attachment && ($groupPermission || ($groupPermission && $userPermission))) {
    try {
        $attachment->rawOut();
        exit(0);
    } catch (Exception $e) {
        $attachmentErrors[] = $PMF_LANG['msgAttachmentInvalid'] . ' (' . $e->getMessage() . ')';
    }
} else {
    $attachmentErrors[] = $PMF_LANG['err_NotAuth'];
}

// If we're here, there was an error with file download
$tpl->parseBlock('writeContent', 'attachmentErrors', array('item' => implode('<br/>', $attachmentErrors)));
$tpl->parse('writeContent', array());
$tpl->merge('writeContent', 'index');
