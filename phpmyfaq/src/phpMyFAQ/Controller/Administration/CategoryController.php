<?php

/**
 * The Administration Category Controller
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
 * @since     2024-12-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Category as AdminCategory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Language\CategoryLanguageService;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use Twig\Error\LoaderError;

final class CategoryController extends AbstractAdministrationController
{
    public function __construct(
        private readonly AdminCategory $adminCategory,
        private readonly Order $categoryOrder,
        private readonly CategoryPermission $categoryPermission,
        private readonly Image $categoryImage,
        private readonly Seo $seo,
        private readonly UserHelper $userHelper,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category', name: 'admin.category', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->buildCategoryTree();

        $categoryInfo = $category->getAllCategories();

        $orderedCategories = $this->categoryOrder->getAllCategories();
        $categoryTree = $this->categoryOrder->getCategoryTree($orderedCategories);

        if (in_array($categoryTree, [[], null, false], strict: true)) {
            // Fallback if no category order is available
            $categoryTree = $category->buildAdminCategoryTree($categoryInfo);
        }

        return $this->render(file: '@admin/content/category.overview.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'csrfTokenInput' => Token::getInstance($this->session)->getTokenInput(page: 'category'),
            'categoryTree' => $categoryTree,
            'categoryInfo' => $categoryInfo,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/add', name: 'admin.category.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->adminCategory->setUser($currentAdminUser);
        $this->adminCategory->setGroups($currentAdminGroups);
        $this->adminCategory->setLanguage($this->configuration->getLanguage()->getLanguage());
        $this->adminCategory->loadCategories();

        return $this->render(file: '@admin/content/category.add.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'csrfTokenInput' => Token::getInstance($this->session)->getTokenInput(page: 'save-category'),
            'faqLangCode' => $this->configuration->getLanguage()->getLanguage(),
            'parentId' => 0,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/add/{parentId}/{language}', name: 'admin.category.add.child', methods: ['GET'])]
    public function addChild(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->adminCategory->setUser($currentAdminUser);
        $this->adminCategory->setGroups($currentAdminGroups);
        $this->adminCategory->setLanguage($this->configuration->getLanguage()->getLanguage());
        $this->adminCategory->loadCategories();

        $parentId = (int) Filter::filterVar($request->attributes->get(key: 'parentId'), FILTER_VALIDATE_INT);

        $templateVars = [];
        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $templateVars = [
                'groupsOptions' => $this->currentUser?->perm->getAllGroupsOptions([], $this->currentUser),
            ];
        }

        return $this->render(file: '@admin/content/category.add.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'faqLangCode' => $this->configuration->getLanguage()->getLanguage(),
            'parentId' => $parentId,
            'categoryNameLangCode' => LanguageCodes::get($this->adminCategory->categoryName[$parentId]['lang'] ?? 'en'),
            'userAllowed' => $this->categoryPermission->get(CategoryPermission::USER, [$parentId])[0] ?? -1,
            'groupsAllowed' => $this->categoryPermission->get(CategoryPermission::GROUP, [$parentId]),
            'categoryName' => $this->adminCategory->categoryName[$parentId]['name'],
            'msgMainCategory' => Translation::get(key: 'msgMainCategory'),
            ...$templateVars,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/create', name: 'admin.category.create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_ADD);

        $csrfToken = Filter::filterVar($request->request->get(key: 'pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken(page: 'save-category', requestToken: $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryPermission = new CategoryPermission($this->configuration);
        $seo = $this->seo;

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = (int) Filter::filterVar($request->request->get(key: 'parent_id'), FILTER_VALIDATE_INT);
        $categoryId = $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id');
        $categoryLang = Filter::filterVar($request->request->get(key: 'lang'), FILTER_SANITIZE_SPECIAL_CHARS);

        $uploadedFile = $request->files->get('image') ?? [];
        if ($uploadedFile instanceof UploadedFile) {
            $this->categoryImage->setUploadedFile($uploadedFile);
        }

        $hasUploadedImage = $uploadedFile instanceof UploadedFile;

        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setParentId($parentId)
            ->setLang($categoryLang)
            ->setName(Filter::filterVar($request->request->get(key: 'name'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterVar(
                $request->request->get(key: 'description'),
                FILTER_SANITIZE_SPECIAL_CHARS,
            ))
            ->setUserId(Filter::filterVar($request->request->get(key: 'user_id'), FILTER_VALIDATE_INT))
            ->setGroupId(Filter::filterVar($request->request->get(key: 'group_id'), FILTER_VALIDATE_INT) ?? -1)
            ->setActive((bool) Filter::filterVar($request->request->get(key: 'active'), FILTER_VALIDATE_INT))
            ->setImage($hasUploadedImage ? $this->categoryImage->getFileName($categoryId, $categoryLang) : '')
            ->setParentId($parentId)
            ->setShowHome(Filter::filterVar($request->request->get(key: 'show_home'), FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterVar($request->request->get(key: 'userpermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterVar($request->request->get(key: 'restricted_users'), FILTER_VALIDATE_INT),
                ],
            ];
        }

        if (
            'all' === Filter::filterVar($request->request->get(key: 'grouppermission'), FILTER_SANITIZE_SPECIAL_CHARS)
        ) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $restrictedGroups = $request->request->all(key: 'restricted_groups');
            $permissions += [
                'restricted_groups' => is_array($restrictedGroups)
                    ? Filter::filterArray($restrictedGroups, FILTER_VALIDATE_INT)
                    : [],
            ];
        }

        $templateVars = [
            'msgHeaderCategoryMain' => Translation::get(key: 'msgHeaderCategoryOverview'),
        ];

        if ($category->checkIfCategoryExists($categoryEntity) > 0) {
            $templateVars = [
                ...$templateVars,
                'isError' => true,
                'errorMessage' => Translation::get(key: 'ad_categ_existing'),
            ];
        }

        $categoryId = $category->create($categoryEntity);

        if ($categoryId) {
            $categoryPermission->add(CategoryPermission::USER, [$categoryId], $permissions['restricted_user']);
            $categoryPermission->add(CategoryPermission::GROUP, [$categoryId], $permissions['restricted_groups']);

            if ($hasUploadedImage) {
                try {
                    $this->categoryImage->upload();
                } catch (Throwable $exception) {
                    $templateVars = [
                        ...$templateVars,
                        'isWarning' => true,
                        'warningMessage' => $exception->getMessage(),
                    ];
                }
            }

            // Category Order entry
            $this->categoryOrder->add($categoryId, $parentId);

            // SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setSeoType(SeoType::CATEGORY)
                ->setReferenceId($categoryId)
                ->setReferenceLanguage($categoryLang)
                ->setTitle(Filter::filterVar($request->request->get(key: 'serpTitle'), FILTER_SANITIZE_SPECIAL_CHARS))
                ->setDescription(Filter::filterVar(
                    $request->request->get(key: 'serpDescription'),
                    FILTER_SANITIZE_SPECIAL_CHARS,
                ));
            $seo->create($seoEntity);

            // Admin Log
            $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_ADD->value . ':' . $categoryId);

            $templateVars = [
                ...$templateVars,
                'isSuccess' => true,
                'successMessage' => Translation::get(key: 'ad_categ_added'),
            ];
        } else {
            $templateVars = [
                ...$templateVars,
                'isError' => true,
                'errorMessage' => $this->configuration->getDb()->error(),
            ];
        }

        return $this->render(file: '@admin/content/category.main.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            ...$templateVars,
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/edit/{categoryId}', name: 'admin.category.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryId = (int) Filter::filterVar(
            $request->attributes->get(key: 'categoryId'),
            FILTER_VALIDATE_INT,
            default: 0,
        );

        $category = new Category($this->configuration, [], withPermission: false);
        $category
            ->setUser($currentAdminUser)
            ->setGroups($currentAdminGroups)
            ->setLanguage($this->configuration->getLanguage()->getLanguage());

        $categoryEntity = $category->getCategoryData($categoryId);

        $seoEntity = new SeoEntity();
        $seoEntity->setSeoType(SeoType::CATEGORY);
        $seoEntity->setReferenceId($categoryId);
        $seoEntity->setReferenceLanguage($categoryEntity->getLang());

        $seoData = $this->seo->get($seoEntity);

        $userPermission = $this->categoryPermission->get(CategoryPermission::USER, [$categoryId]);
        if ($userPermission[0] === -1) {
            $allUsers = true;
            $restrictedUsers = false;
        } else {
            $allUsers = false;
            $restrictedUsers = true;
        }

        $groupPermission = $this->categoryPermission->get(CategoryPermission::GROUP, [$categoryId]);
        if ($groupPermission[0] === -1) {
            $allGroups = true;
            $restrictedGroups = false;
        } else {
            $allGroups = false;
            $restrictedGroups = true;
        }

        $header =
            Translation::get(key: 'ad_categ_edit_1')
            . ' "'
            . $categoryEntity->getName()
            . '" '
            . Translation::get(key: 'ad_categ_edit_2');

        $allGroupsOptions = '';
        $restrictedGroupOptions = '';
        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $allGroupsOptions = $this->currentUser?->perm->getAllGroupsOptions(
                [$categoryEntity->getGroupId()],
                $this->currentUser,
            );
            $restrictedGroupOptions = $this->currentUser->perm->getAllGroupsOptions(
                $groupPermission,
                $this->currentUser,
            );
        }

        return $this->render(file: '@admin/content/category.edit.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'header' => $header,
            'categoryId' => $categoryId,
            'categoryLanguage' => $categoryEntity->getLang(),
            'parentId' => $categoryEntity->getParentId(),
            'csrfInputToken' => Token::getInstance($this->session)->getTokenInput(page: 'update-category'),
            'categoryImage' => $categoryEntity->getImage(),
            'categoryName' => $categoryEntity->getName(),
            'categoryDescription' => $categoryEntity->getDescription(),
            'categoryActive' => 1 === (int) $categoryEntity->getActive() ? 'checked' : '',
            'categoryShowHome' => 1 === (int) $categoryEntity->getShowHome() ? 'checked' : '',
            'categoryImageReset' => Translation::get(key: 'msgCategoryImageReset'),
            'userSelection' => $this->userHelper->getAllUsersForTemplate($categoryEntity->getUserId()),
            'isMediumPermission' => $this->configuration->get(item: 'security.permLevel') !== 'basic',
            'allGroupsOptions' => $allGroupsOptions,
            'allGroups' => $allGroups ? 'checked' : '',
            'restrictedGroups' => $restrictedGroups ? 'checked' : '',
            'restrictedGroupsLabel' => Translation::get(key: 'ad_entry_restricted_groups'),
            'restrictedGroupsOptions' => $restrictedGroupOptions,
            'userPermissionLabel' => Translation::get(key: 'ad_entry_userpermission'),
            'allUsers' => $allUsers ? 'checked' : '',
            'restrictedUsers' => $restrictedUsers ? 'checked' : '',
            'restrictedUsersLabel' => Translation::get(key: 'ad_entry_restricted_users'),
            'serpTitle' => $seoData->getTitle(),
            'serpDescription' => $seoData->getDescription(),
            'buttonCancel' => Translation::get(key: 'ad_gen_cancel'),
            'buttonUpdate' => Translation::get(key: 'ad_gen_save'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/hierarchy', name: 'admin.category.hierarchy', methods: ['GET'])]
    public function hierarchy(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $category->getMissingCategories();
        $category->buildCategoryTree();

        $currentLangCode = $this->configuration->getLanguage()->getLanguage();
        $currentLanguage = LanguageCodes::get($currentLangCode);

        // get languages in use for all categories via service
        $categoryLanguageService = new CategoryLanguageService();
        $languages = $categoryLanguageService->getLanguagesInUse($this->configuration); // [code => name]

        $translations = [];
        foreach ($category->getCategoryTree() as $cat) {
            $existing = $categoryLanguageService->getExistingTranslations($this->configuration, (int) $cat['id']); // [code => name]
            $translations[$cat['id']] = array_keys($existing);
        }

        // Build language codes list: current first
        $languageCodes = [$currentLangCode];
        foreach (array_keys($languages) as $code) {
            if ($code === $currentLangCode) {
                continue;
            }

            $languageCodes[] = $code;
        }

        return $this->render(file: '@admin/content/category.hierarchy.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'currentLanguage' => $currentLanguage,
            'allLangs' => $languages,
            'allLangCodes' => $languageCodes,
            'categoryTree' => $category->getCategoryTree(),
            'basePath' => $request->getBasePath(),
            'faqlangcode' => $currentLangCode,
            'msgCategoryRemark_overview' => Translation::get(key: 'msgCategoryRemark_overview'),
            'categoryNameLabel' => Translation::get(key: 'categoryNameLabel'),
            'ad_categ_translate' => Translation::get(key: 'ad_categ_translate'),
            'ad_menu_categ_structure' => Translation::get(key: 'ad_menu_categ_structure'),
            'msgAddCategory' => Translation::get(key: 'msgAddCategory'),
            'msgHeaderCategoryOverview' => Translation::get(key: 'msgHeaderCategoryOverview'),
            'msgCategory' => Translation::get(key: 'msgCategory'),
            'translations' => $translations,
            'ad_categ_translated' => Translation::get(key: 'ad_categ_translated'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/translate/{categoryId}', name: 'admin.category.translate', methods: ['GET'])]
    public function translate(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryPermission = new CategoryPermission($this->configuration);
        $userHelper = new UserHelper($this->currentUser);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryId = (int) Filter::filterVar($request->attributes->get(key: 'categoryId'), FILTER_VALIDATE_INT);
        $translateTo = Filter::filterVar($request->query->get(key: 'translateTo'), FILTER_SANITIZE_SPECIAL_CHARS);

        // Re-add permission arrays used in the template
        $userPermission = $this->categoryPermission->get(CategoryPermission::USER, [$categoryId]);
        $groupPermission = $this->categoryPermission->get(CategoryPermission::GROUP, [$categoryId]);

        // Prepare language selection data via service
        $categoryLanguageService = new CategoryLanguageService();
        $toTranslate = $categoryLanguageService->getLanguagesToTranslate($this->configuration, $categoryId);

        return $this->render(file: '@admin/content/category.translate.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'categoryName' => $category->getCategoryName($categoryId),
            'ad_categ_trans_1' => Translation::get(key: 'ad_categ_trans_1'),
            'ad_categ_trans_2' => Translation::get(key: 'ad_categ_trans_2'),
            'categoryId' => $categoryId,
            'category' => $category->getCategoryName($categoryId),
            'permLevel' => $this->configuration->get(item: 'security.permLevel'),
            'groupPermission' => $groupPermission[0] ?? -1,
            'userPermission' => $userPermission[0] ?? -1,
            'csrfInputToken' => Token::getInstance($this->session)->getTokenInput(page: 'update-category'),
            'categoryNameLabel' => Translation::get(key: 'categoryNameLabel'),
            'ad_categ_lang' => Translation::get(key: 'ad_categ_lang'),
            'languagesToTranslate' => $toTranslate,
            'selectedLanguage' => $translateTo ?? '',
            'categoryDescriptionLabel' => Translation::get(key: 'categoryDescriptionLabel'),
            'categoryOwnerLabel' => Translation::get(key: 'categoryOwnerLabel'),
            'userSelection' => $userHelper->getAllUsersForTemplate($category->getOwner($categoryId)),
            'ad_categ_transalready' => Translation::get(key: 'ad_categ_transalready'),
            'langTranslated' => $category->getCategoryLanguagesTranslated($categoryId),
            'ad_categ_translatecateg' => Translation::get(key: 'ad_categ_translatecateg'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/category/update', name: 'admin.category.update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $csrfToken = Filter::filterVar($request->request->get(key: 'pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken(page: 'update-category', requestToken: $csrfToken)) {
            throw new Exception(message: 'Invalid CSRF token');
        }

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryPermission = new CategoryPermission($this->configuration);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = (int) Filter::filterVar($request->request->get(key: 'parent_id'), FILTER_VALIDATE_INT);
        $categoryId = (int) Filter::filterVar($request->request->get(key: 'id'), FILTER_VALIDATE_INT);
        $categoryLang = Filter::filterVar($request->request->get(key: 'catlang'), FILTER_SANITIZE_SPECIAL_CHARS);
        $existingImage = Filter::filterVar(
            $request->request->get(key: 'existing_image'),
            FILTER_SANITIZE_SPECIAL_CHARS,
        );

        $uploadedFile = $request->files->get(key: 'image') ?? [];
        if ($uploadedFile instanceof UploadedFile) {
            $this->categoryImage->setUploadedFile($uploadedFile);
        }

        $existingImage = is_null($existingImage) ? '' : $existingImage;
        $hasUploadedImage = $uploadedFile instanceof UploadedFile;
        $image = $hasUploadedImage ? $this->categoryImage->getFileName($categoryId, $categoryLang) : $existingImage;

        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setId($categoryId)
            ->setLang($categoryLang)
            ->setParentId($parentId)
            ->setName(Filter::filterVar($request->request->get(key: 'name'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterVar(
                $request->request->get(key: 'description'),
                FILTER_SANITIZE_SPECIAL_CHARS,
            ))
            ->setUserId((int) Filter::filterVar($request->request->get(key: 'user_id'), FILTER_VALIDATE_INT))
            ->setGroupId((int) Filter::filterVar($request->request->get(key: 'group_id'), FILTER_VALIDATE_INT) ?? -1)
            ->setActive((bool) Filter::filterVar($request->request->get(key: 'active'), FILTER_VALIDATE_INT))
            ->setImage($image)
            ->setShowHome((bool) Filter::filterVar($request->request->get(key: 'show_home'), FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterVar($request->request->get(key: 'userpermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterVar($request->request->get(key: 'restricted_users'), FILTER_VALIDATE_INT),
                ],
            ];
        }

        if (
            'all' === Filter::filterVar($request->request->get(key: 'grouppermission'), FILTER_SANITIZE_SPECIAL_CHARS)
        ) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $restrictedGroups = $request->request->all(key: 'restricted_groups');
            $permissions += [
                'restricted_groups' => is_array($restrictedGroups)
                    ? Filter::filterArray($restrictedGroups, FILTER_VALIDATE_INT)
                    : [],
            ];
        }

        $templateVars = [
            'msgHeaderCategoryMain' => Translation::get(key: 'msgHeaderCategoryOverview'),
        ];

        if (!$category->hasLanguage($categoryEntity->getId(), $categoryEntity->getLang())) {
            if (
                $category->create($categoryEntity)
                && $categoryPermission->add(
                    CategoryPermission::USER,
                    [$categoryEntity->getId()],
                    $permissions['restricted_user'],
                )
                && $categoryPermission->add(
                    CategoryPermission::GROUP,
                    [$categoryEntity->getId()],
                    $permissions['restricted_groups'],
                )
            ) {
                // Add SERP-Title and Description to the translated category
                $seoEntity = new SeoEntity();
                $seoEntity
                    ->setSeoType(SeoType::CATEGORY)
                    ->setReferenceId($categoryEntity->getId())
                    ->setReferenceLanguage($categoryEntity->getLang())
                    ->setTitle(Filter::filterVar($request->request->get('serpTitle'), FILTER_SANITIZE_SPECIAL_CHARS))
                    ->setDescription(Filter::filterVar(
                        $request->request->get('serpDescription'),
                        FILTER_SANITIZE_SPECIAL_CHARS,
                    ));

                if ($this->seo->get($seoEntity)->getId() === null) {
                    $this->seo->create($seoEntity);
                } else {
                    $this->seo->update($seoEntity);
                }

                $templateVars = [
                    ...$templateVars,
                    'isSuccess' => true,
                    'successMessage' => Translation::get(key: 'ad_categ_translated'),
                ];
            } else {
                $templateVars = [
                    ...$templateVars,
                    'isError' => true,
                    'errorMessage' => $this->configuration->getDb()->error(),
                ];
            }
        } elseif ($category->update($categoryEntity)) {
            $categoryPermission->delete(CategoryPermission::USER, [$categoryEntity->getId()]);
            $categoryPermission->delete(CategoryPermission::GROUP, [$categoryEntity->getId()]);
            $categoryPermission->add(
                CategoryPermission::USER,
                [$categoryEntity->getId()],
                $permissions['restricted_user'],
            );
            $categoryPermission->add(
                CategoryPermission::GROUP,
                [$categoryEntity->getId()],
                $permissions['restricted_groups'],
            );

            if ($hasUploadedImage) {
                try {
                    $this->categoryImage->upload();
                } catch (Throwable $exception) {
                    $templateVars = [
                        ...$templateVars,
                        'isWarning' => true,
                        'warningMessage' => $exception->getMessage(),
                    ];
                }
            }

            // SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setSeoType(SeoType::CATEGORY)
                ->setReferenceId($categoryId)
                ->setReferenceLanguage($categoryLang)
                ->setTitle(Filter::filterVar($request->request->get('serpTitle'), FILTER_SANITIZE_SPECIAL_CHARS))
                ->setDescription(Filter::filterVar(
                    $request->request->get('serpDescription'),
                    FILTER_SANITIZE_SPECIAL_CHARS,
                ));

            if ($this->seo->get($seoEntity)->getId() === null) {
                $this->seo->create($seoEntity);
            } else {
                $this->seo->update($seoEntity);
            }

            // Admin Log
            $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_EDIT->value . ':' . $categoryId);

            $templateVars = [
                ...$templateVars,
                'isSuccess' => true,
                'successMessage' => Translation::get(key: 'ad_categ_updated'),
            ];
        } else {
            $templateVars = [
                ...$templateVars,
                'isError' => true,
                'errorMessage' => $this->configuration->getDb()->error(),
            ];
        }

        return $this->render(file: '@admin/content/category.main.twig', context: [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            ...$templateVars,
        ]);
    }

    /**
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        return [
            'csrfTokenInput' => Token::getInstance($this->session)->getTokenInput(page: 'save-category'),
            'userSelection' => $this->userHelper->getAllUsersForTemplate(),
            'permLevel' => $this->configuration->get(item: 'security.permLevel'),
            'msgAccessAllUsers' => Translation::get(key: 'msgAccessAllUsers'),
            'ad_entry_restricted_users' => Translation::get(key: 'ad_entry_restricted_users'),
            'ad_entry_userpermission' => Translation::get(key: 'ad_entry_userpermission'),
            'ad_categ_add' => Translation::get(key: 'ad_categ_add'),
            'ad_entry_restricted_groups' => Translation::get(key: 'ad_entry_restricted_groups'),
            'restricted_groups' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser?->perm->getAllGroupsOptions([], $this->currentUser)
                : '',
            'buttonCancel' => Translation::get(key: 'ad_gen_cancel'),
        ];
    }
}
