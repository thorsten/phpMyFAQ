<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class CategoryController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category', name: 'admin.category', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $category = new Category($this->configuration, [], false);
        $category->buildCategoryTree();
        $categoryInfo = $category->getAllCategories();

        $session = $this->container->get('session');
        $categoryOrder = $this->container->get('phpmyfaq.category.order');
        $orderedCategories = $categoryOrder->getAllCategories();
        $categoryTree = $categoryOrder->getCategoryTree($orderedCategories);

        if (empty($categoryTree)) {
            // Fallback if no category order is available
            $categoryTree = $category->buildAdminCategoryTree($categoryInfo);
        }

        return $this->render(
            '@admin/content/category.overview.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'csrfTokenInput' => Token::getInstance($session)->getTokenInput('category'),
                'categoryTree' => $categoryTree,
                'categoryInfo' => $categoryInfo,
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/add', name: 'admin.category/add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = $this->container->get('phpmyfaq.admin.category');
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->setLanguage($this->configuration->getLanguage()->getLanguage());
        $category->loadCategories();

        $session = $this->container->get('session');

        return $this->render(
            '@admin/content/category.add.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                'csrfTokenInput' => Token::getInstance($session)->getTokenInput('save-category'),
                'faqLangCode' => $this->configuration->getLanguage()->getLanguage(),
                'parentId' => 0,
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/add/:parentId/:language', name: 'admin.category.add.child', methods: ['GET'])]
    public function addChild(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = $this->container->get('phpmyfaq.admin.category');
        $categoryPermission = $this->container->get('phpmyfaq.category.permission');

        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->setLanguage($this->configuration->getLanguage()->getLanguage());
        $category->loadCategories();

        $parentId = Filter::filterVar($request->get('parentId'), FILTER_VALIDATE_INT);

        $templateVars = [];
        if ($this->configuration->get('security.permLevel') !== 'basic') {
            $templateVars = [
                'groupsOptions' => $this->currentUser->perm->getAllGroupsOptions([], $this->currentUser),
            ];
        }

        return $this->render(
            '@admin/content/category.add.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                'faqLangCode' => $this->configuration->getLanguage()->getLanguage(),
                'parentId' => $parentId,
                'categoryNameLangCode' => LanguageCodes::get($category->categoryName[$parentId]['lang']),
                'userAllowed' => $categoryPermission->get(Permission::USER, [$parentId])[0],
                'groupsAllowed' => $categoryPermission->get(Permission::GROUP, [$parentId]),
                'categoryName' => $category->categoryName[$parentId]['name'],
                'msgMainCategory' => Translation::get('msgMainCategory'),
                ... $templateVars,
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/create', name: 'admin.category.create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        return $this->render(
            '@admin/content/category.overview.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/edit/:categoryId', name: 'admin.category.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT, 0);

        $session = $this->container->get('session');
        $userHelper = $this->container->get('phpmyfaq.helper.user-helper');
        $categoryPermission = $this->container->get('phpmyfaq.category.permission');

        $category = new Category($this->configuration, [], false);
        $category
            ->setUser($currentAdminUser)
            ->setGroups($currentAdminGroups)
            ->setLanguage($this->configuration->getLanguage()->getLanguage());

        $categoryData = $category->getCategoryData($categoryId);

        $seoEntity = new SeoEntity();
        $seoEntity->setType(SeoType::CATEGORY);
        $seoEntity->setReferenceId($categoryId);
        $seoEntity->setReferenceLanguage($categoryData->getLang());
        $seoData = $this->container->get('phpmyfaq.seo')->get($seoEntity);

        $userPermission = $categoryPermission->get(Permission::USER, [$categoryId]);
        if ($userPermission[0] == -1) {
            $allUsers = true;
            $restrictedUsers = false;
        } else {
            $allUsers = false;
            $restrictedUsers = true;
        }

        $groupPermission = $categoryPermission->get(Permission::GROUP, [$categoryId]);
        if ($groupPermission[0] == -1) {
            $allGroups = true;
            $restrictedGroups = false;
        } else {
            $allGroups = false;
            $restrictedGroups = true;
        }

        $header = Translation::get('ad_categ_edit_1') . ' "' . $categoryData->getName() . '" ' .
            Translation::get('ad_categ_edit_2');

        $allGroupsOptions = '';
        $restrictedGroupOptions = '';
        if ($this->configuration->get('security.permLevel') !== 'basic') {
            $allGroupsOptions = $this->currentUser->perm->getAllGroupsOptions(
                [$categoryData->getGroupId()],
                $this->currentUser
            );
            $restrictedGroupOptions = $this->currentUser->perm->getAllGroupsOptions(
                $groupPermission,
                $this->currentUser
            );
        }

        return $this->render(
            '@admin/content/category.edit.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'header' => $header,
                'categoryId' => $categoryId,
                'categoryLanguage' => $categoryData->getLang(),
                'parentId' => $categoryData->getParentId(),
                'csrfInputToken' => Token::getInstance($session)->getTokenInput('update-category'),
                'categoryImage' => $categoryData->getImage(),
                'categoryName' => $categoryData->getName(),
                'categoryDescription' => $categoryData->getDescription(),
                'categoryActive' => 1 === (int)$categoryData->getActive() ? 'checked' : '',
                'categoryShowHome' => 1 === (int)$categoryData->getShowHome() ? 'checked' : '',
                'categoryImageReset' => 'Reset category image', // @todo needs translation
                'categoryOwnerOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
                'isMediumPermission' => $this->configuration->get('security.permLevel') !== 'basic',
                'allGroupsOptions' => $allGroupsOptions,
                'categoryGroupPermissionLabel' => Translation::get('ad_entry_grouppermission'),
                'allGroups' => $allGroups ? 'checked' : '',
                'categoryGroupPermissionAllLabel' => Translation::get('ad_entry_all_groups'),
                'restrictedGroups' => $restrictedGroups ? 'checked' : '',
                'restrictedGroupsLabel' => Translation::get('ad_entry_restricted_groups'),
                'restrictedGroupsOptions' => $restrictedGroupOptions,
                'userPermissionLabel' => Translation::get('ad_entry_userpermission'),
                'allUsers' => $allUsers ? 'checked' : '',
                'restrictedUsers' => $restrictedUsers ? 'checked' : '',
                'restrictedUsersLabel' => Translation::get('ad_entry_restricted_users'),
                'allUsersOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
                'msgSerpTitle' => Translation::get('msgSerpTitle'),
                'serpTitle' => $seoData->getTitle(),
                'serpDescription' => $seoData->getDescription(),
                'buttonUpdate' => Translation::get('ad_gen_save'),
            ],
        );
    }

    /**
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        $session = $this->container->get('session');
        $userHelper = $this->container->get('phpmyfaq.helper.user-helper');

        return [
            'csrfTokenInput' => Token::getInstance($session)->getTokenInput('save-category'),
            'userOptions' => $userHelper->getAllUserOptions(),
            'permLevel' => $this->configuration->get('security.permLevel'),
            'msgAccessAllUsers' => Translation::get('msgAccessAllUsers'),
            'ad_entry_restricted_users' => Translation::get('ad_entry_restricted_users'),
            'ad_entry_userpermission' => Translation::get('ad_entry_userpermission'),
            'ad_categ_add' => Translation::get('ad_categ_add'),
            'ad_entry_grouppermission' => Translation::get('ad_entry_grouppermission'),
            'ad_entry_all_groups' => Translation::get('ad_entry_all_groups'),
            'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
            'msgSerpTitle' => Translation::get('msgSerpTitle'),
            'restricted_groups' => ($this->configuration->get('security.permLevel') === 'medium') ?
                $this->currentUser->perm->getAllGroupsOptions([], $this->currentUser) : '',
        ];
    }
}
