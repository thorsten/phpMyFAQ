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
 * @copyright 2009-2026 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2009-06-23
 */

use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Attachment\AttachmentPermission;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

set_time_limit(0);

if (headers_sent()) {
    die();
}

$attachmentErrors = [];

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('src/services.php');
} catch (Exception $exception) {
    echo $exception->getMessage();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$request = Request::createFromGlobals();

// authenticate with session information
$user = $container->get('phpmyfaq.user.current_user');

$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT);

$faqPermission = new Permission($faqConfig);

$userPermission = [];
$groupPermission = [];

try {
    $attachment = AttachmentFactory::create($id);
    $userPermission = $faqPermission->get(Permission::USER, $attachment->getRecordId());
    $groupPermission = $faqPermission->get(Permission::GROUP, $attachment->getRecordId());
} catch (AttachmentException $attachmentException) {
    $attachmentErrors[] =
        Translation::get(key: 'msgAttachmentInvalid') . ' (' . $attachmentException->getMessage() . ')';
}

$userId = $user->getUserId();

// The per-record ACL (user + group permissions, incl. the -1 "all" sentinel)
// must always be satisfied before an attachment is served. Groups are only
// enforced when the permission layer actually supports them (security.permLevel
// other than "basic").
$groupSupport = $user->perm instanceof MediumPermission;
$userGroups = $groupSupport ? $user->perm->getUserGroups($userId) : [];

$hasRecordAccess = AttachmentPermission::hasRecordAccess(
    $userId,
    $userPermission,
    $groupPermission,
    $userGroups,
    $groupSupport,
);

// get user rights
$permission = [];
if ($user->isLoggedIn()) {
    // read all rights, set false
    $allRights = $user->perm->getAllRightsData();
    foreach ($allRights as $right) {
        $permission[$right['name']] = false;
    }

    // check user rights, set true
    $allUserRights = $user->perm->getAllUserRights($userId);
    foreach ($allRights as $allRight) {
        if (in_array($allRight['right_id'], $allUserRights)) {
            $permission[$allRight['name']] = true;
        }
    }
}

// Logged-in users need the "dlattachment" right; anonymous users are only
// permitted when records.allowDownloadsForGuests is enabled. This flag only
// waives the right requirement for guests - it never widens the per-record ACL.
$hasDownloadRight = AttachmentPermission::hasDownloadRight(
    $user->isLoggedIn(),
    isset($permission['dlattachment']) && $permission['dlattachment'] === true,
    (bool) $faqConfig->get('records.allowDownloadsForGuests'),
);

if ($attachment && $attachment->getRecordId() > 0 && $hasRecordAccess && $hasDownloadRight) {
    $response = new StreamedResponse(function () use ($attachment) {
        $attachment->rawOut();
    });

    $response->headers->set('Content-Type', $attachment->getMimeType());
    $response->headers->set('Content-Length', $attachment->getFilesize());

    if ($attachment->getMimeType() === 'application/pdf') {
        $response->headers->set(
            'Content-Disposition',
            'inline; filename="' . rawurlencode($attachment->getFilename()) . '"',
        );
    } else {
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="' . rawurlencode($attachment->getFilename()) . '"',
        );
    }

    $response->headers->set('Content-MD5', $attachment->getRealHash());
    $response->send();
} else {
    $attachmentErrors[] = Translation::get(key: 'msgAttachmentInvalid');
}

// If we're here, there was an error with file download
$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/');
$twigTemplate = $twig->loadTemplate('./attachment.twig');

// Twig template variables
$templateVars = [
    ...$templateVars,
    'attachmentErrors' => $attachmentErrors,
];

return $templateVars;
