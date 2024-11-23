<?php

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
    #[Route('/export')]
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
                'msgCategory' => Translation::get('ad_entry_category'),
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
