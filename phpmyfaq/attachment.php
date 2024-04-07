<?php

/**
 * Handle attachment downloads.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-06-23
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

set_time_limit(0);

if (headers_sent()) {
    die();
}

$attachmentErrors = [];

$request = Request::createFromGlobals();

// authenticate with session information
$user = CurrentUser::getCurrentUser($faqConfig);

$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT);

$faqPermission = new Permission($faqConfig);

$userPermission = [];
$groupPermission = [];

try {
    $attachment = AttachmentFactory::create($id);
    $userPermission = $faqPermission->get(Permission::USER, $attachment->getRecordId());
    $groupPermission = $faqPermission->get(Permission::GROUP, $attachment->getRecordId());
} catch (AttachmentException $attachmentException) {
    $attachmentErrors[] = Translation::get('msgAttachmentInvalid') . ' (' . $attachmentException->getMessage() . ')';
}

// Check on group permissions
if ($user->perm instanceof MediumPermission) {
    if ($groupPermission !== []) {
        foreach ($user->perm->getUserGroups($user->getUserId()) as $userGroups) {
            if (in_array($userGroups, $groupPermission)) {
                $groupPermission = true;
                break;
            }
        }
    } else {
        $groupPermission = false;
    }
} else {
    $groupPermission = true;
}

// Check user's permissions
$userPermission = in_array($user->getUserId(), $userPermission);

// get user rights
$permission = [];
if ($user->isLoggedIn()) {
    // read all rights, set false
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }

    // check user rights, set true
    $allUserRights = $user->perm->getAllUserRights($user->getUserId());
    foreach ($allRights as $allRight) {
        if (in_array($allRight['right_id'], $allUserRights)) {
            $permission[$allRight['name']] = true;
        }
    }
}

if (
    $attachment && ($faqConfig->get('records.allowDownloadsForGuests') ||
        (($groupPermission || ($groupPermission && $userPermission)) && isset($permission['dlattachment'])))
) {
    try {
        $attachment->rawOut();
    } catch (AttachmentException $e) {
        $attachmentErrors[] = $e->getMessage();
    }

    exit(0);
}
$attachmentErrors[] = Translation::get('err_NotAuth');

// If we're here, there was an error with file download
$template->parseBlock('mainPageContent', 'attachmentErrors', ['item' => implode('<br>', $attachmentErrors)]);
$template->parse('mainPageContent', []);
