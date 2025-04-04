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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-04
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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
        $categoryTree = $categoryOrder->getCategoryTree($categoryInfo);
       

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
                'categoryNameLangCode' => LanguageCodes::get($category->categoryName[$parentId]['lang'] ?? 'en'),
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

        $csrfToken = Filter::filterVar($request->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get('session'))->verifyToken('save-category', $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryPermission = new Permission($this->configuration);
        $seo = $this->container->get('phpmyfaq.seo');

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = Filter::filterVar($request->get('parent_id'), FILTER_VALIDATE_INT);
        $categoryId = $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id');
        $categoryLang = Filter::filterVar($request->get('lang'), FILTER_SANITIZE_SPECIAL_CHARS);

        $uploadedFile = $request->files->get('image') ?? [];
        $categoryImage = $this->container->get('phpmyfaq.category.image');
        if ($uploadedFile instanceof UploadedFile) {
            $categoryImage->setUploadedFile($uploadedFile);
        }

        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setParentId($parentId)
            ->setLang($categoryLang)
            ->setName(Filter::filterVar($request->get('name'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterVar($request->get('description'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserId(Filter::filterVar($request->get('user_id'), FILTER_VALIDATE_INT))
            ->setGroupId(Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT) ?? -1)
            ->setActive((bool) Filter::filterVar($request->get('active'), FILTER_VALIDATE_INT))
            ->setImage($categoryImage->getFileName($categoryId, $categoryLang))
            ->setParentId($parentId)
            ->setShowHome(Filter::filterVar($request->get('show_home'), FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterVar($request->get('userpermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterVar($request->get('restricted_users'), FILTER_VALIDATE_INT),
                ],
            ];
        }

        if ('all' === Filter::filterVar($request->get('grouppermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $permissions += Filter::filterArray(
                [
                    'restricted_groups' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ],
                ]
            );
        }

        $templateVars = [
            'msgHeaderCategoryMain' => Translation::get('msgHeaderCategoryOverview'),
        ];

        if ($category->checkIfCategoryExists($categoryEntity) > 0) {
            $templateVars = [
                ...$templateVars,
                'isError' => true,
                'errorMessage' => Translation::get('ad_categ_existing'),
            ];
        }

        $categoryId = $category->create($categoryEntity);

        if ($categoryId) {
            $categoryPermission->add(Permission::USER, [$categoryId], $permissions['restricted_user']);
            $categoryPermission->add(
                Permission::GROUP,
                [$categoryId],
                $permissions['restricted_groups']
            );

            if ($categoryImage->getFileName($categoryId, $categoryLang)) {
                try {
                    $categoryImage->upload();
                } catch (Exception $exception) {
                    $templateVars = [
                        ...$templateVars,
                        'isWarning' => true,
                        'warningMessage' => $exception->getMessage(),
                    ];
                }
            }

            // Category Order entry
            $categoryOrder = $this->container->get('phpmyfaq.category.order');
            $categoryOrder->add($categoryId, $parentId);

            // SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setType(SeoType::CATEGORY)
                ->setReferenceId($categoryId)
                ->setReferenceLanguage($categoryLang)
                ->setTitle(Filter::filterVar($request->get('serpTitle'), FILTER_SANITIZE_SPECIAL_CHARS))
                ->setDescription(Filter::filterVar($request->get('serpDescription'), FILTER_SANITIZE_SPECIAL_CHARS));
            $seo->create($seoEntity);

            $templateVars = [
                ...$templateVars,
                'isSuccess' => true,
                'successMessage' => Translation::get('ad_categ_added')
            ];
        } else {
            $templateVars = [
                ...$templateVars,
                'isError' => true,
                'errorMessage' => $this->configuration->getDb()->error(),
            ];
        }

        return $this->render(
            '@admin/content/category.main.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                ...$templateVars,
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
                'categoryImageReset' => Translation::get('msgCategoryImageReset'),
                'categoryOwnerOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
                'isMediumPermission' => $this->configuration->get('security.permLevel') !== 'basic',
                'allGroupsOptions' => $allGroupsOptions,
                'allGroups' => $allGroups ? 'checked' : '',
                'restrictedGroups' => $restrictedGroups ? 'checked' : '',
                'restrictedGroupsLabel' => Translation::get('ad_entry_restricted_groups'),
                'restrictedGroupsOptions' => $restrictedGroupOptions,
                'userPermissionLabel' => Translation::get('ad_entry_userpermission'),
                'allUsers' => $allUsers ? 'checked' : '',
                'restrictedUsers' => $restrictedUsers ? 'checked' : '',
                'restrictedUsersLabel' => Translation::get('ad_entry_restricted_users'),
                'allUsersOptions' => $userHelper->getAllUserOptions($categoryData->getUserId()),
                'serpTitle' => $seoData->getTitle(),
                'serpDescription' => $seoData->getDescription(),
                'buttonUpdate' => Translation::get('ad_gen_save'),
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/hierarchy', name: 'admin.category.hierarchy', methods: ['GET'])]
    public function hierarchy(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $category->getMissingCategories();
        $category->buildCategoryTree();

        $currentLangCode = $this->configuration->getLanguage()->getLanguage();
        $currentLanguage = LanguageCodes::get($currentLangCode);

        // get languages in use for all categories
        $allLanguages = $this->configuration->getLanguage()->isLanguageAvailable(0, 'faqcategories');
        $languages = [];
        foreach ($allLanguages as $lang) {
            $languages[$lang] = LanguageCodes::get($lang);
        }
        asort($languages);

        $translations = [];

        foreach ($category->getCategoryTree() as $cat) {
            $languageIds = $category->getCategoryLanguagesTranslated((int) $cat['id']);
            $translationArray = [];
            foreach ($languageIds as $lang => $title) {
                $translationArray[] = $lang;
            }
            $translations[$cat['id']] = $translationArray;
        }

        $languageCodes = [LanguageCodes::getKey($currentLanguage)];
        foreach ($languages as $language) {
            if ($language !== $currentLanguage) {
                $languageCodes[] = LanguageCodes::getKey($language);
            }
        }

        return $this->render(
            '@admin/content/category.hierarchy.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'currentLanguage' => $currentLanguage,
                'allLangs' => $languages,
                'allLangCodes' => $languageCodes,
                'categoryTree' => $category->getCategoryTree(),
                'basePath' => $request->getBasePath(),
                'faqlangcode' => $currentLangCode,
                'msgCategoryRemark_overview' => Translation::get('msgCategoryRemark_overview'),
                'categoryNameLabel' => Translation::get('categoryNameLabel'),
                'ad_categ_translate' => Translation::get('ad_categ_translate'),
                'ad_menu_categ_structure' => Translation::get('ad_menu_categ_structure'),
                'msgAddCategory' => Translation::get('msgAddCategory'),
                'msgHeaderCategoryOverview' => Translation::get('msgHeaderCategoryOverview'),
                'msgCategory' => Translation::get('msgCategory'),
                'translations' => $translations,
                'ad_categ_translated' => Translation::get('ad_categ_translated')
            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/translate/:categoryId', name: 'admin.category.translate', methods: ['GET'])]
    public function translate(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $session = $this->container->get('session');

        $categoryPermission = new Permission($this->configuration);
        $userHelper = new UserHelper($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
        $translateTo = Filter::filterVar($request->query->get('translateTo'), FILTER_SANITIZE_SPECIAL_CHARS);

        $userPermission = $categoryPermission->get(Permission::USER, [$categoryId]);
        $groupPermission = $categoryPermission->get(Permission::GROUP, [$categoryId]);

        return $this->render(
            '@admin/content/category.translate.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'categoryName' => $category->categoryName[$categoryId]['name'],
                'ad_categ_trans_1' => Translation::get('ad_categ_trans_1'),
                'ad_categ_trans_2' => Translation::get('ad_categ_trans_2'),
                'categoryId' => $categoryId,
                'category' => $category->categoryName[$categoryId],
                'permLevel' => $this->configuration->get('security.permLevel'),
                'groupPermission' => $groupPermission[0],
                'userPermission' => $userPermission[0],
                'csrfInputToken' => Token::getInstance($session)->getTokenInput('update-category'),
                'categoryNameLabel' => Translation::get('categoryNameLabel'),
                'ad_categ_lang' => Translation::get('ad_categ_lang'),
                'langToTranslate' => $category->getCategoryLanguagesToTranslate($categoryId, $translateTo),
                'categoryDescriptionLabel' => Translation::get('categoryDescriptionLabel'),
                'categoryOwnerLabel' => Translation::get('categoryOwnerLabel'),
                'userOptions' => $userHelper->getAllUserOptions((int) $category->categoryName[$categoryId]['user_id']),
                'ad_categ_transalready' => Translation::get('ad_categ_transalready'),
                'langTranslated' => $category->getCategoryLanguagesTranslated($categoryId),
                'ad_categ_translatecateg' => Translation::get('ad_categ_translatecateg')

            ],
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/category/update', name: 'admin.category.update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $csrfToken = Filter::filterVar($request->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->container->get('session'))->verifyToken('update-category', $csrfToken)) {
            throw new Exception('Invalid CSRF token');
        }

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryPermission = new Permission($this->configuration);
        $seo = $this->container->get('phpmyfaq.seo');

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = Filter::filterVar($request->get('parent_id'), FILTER_VALIDATE_INT);
        $categoryId = Filter::filterVar($request->get('id'), FILTER_VALIDATE_INT);
        $categoryLang = Filter::filterVar($request->get('catlang'), FILTER_SANITIZE_SPECIAL_CHARS);
        $existingImage = Filter::filterVar($request->get('existing_image'), FILTER_SANITIZE_SPECIAL_CHARS);

        $uploadedFile = $request->files->get('image') ?? [];
        $categoryImage = $this->container->get('phpmyfaq.category.image');
        if ($uploadedFile instanceof UploadedFile) {
            $categoryImage->setUploadedFile($uploadedFile);
        }

        $existingImage = is_null($existingImage) ? '' : $existingImage;
        $image = count($uploadedFile) ? $categoryImage->getFileName(
            $categoryId,
            $categoryLang
        ) : $existingImage;


        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setId($categoryId)
            ->setLang($categoryLang)
            ->setParentId($parentId)
            ->setName(Filter::filterVar($request->get('name'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterVar($request->get('description'), FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserId(Filter::filterVar($request->get('user_id'), FILTER_VALIDATE_INT))
            ->setGroupId(Filter::filterVar($request->get('group_id'), FILTER_VALIDATE_INT) ?? -1)
            ->setActive((bool) Filter::filterVar($request->get('active'), FILTER_VALIDATE_INT))
            ->setImage($image)
            ->setShowHome(Filter::filterVar($request->get('show_home'), FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterVar($request->get('userpermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterVar($request->get('restricted_users'), FILTER_VALIDATE_INT),
                ],
            ];
        }

        if ('all' === Filter::filterVar($request->get('grouppermission'), FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $permissions += Filter::filterArray(
                [
                    'restricted_groups' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ],
                ]
            );
        }

        $templateVars = [
            'msgHeaderCategoryMain' => Translation::get('msgHeaderCategoryOverview'),
        ];

        if (!$category->hasLanguage($categoryEntity->getId(), $categoryEntity->getLang())) {
            if (
                $category->create($categoryEntity) && $categoryPermission->add(
                    Permission::USER,
                    [$categoryEntity->getId()],
                    $permissions['restricted_user']
                ) && $categoryPermission->add(
                    Permission::GROUP,
                    [$categoryEntity->getId()],
                    $permissions['restricted_groups']
                )
            ) {
                // Add SERP-Title and Description to translated category
                $seoEntity = new SeoEntity();
                $seoEntity
                    ->setType(SeoType::CATEGORY)
                    ->setReferenceId($categoryEntity->getId())
                    ->setReferenceLanguage($categoryEntity->getLang())
                    ->setTitle(Filter::filterInput(INPUT_POST, 'serpTitle', FILTER_SANITIZE_SPECIAL_CHARS))
                    ->setDescription(Filter::filterInput(INPUT_POST, 'serpDescription', FILTER_SANITIZE_SPECIAL_CHARS));

                if ($seo->get(clone $seoEntity)->getId() === null) {
                    $seo->create($seoEntity);
                } else {
                    $seo->update($seoEntity);
                }

                $templateVars = [
                    ...$templateVars,
                    'isSuccess' => true,
                    'successMessage' => Translation::get('ad_categ_translated')
                ];
            } else {
                $templateVars = [
                    ...$templateVars,
                    'isError' => true,
                    'errorMessage' => $this->configuration->getDb()->error(),
                ];
            }
        } else {
            if ($category->update($categoryEntity)) {
                $categoryPermission->delete(Permission::USER, [$categoryEntity->getId()]);
                $categoryPermission->delete(Permission::GROUP, [$categoryEntity->getId()]);
                $categoryPermission->add(
                    Permission::USER,
                    [$categoryEntity->getId()],
                    $permissions['restricted_user']
                );
                $categoryPermission->add(
                    Permission::GROUP,
                    [$categoryEntity->getId()],
                    $permissions['restricted_groups']
                );

                if ($categoryImage->getFileName($categoryId, $categoryLang)) {
                    try {
                        $categoryImage->upload();
                    } catch (Exception $exception) {
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
                    ->setType(SeoType::CATEGORY)
                    ->setReferenceId($categoryId)
                    ->setReferenceLanguage($categoryLang)
                    ->setTitle(Filter::filterInput(INPUT_POST, 'serpTitle', FILTER_SANITIZE_SPECIAL_CHARS))
                    ->setDescription(Filter::filterInput(INPUT_POST, 'serpDescription', FILTER_SANITIZE_SPECIAL_CHARS));

                if ($seo->get(clone $seoEntity)->getId() === null) {
                    $seo->create($seoEntity);
                } else {
                    $seo->update($seoEntity);
                }

                $templateVars = [
                    ...$templateVars,
                    'isSuccess' => true,
                    'successMessage' => Translation::get('ad_categ_updated')
                ];
            } else {
                $templateVars = [
                    ...$templateVars,
                    'isError' => true,
                    'errorMessage' => $this->configuration->getDb()->error(),
                ];
            }
        }


        return $this->render(
            '@admin/content/category.main.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                ...$templateVars,
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
            'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
            'restricted_groups' => ($this->configuration->get('security.permLevel') === 'medium') ?
                $this->currentUser->perm->getAllGroupsOptions([], $this->currentUser) : '',
        ];
    }
}
