<?php

/**
 * List all categories in the admin section.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-12-20
 */

use phpMyFAQ\Category;
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Filter;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = $container->get('phpmyfaq.configuration');
$currentUser = CurrentUser::getCurrentUser($faqConfig);

$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

//
// Image upload
//
$request = Request::createFromGlobals();
$uploadedFile = $request->files->get('image') ?? [];
$categoryImage = new Image($faqConfig);
if ($uploadedFile instanceof UploadedFile) {
    $categoryImage->setUploadedFile($uploadedFile);
}

$categoryPermission = new Permission($faqConfig);
$seo = new Seo($faqConfig);

if ($currentUser->perm->hasPermission($currentUser->getUserId(), PermissionType::CATEGORY_EDIT->value)) {
    $templateVars = [
        'msgHeaderCategoryMain' => Translation::get('msgHeaderCategoryOverview'),
    ];

    // Save a new category
    if (
        $action === 'savecategory' &&
        Token::getInstance($container->get('session'))->verifyToken('save-category', $csrfToken)
    ) {
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = $faqConfig->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id');
        $categoryLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);

        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setParentId($parentId)
            ->setLang($categoryLang)
            ->setName(Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserId(Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT))
            ->setGroupId(Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT) ?? -1)
            ->setActive(Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT) ?? false)
            ->setImage($categoryImage->getFileName($categoryId, $categoryLang))
            ->setParentId($parentId)
            ->setShowHome(Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
                ],
            ];
        }

        if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $permissions += Filter::filterInputArray(
                INPUT_POST,
                [
                    'restricted_groups' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ],
                ]
            );
        }

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
            $categoryOrder = new Order($faqConfig);
            $categoryOrder->add($categoryId, $parentId);

            // All the other translations
            $languages = Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_SPECIAL_CHARS);

            // SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setType(SeoType::CATEGORY)
                ->setReferenceId($categoryId)
                ->setReferenceLanguage($categoryLang)
                ->setTitle(Filter::filterInput(INPUT_POST, 'serpTitle', FILTER_SANITIZE_SPECIAL_CHARS))
                ->setDescription(Filter::filterInput(INPUT_POST, 'serpDescription', FILTER_SANITIZE_SPECIAL_CHARS));
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
                'errorMessage' => $faqConfig->getDb()->error(),
            ];
        }
    }

    // Updates an existing category
    if (
        $action === 'updatecategory' &&
        Token::getInstance($container->get('session'))->verifyToken('update-category', $csrfToken)
    ) {
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $categoryLang = Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_SPECIAL_CHARS);
        $existingImage = Filter::filterInput(INPUT_POST, 'existing_image', FILTER_SANITIZE_SPECIAL_CHARS);
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
            ->setName(Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserId(Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT))
            ->setGroupId(Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT))
            ->setActive(Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT))
            ->setImage($image)
            ->setShowHome(Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT));

        $permissions = [];
        if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_user' => [
                    -1,
                ],
            ];
        } else {
            $permissions += [
                'restricted_user' => [
                    Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
                ],
            ];
        }

        if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_SPECIAL_CHARS)) {
            $permissions += [
                'restricted_groups' => [
                    -1,
                ],
            ];
        } else {
            $permissions += Filter::filterInputArray(
                INPUT_POST,
                [
                    'restricted_groups' => [
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ],
                ]
            );
        }

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
                    'errorMessage' => $faqConfig->getDb()->error(),
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
                    'errorMessage' => $faqConfig->getDb()->error(),
                ];
            }
        }
    }

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/content/category.main.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
