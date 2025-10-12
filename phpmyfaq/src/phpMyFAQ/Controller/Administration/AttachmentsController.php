<?php

declare(strict_types=1);

/**
 * The Administration Attachment Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\FormatBytesTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

class AttachmentsController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/attachments', name: 'admin.attachments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT);
        $page = max(1, $page);

        $session = $this->container->get('session');
        $collection = $this->container->get('phpmyfaq.attachment-collection');

        $itemsPerPage = 24;
        $allCrumbs = $collection->getBreadcrumbs();

        $crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $baseUrl = sprintf('%sadmin/attachments?page=%d', $this->configuration->getDefaultUrl(), $page);

        $pagination = new Pagination([
            'baseUrl' => $baseUrl,
            'total' => is_countable($allCrumbs) ? count($allCrumbs) : 0,
            'perPage' => $itemsPerPage,
        ]);

        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/attachments.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderAttachments' => Translation::get('ad_menu_attachment_admin'),
            'adminMsgAttachmentsFilename' => Translation::get('msgAttachmentsFilename'),
            'adminMsgTransToolLanguage' => Translation::get('msgTransToolLanguage'),
            'adminMsgAttachmentsFilesize' => Translation::get('msgAttachmentsFilesize'),
            'adminMsgAttachmentsMimeType' => Translation::get('msgAttachmentsMimeType'),
            'csrfTokenDeletion' => Token::getInstance($session)->getTokenString('delete-attachment'),
            'csrfTokenRefresh' => Token::getInstance($session)->getTokenString('refresh-attachment'),
            'attachments' => $crumbs,
            'adminMsgButtonDelete' => Translation::get('ad_gen_delete'),
            'adminMsgFaqTitle' => Translation::get('ad_entry_faq_record'),
            'adminAttachmentPagination' => $pagination->render(),
        ]);
    }
}
