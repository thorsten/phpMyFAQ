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
 * @copyright 2003-2023 phpMyFAQ Team
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
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-folder"></i> <?= Translation::get('ad_menu_categ_edit') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <a class="btn btn-sm btn-success" href="?action=addcategory">
          <i aria-hidden="true" class="fa fa-folder-plus"></i> <?= Translation::get('ad_kateg_add'); ?>
        </a>
        <a class="btn btn-sm btn-info" href="?action=showcategory">
          <i aria-hidden="true" class="fa fa-list"></i> <?= Translation::get('ad_categ_show'); ?>
        </a>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-12">
      <form method="post">
        <?= Token::getInstance()->getTokenInput('category') ?>
        <?php
        $csrfToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

        //
        // Image upload
        //
        $uploadedFile = (isset($_FILES['image']['size']) && $_FILES['image']['size'] > 0) ? $_FILES['image'] : [];
        $categoryImage = new CategoryImage($faqConfig);
        $categoryImage->setUploadedFile($uploadedFile);

        $categoryPermission = new CategoryPermission($faqConfig);

        if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
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
                    'show_home' => Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT)
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
                    $categoryOrder->add($categoryId);

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

                if (!$category->checkLanguage($categoryData['id'], $categoryData['lang'])) {
                    if (
                        $category->addCategory($categoryData, $parentId, $categoryData['id']) &&
                        $categoryPermission->add(
                            CategoryPermission::USER,
                            [$categoryData['id']],
                            $permissions['restricted_user']
                        ) &&
                        $categoryPermission->add(
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

                // All the other translations
                $languages = Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_SPECIAL_CHARS);
            }

            // Deletes an existing category
            if (
                $user->perm->hasPermission($user->getUserId(), 'delcateg') && $action === 'removecategory' &&
                Token::getInstance()->verifyToken('remove-category', $csrfToken)
            ) {
                $categoryId = Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
                $categoryLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);

                $category = new Category($faqConfig, [], false);
                $category->setUser($currentAdminUser);
                $category->setGroups($currentAdminGroups);

                $categoryRelation = new CategoryRelation($faqConfig, $category);

                $categoryImage = new CategoryImage($faqConfig);
                $categoryImage->setFileName($category->getCategoryData($categoryId)->getImage());

                if ((is_countable($category->getCategoryLanguagesTranslated($categoryId)) ? count($category->getCategoryLanguagesTranslated($categoryId)) : 0) === 1) {
                    $categoryPermission->delete(CategoryPermission::USER, [$categoryId]);
                    $categoryPermission->delete(CategoryPermission::GROUP, [$categoryId]);
                    $categoryImage->delete();
                }

                if (
                    $category->deleteCategory($categoryId, $categoryLang) &&
                    $categoryRelation->delete($categoryId, $categoryLang)
                ) {
                    echo Alert::success('ad_categ_deleted');
                } else {
                    echo Alert::danger('ad_adus_dberr', $faqConfig->getDb()->error());
                }
            }

            // Pastes a category
            if ($action == 'pastecategory') {
                $category = new Category($faqConfig, [], false);
                $category->setUser($currentAdminUser);
                $category->setGroups($currentAdminGroups);
                $categoryId = Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
                $parentId = Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
                if ($category->updateParentCategory((int) $categoryId, (int) $parentId)) {
                    echo Alert::success('ad_categ_updated');
                } else {
                    echo Alert::danger('ad_categ_paste_error', $faqConfig->getDb()->error());
                }
            }

            // Lists all categories
            $lang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS, $faqLangCode);

            // If we changed the category tree, unset the object
            if (isset($category)) {
                unset($category);
            }

            $category = new Category($faqConfig, $currentAdminGroups, true);
            $category->setUser($currentAdminUser);
            $category->setGroups($currentAdminGroups);
            $category->getMissingCategories();
            $category->buildCategoryTree();

            $open = $lastCatId = $openDiv = 0;

            echo '<div class="list-group list-group-root">';
            foreach ($category->getCategoryTree() as $cat) {
                // CategoryHelper translated in this language?
                if ($cat['lang'] == $lang) {
                    $categoryName = Strings::htmlentities($cat['name']);
                } else {
                    $categoryName = Strings::htmlentities($cat['name']) . ' (' . LanguageCodes::get($cat['lang']) . ')';
                }


                // Has permissions, show lock icon
                if ($categoryPermission->isRestricted($cat['id'])) {
                    $categoryName .= ' <i class="fa fa-lock" aria-hidden="true"></i>';
                }

                   // Category is shown on start page
                if (isset($cat['show_home']) && (int)$cat['show_home'] === 1) {
                    $categoryName .= ' <i class="fa fa-star" aria-hidden="true"></i>';
                }

                // Category is inactive
                if (isset($cat['active']) && (int)$cat['active'] === 0) {
                    $categoryName .= ' <i class="fa fa-eye-slash" aria-hidden="true"></i>';
                }

                // Level of the category
                $level = $cat['indent'];

                // Any sub-categories?
                $subCategories = $category->getChildren($cat['id']);
                $numSubCategories = is_countable($subCategories) ? count($subCategories) : 0;

                $hasParent = (bool) $cat['parent_id'];

                if ($hasParent) {
                    printf(
                        '<div class="list-group collapse pmf-category-not-sortable" id="category-id-%d">',
                        $cat['parent_id']
                    );
                }

                printf(
                    '<div href="#category-id-%d" id="%s-%d" data-id="%d" class="list-group-item list-group-item-action border-left-0 border-right-0 d-flex justify-content-between align-items-center %s" %s>',
                    $cat['id'],
                    trim(strip_tags($categoryName)),
                    $cat['id'],
                    $cat['id'],
                    $numSubCategories > 0 ? ' pmf-has-subcategories' : '',
                    $numSubCategories > 0 ? 'data-bs-toggle="collapse"' : ''
                );
                printf(
                    '<span>%s %s</span>',
                    $numSubCategories > 0 ? '<i class="fa fa-caret-right pmf-has-subcategories"></i>' : '',
                    $categoryName
                );

                // Buttons:
                echo '<span>';
                // Add FAQ to category (always)
                printf(
                    '<a class="btn btn-info btn-sm" href="?action=editentry&amp;cat=%s&amp;lang=%s"><i aria-hidden="true" class="fa fa-indent" title="%s"></i></a></a> ',
                    $cat['id'],
                    $cat['lang'],
                    Translation::get('ad_quick_entry')
                );

                if ($cat['lang'] == $lang) {
                    // add sub category (if current language)
                    printf(
                        '<a class="btn btn-info btn-sm" href="?action=addcategory&amp;cat=%s&amp;lang=%s"><i aria-hidden="true" class="fa fa-plus-square" title="%s"></i></a> ',
                        $cat['id'],
                        $cat['lang'],
                        Translation::get('ad_quick_category')
                    );

                    // rename (sub) category (if current language)
                    printf(
                        '<a class="btn btn-info btn-sm" href="?action=editcategory&amp;cat=%s"><i aria-hidden="true" class="fa fa-edit" title="%s"></i></a> ',
                        $cat['id'],
                        Translation::get('ad_kateg_rename')
                    );
                }

                // translate category (always)
                printf(
                    '<a class="btn btn-info btn-sm" href="?action=translatecategory&amp;cat=%s"><i aria-hidden="true" class="fa fa-globe" title="%s"></i></a> ',
                    $cat['id'],
                    Translation::get('ad_categ_translate')
                );

                // delete (sub) category (if current language)
                if ((is_countable($category->getChildren($cat['id'])) ? count($category->getChildren($cat['id'])) : 0) == 0 && $cat['lang'] == $lang) {
                    printf(
                        '<a class="btn btn-danger btn-sm" href="?action=deletecategory&amp;cat=%s&amp;catlang=%s"><i aria-hidden="true" class="fa fa-trash" title="%s"></i></a> ',
                        $cat['id'],
                        $cat['lang'],
                        Translation::get('ad_categ_delete')
                    );
                } else {
                    echo '<a class="btn btn-inverse btn-sm" style="cursor: not-allowed;"><i aria-hidden="true" class="fa fa-trash"></i></a>';
                }

                if ($cat['lang'] == $lang) {
                    // cut category (if current language)
                    printf(
                        '<a class="btn btn-warning btn-sm" href="?action=cutcategory&amp;cat=%s"><i aria-hidden="true" class="fa fa-cut" title="%s"></i></a>  ',
                        $cat['id'],
                        Translation::get('ad_categ_cut')
                    );
                }
                echo '</span>';
                echo '</div>';

                if ($hasParent) {
                    echo '</div>';
                }

                $lastCatId = $cat['id'];
            }
            ?>
        <div class="mt-4">
        <?= Alert::info('ad_categ_remark') ?>
        </div>
      </form>
    </div>
  </div>
            <?php
        } else {
            echo Translation::get('err_NotAuth');
        }
