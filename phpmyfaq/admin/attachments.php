<?php

/**
 * Frontend for handling with attachments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-12-13
 */

use phpMyFAQ\Attachment\AttachmentCollection;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\FormatBytesTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();
$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::ATTACHMENT_DELETE->value)) {
    $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT);
    $page = max(1, $page);

    $attachmentCollection = new AttachmentCollection($faqConfig);
    $itemsPerPage = 24;
    $allCrumbs = $attachmentCollection->getBreadcrumbs();

    $crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

    $baseUrl = sprintf(
        '%sadmin/?action=attachments&amp;page=%d',
        $faqConfig->getDefaultUrl(),
        $page
    );

    $pagination = new Pagination(
        [
            'baseUrl' => $baseUrl,
            'total' => is_countable($allCrumbs) ? count($allCrumbs) : 0,
            'perPage' => $itemsPerPage,
        ]
    );

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addExtension(new FormatBytesTwigExtension());
    $template = $twig->loadTemplate('@admin/content/attachments.twig');

    $templateVars = [
        'adminHeaderAttachments' => Translation::get('ad_menu_attachment_admin'),
        'adminMsgAttachmentsFilename' => Translation::get('msgAttachmentsFilename'),
        'adminMsgTransToolLanguage' => Translation::get('msgTransToolLanguage'),
        'adminMsgAttachmentsFilesize' => Translation::get('msgAttachmentsFilesize'),
        'adminMsgAttachmentsMimeType' => Translation::get('msgAttachmentsMimeType'),
        'csrfTokenDeletion' => Token::getInstance()->getTokenString('delete-attachment'),
        'csrfTokenRefresh' => Token::getInstance()->getTokenString('refresh-attachment'),
        'attachments' => $crumbs,
        'adminMsgButtonDelete' => Translation::get('ad_gen_delete'),
        'adminMsgFaqTitle' => Translation::get('ad_entry_faq_record'),
        'adminAttachmentPagination' => $pagination->render()
    ];

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
