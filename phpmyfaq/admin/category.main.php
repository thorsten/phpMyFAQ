<?php

/**
 * List all categories in the admin section.
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
use phpMyFAQ\Category\CategoryImage;
use phpMyFAQ\Category\CategoryOrder;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">
            <i aria-hidden="true" class="bi bi-folder"></i>
            <?= Translation::get('ad_menu_categ_edit') ?>
        </h1>
    </div>

    <div class="row">
        <div class="col-lg-12">
<?php
$csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

//
// Image upload
//
$uploadedFile = (isset($_FILES['image']['size']) && $_FILES['image']['size'] > 0) ? $_FILES['image'] : [];
$categoryImage = new CategoryImage($faqConfig);
$categoryImage->setUploadedFile($uploadedFile);

$categoryPermission = new CategoryPermission($faqConfig);

if ($user->perm->hasPermission($user->getUserId(), PermissionType::CATEGORY_EDIT->value)) {
    // Save a new category
    if ($action === 'savecategory' && Token::getInstance()->verifyToken('save-category', $csrfToken)) {
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = $faqConfig->getDb()->nextId(Database::getTablePrefix() . 'faqcategories', 'id');
        $categoryLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryData = [
            'lang' => $categoryLang,
            'name' => Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS),
            'description' => Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS),
            'user_id' => Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),
            'image' => $categoryImage->getFileName($categoryId, $categoryLang),
            'show_home' => Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT),
        ];

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

        if ($category->checkIfCategoryExists($categoryData) > 0) {
            echo Alert::danger('ad_categ_existing');
            exit();
        }

        $categoryId = $category->addCategory($categoryData, $parentId, $categoryId);

        if ($categoryId) {
            $categoryPermission->add(CategoryPermission::USER, [$categoryId], $permissions['restricted_user']);
            $categoryPermission->add(
                CategoryPermission::GROUP,
                [$categoryId],
                $permissions['restricted_groups']
            );

            if ($categoryImage->getFileName($categoryId, $categoryLang)) {
                try {
                    $categoryImage->upload();
                } catch (Exception $exception) {
                    echo Alert::warning('ad_adus_dberr', $exception->getMessage());
                }
            }

            // Category Order entry
            $categoryOrder = new CategoryOrder($faqConfig);
            $categoryOrder->add($categoryId, $parentId);

            // All the other translations
            $languages = Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_SPECIAL_CHARS);
            echo Alert::success('ad_categ_added');
        } else {
            echo Alert::danger('ad_adus_dberr', $faqConfig->getDb()->error());
        }
    }

    // Updates an existing category
    if ($action === 'updatecategory' && Token::getInstance()->verifyToken('update-category', $csrfToken)) {
        $category = new Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $categoryLang = Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_SPECIAL_CHARS);
        $existingImage = Filter::filterInput(INPUT_POST, 'existing_image', FILTER_SANITIZE_SPECIAL_CHARS);
        $image = count($uploadedFile) ? $categoryImage->getFileName(
            $categoryId,
            $categoryLang
        ) : $existingImage;

        $categoryData = [
            'id' => $categoryId,
            'lang' => $categoryLang,
            'parent_id' => $parentId,
            'name' => Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS),
            'description' => Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS),
            'user_id' => Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),
            'image' => $image,
            'show_home' => Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT),
        ];

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

        if (!$category->hasLanguage($categoryData['id'], $categoryData['lang'])) {
            if (
                $category->addCategory($categoryData, $parentId, $categoryData['id']) && $categoryPermission->add(
                    CategoryPermission::USER,
                    [$categoryData['id']],
                    $permissions['restricted_user']
                ) && $categoryPermission->add(
                    CategoryPermission::GROUP,
                    [$categoryData['id']],
                    $permissions['restricted_groups']
                )
            ) {
                echo Alert::success('ad_categ_translated');
            } else {
                echo Alert::danger('ad_adus_dberr', $faqConfig->getDb()->error());
            }
        } else {
            if ($category->update($categoryData)) {
                $categoryPermission->delete(CategoryPermission::USER, [$categoryData['id']]);
                $categoryPermission->delete(CategoryPermission::GROUP, [$categoryData['id']]);
                $categoryPermission->add(
                    CategoryPermission::USER,
                    [$categoryData['id']],
                    $permissions['restricted_user']
                );
                $categoryPermission->add(
                    CategoryPermission::GROUP,
                    [$categoryData['id']],
                    $permissions['restricted_groups']
                );

                if ($categoryImage->getFileName($categoryId, $categoryLang)) {
                    try {
                        $categoryImage->upload();
                    } catch (Exception $exception) {
                        echo Alert::warning('ad_adus_dberr', $exception->getMessage());
                    }
                }

                echo Alert::success('ad_categ_updated');
            } else {
                echo Alert::danger('ad_adus_dberr', $faqConfig->getDb()->error());
            }
        }
    }
    ?>
            <div class="d-flex justify-content-center">
                <div class="spinner-grow" role="status">
                    <span class="visually-hidden">Saving ...</span>
                </div>
            </div>
            <script>
                (() => {
                    setTimeout(() => {
                        window.location = "index.php?action=category-overview";
                    }, 5000);
                })();
            </script>
        </div>
    </div>
    <?php
} else {
    require __DIR__ . '/no-permission.php';
}
