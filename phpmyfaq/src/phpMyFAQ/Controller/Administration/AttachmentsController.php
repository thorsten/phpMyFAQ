<?php

/**
 * The Administration Attachment Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Attachment\AttachmentCollection;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\FormatBytesTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class AttachmentsController extends AbstractAdministrationController
{
    public function __construct(
        private readonly AttachmentCollection $attachmentCollection,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/attachments', name: 'admin.attachments', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::ATTACHMENT_DELETE);

        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);
        $page = max(1, $page);

        $collection = $this->attachmentCollection;

        $itemsPerPage = 24;
        /** @var list<array<string, mixed>|\stdClass> $allCrumbs */
        $allCrumbs = $collection->getBreadcrumbs();
        $allCrumbs = $this->restrictToAllowedCategories($allCrumbs);

        $crumbs = array_slice($allCrumbs, ($page - 1) * $itemsPerPage, $itemsPerPage);

        $pagination = new Pagination(
            baseUrl: $request->getUri(),
            total: is_countable($allCrumbs) ? count($allCrumbs) : 0,
            perPage: $itemsPerPage,
            urlConfig: new UrlConfig(pageParamName: 'page'),
        );

        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/attachments.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderAttachments' => Translation::get(key: 'ad_menu_attachment_admin'),
            'adminMsgAttachmentsFilename' => Translation::get(key: 'msgAttachmentsFilename'),
            'adminMsgTransToolLanguage' => Translation::get(key: 'msgTransToolLanguage'),
            'adminMsgAttachmentsFilesize' => Translation::get(key: 'msgAttachmentsFilesize'),
            'adminMsgAttachmentsMimeType' => Translation::get(key: 'msgAttachmentsMimeType'),
            'csrfTokenDeletion' => Token::getInstance($this->session)->getTokenString('delete-attachment'),
            'csrfTokenRefresh' => Token::getInstance($this->session)->getTokenString('refresh-attachment'),
            'attachments' => $crumbs,
            'adminMsgButtonDelete' => Translation::get(key: 'ad_gen_delete'),
            'adminMsgFaqTitle' => Translation::get(key: 'ad_entry_faq_record'),
            'adminAttachmentPagination' => $pagination->render(),
        ]);
    }

    /**
     * Keeps only the attachments whose FAQ lies entirely within the categories the user's
     * delete right is restricted to, so the overview offers no attachment the API would
     * then reject with a 403. Mirrors the empty-list policy of
     * userHasPermissionForCategories(): an uncategorized FAQ is hidden from restricted users.
     *
     * @param list<array<string, mixed>|\stdClass> $crumbs
     * @return list<array<string, mixed>|\stdClass>
     * @throws \Exception
     */
    private function restrictToAllowedCategories(array $crumbs): array
    {
        $allowedCategoryIds = $this->currentUser->perm->getAllowedCategoriesForRight(
            $this->currentUser->getUserId(),
            PermissionType::ATTACHMENT_DELETE->value,
        );

        if ($allowedCategoryIds === null) {
            return $crumbs;
        }

        if ($allowedCategoryIds === [] || $crumbs === []) {
            return [];
        }

        $categoryRelation = new Relation(
            $this->configuration,
            new Category($this->configuration, [], withPermission: false),
        );

        $recordIdsByLanguage = [];
        foreach ($crumbs as $crumb) {
            [$language, $recordId] = self::faqOfCrumb($crumb);
            $recordIdsByLanguage[$language][] = $recordId;
        }

        $categoryIdsByRecord = [];
        foreach ($recordIdsByLanguage as $language => $recordIds) {
            $categoryIdsByRecord[$language] = $categoryRelation->getCategoryIdsForRecords($recordIds, $language);
        }

        return array_values(array_filter($crumbs, static function (array|\stdClass $crumb) use (
            $categoryIdsByRecord,
            $allowedCategoryIds,
        ): bool {
            /** @var array<string, mixed>|\stdClass $crumb */
            [$language, $recordId] = self::faqOfCrumb($crumb);
            $faqCategoryIds = $categoryIdsByRecord[$language][$recordId] ?? [];

            return $faqCategoryIds !== [] && array_diff($faqCategoryIds, $allowedCategoryIds) === [];
        }));
    }

    /**
     * The database drivers return rows as objects, while callers may also pass plain arrays.
     *
     * @param array<string, mixed>|\stdClass $crumb
     * @return array{0: string, 1: int} the language and id of the FAQ the attachment belongs to
     */
    private static function faqOfCrumb(array|\stdClass $crumb): array
    {
        if ($crumb instanceof \stdClass) {
            return [(string) $crumb->record_lang, (int) $crumb->record_id];
        }

        return [(string) $crumb['record_lang'], (int) $crumb['record_id']];
    }
}
