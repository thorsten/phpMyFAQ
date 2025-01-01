<?php

/**
 * The Administration Export Controller
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
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class ExportController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     */
    #[Route('/export', name: 'admin.export', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::EXPORT);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get('phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        return $this->render(
            '@admin/import-export/export.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderExport' => Translation::get('ad_menu_export'),
                'hasNoFaqs' => Database::checkOnEmptyTable('faqdata'),
                'errorMessageNoFaqs' => Translation::get('msgErrorNoRecords'),
                'hasCategories' => !Database::checkOnEmptyTable('faqcategories'),
                'headerCategories' => Translation::get('ad_export_which_cat'),
                'msgCategory' => Translation::get('msgCategory'),
                'msgAllCategories' => Translation::get('msgShowAllCategories'),
                'categoryOptions' => $categoryHelper->renderOptions(0),
                'msgWithSubCategories' => Translation::get('ad_export_cat_downwards'),
                'headerExportType' => Translation::get('ad_export_type'),
                'msgChooseExportType' => Translation::get('ad_export_type_choose'),
                'msgViewType' => Translation::get('ad_export_download_view'),
                'msgDownloadType' => HeaderUtils::DISPOSITION_ATTACHMENT,
                'msgDownload' => Translation::get('ad_export_download'),
                'msgInlineType' => HeaderUtils::DISPOSITION_INLINE,
                'msgInline' => Translation::get('ad_export_view'),
                'buttonReset' => Translation::get('ad_config_reset'),
                'buttonExport' => Translation::get('ad_menu_export'),
            ]
        );
    }
}
