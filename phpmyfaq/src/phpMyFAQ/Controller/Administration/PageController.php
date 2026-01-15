<?php

/**
 * The Page Administration Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Pagination;
use phpMyFAQ\Pagination\UrlConfig;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\FormatDateTwigExtension;
use phpMyFAQ\Twig\Extensions\IsoDateTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class PageController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/pages', name: 'admin.pages', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::PAGE_ADD);
        $this->userHasPermission(PermissionType::PAGE_DELETE);
        $this->userHasPermission(PermissionType::PAGE_EDIT);

        $customPage = $this->container->get(id: 'phpmyfaq.custom-page');

        $itemsPerPage = 25;
        $page = Filter::filterVar($request->query->get('page'), FILTER_VALIDATE_INT, 1);

        $totalPages = $customPage->countPages(activeOnly: false);

        $pagination = new Pagination(
            baseUrl: $request->getUri(),
            total: $totalPages,
            perPage: $itemsPerPage,
            urlConfig: new UrlConfig(pageParamName: 'page'),
        );

        $offset = ($page - 1) * $itemsPerPage;
        $pages = $customPage->getPagesPaginated(
            activeOnly: false,
            limit: $itemsPerPage,
            offset: $offset,
            sortField: 'created',
            sortOrder: 'DESC',
        );

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/pages.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'pages' => $pages,
            'pagination' => $pagination->render(),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/page/add', name: 'admin.page.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::PAGE_ADD);

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/page.add.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'userEmail' => $this->currentUser->getUserData('email'),
            'userName' => $this->currentUser->getUserData('display_name'),
        ]);
    }

    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/page/edit/:pageId', name: 'admin.page.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::PAGE_EDIT);

        $pageId = (int) Filter::filterVar($request->attributes->get('pageId'), FILTER_VALIDATE_INT);

        $customPage = $this->container->get(id: 'phpmyfaq.custom-page');
        $pageData = $customPage->getById($pageId);

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatDateTwigExtension::class));
        return $this->render('@admin/content/page.edit.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'pageData' => $pageData,
            'pageDataContent' => isset($pageData['content'])
                ? htmlspecialchars((string) $pageData['content'], ENT_QUOTES)
                : '',
            'pageId' => $pageId,
        ]);
    }

    /**
     * @return array<string, string>
     * @throws \Exception
     */
    private function getBaseTemplateVars(): array
    {
        $user = $this->currentUser;
        $language = $this->configuration->getLanguage()->getLanguage();

        return [
            'permissionAddPage' => $user->perm->hasPermission($user->getUserId(), PermissionType::PAGE_ADD),
            'permissionEditPage' => $user->perm->hasPermission($user->getUserId(), PermissionType::PAGE_EDIT),
            'permissionDeletePage' => $user->perm->hasPermission($user->getUserId(), PermissionType::PAGE_DELETE),
            'defaultUrl' => $this->configuration->getDefaultUrl(),
            'enableWysiwyg' => $this->configuration->get(item: 'main.enableWysiwygEditor'),
            'ad_page_add' => Translation::get(key: 'ad_page_add'),
            'csrfToken_savePage' => Token::getInstance($this->session)->getTokenString('save-page'),
            'ad_page_author_name' => Translation::get(key: 'ad_page_author_name'),
            'ad_page_author_email' => Translation::get(key: 'ad_page_author_email'),
            'ad_page_set_active' => Translation::get(key: 'ad_page_set_active'),
            'selectLanguage' => LanguageHelper::renderSelectLanguage($language, false, [], 'lang'),
            'ad_entry_back' => Translation::get(key: 'ad_entry_back'),
            'ad_menu_pages' => Translation::get(key: 'ad_menu_pages'),
            'ad_page_title' => Translation::get(key: 'ad_page_title'),
            'ad_page_slug' => Translation::get(key: 'ad_page_slug'),
            'ad_page_created' => Translation::get(key: 'ad_page_created'),
            'ad_page_updated' => Translation::get(key: 'ad_page_updated'),
            'ad_page_update' => Translation::get(key: 'ad_page_update'),
            'ad_page_delete' => Translation::get(key: 'ad_page_delete'),
            'ad_page_nodata' => Translation::get(key: 'ad_page_nodata'),
            'ad_page_edit' => Translation::get(key: 'ad_page_edit'),
            'ad_page_header' => Translation::get(key: 'ad_page_header'),
            'ad_page_content' => Translation::get(key: 'ad_page_content'),
            'msgLanguage' => Translation::get(key: 'msgLanguage'),
            'ad_page_insertfail' => Translation::get(key: 'ad_page_insertfail'),
            'msgPages' => Translation::get(key: 'msgPages'),
            'ad_page_del' => Translation::get(key: 'ad_page_del'),
            'ad_page_nodelete' => Translation::get(key: 'ad_page_nodelete'),
            'ad_page_yesdelete' => Translation::get(key: 'ad_page_yesdelete'),
            'ad_page_delsuc' => Translation::get(key: 'ad_page_delsuc'),
            'ad_page_updatesuc' => Translation::get(key: 'ad_page_updatesuc'),
            'msgDeletePage' => Translation::get(key: 'msgDeletePage'),
            'csrfToken_deletePage' => Token::getInstance($this->session)->getTokenString('delete-page'),
            'csrfToken_updatePage' => Token::getInstance($this->session)->getTokenString('update-page'),
            'ad_entry_active' => Translation::get(key: 'ad_entry_active'),
            'csrfToken_activatePage' => Token::getInstance($this->session)->getTokenString('activate-page'),
            'ad_page_tab_content' => Translation::get(key: 'ad_page_tab_content'),
            'ad_page_tab_seo' => Translation::get(key: 'ad_page_tab_seo'),
            'ad_page_tab_settings' => Translation::get(key: 'ad_page_tab_settings'),
            'ad_page_seo_title' => Translation::get(key: 'ad_page_seo_title'),
            'ad_page_seo_description' => Translation::get(key: 'ad_page_seo_description'),
            'ad_page_seo_robots' => Translation::get(key: 'ad_page_seo_robots'),
            'ad_page_slug_help' => Translation::get(key: 'ad_page_slug_help'),
        ];
    }
}
