<?php
/**
 * List all categories in the admin section.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2017 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

<<<<<<< HEAD
if ($user->perm->checkRight($user->getUserId(), 'editcateg')) {
    $templateVars = array(
        'PMF_LANG'        => $PMF_LANG,
        'categoryTree'    => '',
        'errorMessages'   => '',
        'successMessages' => ''
    );

    $csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
    if ('category' != $action && 'content' != $action &&
        (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken)
    ) {
        $permission['editcateg'] = false;
    }
=======
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-list"></i> <?php echo $PMF_LANG['ad_menu_categ_edit'] ?>
                    <div class="pull-right">
                        <a class="btn btn-success" href="?action=addcategory">
                            <i aria-hidden="true" class="fa fa-plus fa-fw"></i> <?php echo $PMF_LANG['ad_kateg_add']; ?>
                        </a>
                        <a class="btn btn-info" href="?action=showcategory">
                            <i aria-hidden="true" class="fa fa-th fa-fw"></i> <?php echo $PMF_LANG['ad_categ_show'];?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
//
// CSRF Check
//
$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if ('category' != $action && 'content' != $action &&
    (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken)) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}

//
// Image upload
//
$uploadedFile = (isset($_FILES['image']['size']) && $_FILES['image']['size'] > 0) ? $_FILES['image'] : [];
$categoryImage = new PMF_Category_Image($faqConfig);
$categoryImage->setUploadedFile($uploadedFile);

if ($user->perm->checkRight($user->getUserId(), 'editcateg') && $csrfCheck) {
>>>>>>> 2.10

    // Save a new category
    if ($action == 'savecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = $faqConfig->getDb()->nextId(PMF_Db::getTablePrefix().'faqcategories', 'id');
        $categoryLang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $categoryData = [
            'lang' => $categoryLang,
            'name' => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id' => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),
            'image' => $categoryImage->getFileName($categoryId, $categoryLang),
            'show_home' => PMF_Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT)
        ];

        $permissions = [];
        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_user' => array(
                    -1,
                ),
            );
        } else {
            $permissions += array(
                'restricted_user' => array(
                    PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
                ),
            );
        }

        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_groups' => array(
                    -1,
                ),
            );
        } else {
            $permissions += PMF_Filter::filterInputArray(
                INPUT_POST,
                array(
                    'restricted_groups' => array(
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ),
                )
            );
        }

        $categoryId = $category->addCategory($categoryData, $parentId);

        if ($categoryId) {
            $category->addPermission('user', array($categoryId), $permissions['restricted_user']);
            $category->addPermission('group', array($categoryId), $permissions['restricted_groups']);

            $categoryImage->upload();

            // All the other translations
            $languages                      = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_added'];
        } else {
            $templateVars['errorMessage'] = $faqConfig->getDb()->error();
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $categoryLang = PMF_Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_STRING);
        $existingImage = PMF_Filter::filterInput(INPUT_POST, 'existing_image', FILTER_SANITIZE_STRING);
        $image = count($uploadedFile) ? $categoryImage->getFileName($categoryId, $categoryLang) : $existingImage;
        $categoryData = [
            'id' => $categoryId,
            'lang' => $categoryLang,
            'parent_id' => $parentId,
            'name' => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id' => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),
            'image' => $image,
            'show_home' => PMF_Filter::filterInput(INPUT_POST, 'show_home', FILTER_VALIDATE_INT),
        ];

        $permissions = [];
        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_user' => array(
                    -1,
                ),
            );
        } else {
            $permissions += array(
                'restricted_user' => array(
                    PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
                ),
            );
        }

        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_groups' => array(
                    -1,
                ),
            );
        } else {
            $permissions += PMF_Filter::filterInputArray(
                INPUT_POST,
                array(
                    'restricted_groups' => array(
                        'filter' => FILTER_VALIDATE_INT,
                        'flags' => FILTER_REQUIRE_ARRAY,
                    ),
                )
            );
        }

        if (!$category->checkLanguage($categoryData['id'], $categoryData['lang'])) {
            if ($category->addCategory($categoryData, $parentId, $categoryData['id']) &&
                $category->addPermission('user', array($categoryData['id']), $permissions['restricted_user']) &&
                $category->addPermission('group', array($categoryData['id']), $permissions['restricted_groups'])
            ) {
                $templateVars['successMessage'] = $PMF_LANG['ad_categ_translated'];
            } else {
                $templateVars['errorMessage'] = $faqConfig->getDb()->error();
            }
        } else {
            if ($category->updateCategory($categoryData)) {
                $category->deletePermission('user', array($categoryData['id']));
                $category->deletePermission('group', array($categoryData['id']));
                $category->addPermission('user', array($categoryData['id']), $permissions['restricted_user']);
                $category->addPermission('group', array($categoryData['id']), $permissions['restricted_groups']);
<<<<<<< HEAD
                $templateVars['successMessage'] = $PMF_LANG['ad_categ_updated'];
=======

                $categoryImage->upload();

                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
>>>>>>> 2.10
            } else {
                $templateVars['errorMessage'] = $faqConfig->getDb()->error();
            }
        }

        // All the other translations
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
    }

    // Deletes an existing category
    if ($user->perm->checkRight($user->getUserId(), 'delcateg') && $action == 'removecategory') {

        $categoryId = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $categoryLang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteAll = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        $deleteAll = strtolower($deleteAll) == 'yes' ? true : false;

        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
<<<<<<< HEAD
        $id         = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $lang       = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteall  = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        $delete_all = strtolower($deleteall) == 'yes' ? true : false;

        if ($category->deleteCategory($id, $lang, $delete_all) &&
            $category->deleteCategoryRelation($id, $lang, $delete_all) &&
            $category->deletePermission('user', array($id)) && $category->deletePermission('group', array($id))
        ) {
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_deleted'];
=======

        $categoryImage = new PMF_Category_Image($faqConfig);
        $categoryImage->setFileName($category->getCategoryData($categoryId)->getImage());

        if ($category->deleteCategory($categoryId, $categoryLang, $deleteAll) &&
            $category->deleteCategoryRelation($categoryId, $categoryLang, $deleteAll) &&
            $category->deletePermission('user', [$categoryId]) &&
            $category->deletePermission('group', [$categoryId]) &&
            $categoryImage->delete()) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_deleted']);
>>>>>>> 2.10
        } else {
            $templateVars['errorMessage'] = $faqConfig->getDb()->error();
        }
        unset($category, $id, $lang, $deleteall, $delete_all);
    }

    // Moves a category
    if ($action == 'changecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $categoryId_1 = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $categoryId_2 = PMF_Filter::filterInput(INPUT_POST, 'change', FILTER_VALIDATE_INT);

        if ($category->swapCategories($categoryId_1, $categoryId_2)) {
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_updated'];
        } else {
            $templateVars['errorMessage'] = sprintf(
                '%s<br />%s',
                $PMF_LANG['ad_categ_paste_error'],
                $faqConfig->getDb()->error()
            );
        }
    }

    // Pastes a category
    if ($action == 'pastecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $categoryId = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $parentId = PMF_Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
        if ($category->updateParentCategory($categoryId, $parentId)) {
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_updated'];
        } else {
            $templateVars['errorMessage'] = sprintf(
                '%s<br />%s',
                $PMF_LANG['ad_categ_paste_error'],
                $faqConfig->getDb()->error()
            );
        }
    }

    // Lists all categories
    $lang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING, $LANGCODE);

    // If we changed the category tree, unset the object
    if (isset($category)) {
        unset($category);
    }
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();
    $category->buildTree();

    $open = $lastCatId = $openDiv = 0;
<<<<<<< HEAD
    $templateVars['categoryTree'] .= '<ul>';
=======
    echo '<ul>';
>>>>>>> 2.10
    foreach ($category->catTree as $id => $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; ++$i) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }

        // Category translated in this language?
        if ($cat['lang'] == $lang) {
            $categoryName = $cat['name'];
        } else {
            $categoryName = $cat['name'].' ('.$languageCodes[strtoupper($cat['lang'])].')';
        }

        if (isset($cat['active']) && 0 === $cat['active']) {
            $categoryName = '<em style="color: gray">'.$categoryName.'</em>';
        }

        $level = $cat['indent'];
        $leveldiff = $open - $level;

        if ($leveldiff > 1) {
<<<<<<< HEAD

            $templateVars['categoryTree'] .= '</li>';
            for ($i = $leveldiff; $i > 1; $i--) {
                $templateVars['categoryTree'] .= '</ul></div></li>';
=======
            echo '</li>';
            for ($i = $leveldiff; $i > 1; --$i) {
                echo '</ul></div></li>';
>>>>>>> 2.10
            }
        }

        if ($level < $open) {
            if (($level - $open) == -1) {
<<<<<<< HEAD
                $templateVars['categoryTree'] .= '</li>';
            }
            $templateVars['categoryTree'] .= '</ul></li>';
        } elseif ($level == $open) {
            $templateVars['categoryTree'] .= '</li>';
        }

        if ($level > $open) {
            $templateVars['categoryTree'] .= sprintf('<div id="div_%d" style="display: none;">', $lastCatId);
            $templateVars['categoryTree'] .= '<ul><li>';
        } else {
            $templateVars['categoryTree'] .= '<li>';
        }

        $templateVars['categoryTree'] .= $twig->loadTemplate('category/treeItem.twig')
            ->render(
                array(
                    'PMF_LANG'           => $PMF_LANG,
                    'id'                 => $cat['id'],
                    'addButtonUrl'       => sprintf('?action=addcategory&cat=%s&lang=%s', $cat['id'], $cat['lang']),
                    'cutButtonUrl'       => sprintf('?action=cutcategory&cat=%s', $cat['id']),
                    'deleteButtonUrl'    => sprintf('?action=deletecategory&cat=%s&catlang=%s', $cat['id'], $cat['lang']),
                    'moveButtonUrl'      => sprintf('?action=movecategory&cat=%s&parent_id=%s', $cat['id'], $cat['parent_id']),
                    'name'               => $categoryName,
                    'renameButtonUrl'    => sprintf('?action=editcategory&cat=%s', $cat['id']),
                    // add sub category (if current language)
                    'renderAddButton'    => $cat["lang"] == $lang,
                    // cut category (if current language)
                    'renderCutButton'    => $cat["lang"] == $lang,
                    // delete (sub) category (if current language)
                    'renderDeleteButton' => count($category->getChildren($cat['id'])) == 0 && $cat["lang"] == $lang,
                    // move category (if current language) AND more than 1 category at the same level)
                    'renderMoveButton'   => $cat["lang"] == $lang && $category->numParent($cat['parent_id']) > 1,
                    // rename (sub) category (if current language)
                    'renderRenameButton' => $cat["lang"] == $lang,
                    'renderToggler'      => count($category->getChildren($cat['id'])) != 0,
                    'translateButtonUrl' => sprintf('?action=translatecategory&cat=%s', $cat['id'])
                )
            );
=======
                echo '</li>';
            }
            echo '</ul></li>';
        } elseif ($level == $open) {
            echo '</li>';
        }

        if ($level > $open) {
            printf('<div id="div_%d" style="display: none; filter: inherit;">', $lastCatId);
            echo '<ul><li>';
        } else {
            echo '<li>';
        }

        if (count($category->getChildren($cat['id'])) != 0) {
            // Show name and icon for expand the sub-categories
            printf(
                '<h4 class="category-header" data-category-id="%d">%s</h4> ',
                $cat['id'],
                $categoryName
            );
        } else {
            // Show just the name
            printf('<h4>%s</h4> ', $categoryName);
        }

        if ($cat['lang'] == $lang) {
            // add sub category (if current language)
           printf('
            <a class="btn btn-info btn-sm" href="?action=addcategory&amp;cat=%s&amp;lang=%s"><span title="%s" class="fa fa-plus fa-fw"></span></a> ',
               $cat['id'],
               $cat['lang'],
               $PMF_LANG['ad_quick_category']
           );

           // rename (sub) category (if current language)
           printf('
               <a class="btn btn-info btn-sm" href="?action=editcategory&amp;cat=%s"><span title="%s" class="fa fa-edit fa-fw"></a> ',
               $cat['id'],
               $PMF_LANG['ad_kateg_rename']
           );
        }

        // translate category (always)
        printf(
            '<a class="btn btn-info btn-sm" href="?action=translatecategory&amp;cat=%s"><span title="%s" class="fa fa-share fa-fw"></a> ',
            $cat['id'],
            $PMF_LANG['ad_categ_translate']
        );

        // delete (sub) category (if current language)
        if (count($category->getChildren($cat['id'])) == 0 && $cat['lang'] == $lang) {
            printf(
                '<a class="btn btn-danger btn-sm" href="?action=deletecategory&amp;cat=%s&amp;catlang=%s"><span title="%s" class="fa fa-trash-o fa-fw"></a> ',
                $cat['id'],
                $cat['lang'],
                $PMF_LANG['ad_categ_delete']
            );
        } else {
            echo  '<a class="btn btn-inverse btn-sm" style="cursor: not-allowed;"><span class="fa fa-trash-o fa-fw"></a> ';
        }

        if ($cat['lang'] == $lang) {
            // cut category (if current language)
           printf(
               '<a class="btn btn-warning btn-sm" href="?action=cutcategory&amp;cat=%s"><span title="%s" class="fa fa-cut fa-fw"></a> ',
               $cat['id'],
               $PMF_LANG['ad_categ_cut']
           );

            if ($category->numParent($cat['parent_id']) > 1) {
                // move category (if current language) AND more than 1 category at the same level)
              printf(
                  '<a class="btn btn-warning btn-sm" href="?action=movecategory&amp;cat=%s&amp;parent_id=%s"><span title="%s" class="fa fa-sort fa-fw"></a> ',
                  $cat['id'],
                  $cat['parent_id'],
                  $PMF_LANG['ad_categ_move']
              );
            }
        }
>>>>>>> 2.10

        $open = $level;
        $lastCatId = $cat['id'];
    }

    if ($open > 0) {
<<<<<<< HEAD
        $templateVars['categoryTree'] .= str_repeat("</li>\n\t</ul>\n\t", $open);
    }

    $templateVars['categoryTree'] .= "</li>\n</ul>";

    $twig->loadTemplate('category/main.twig')
         ->display($templateVars);

    unset($templateVars, $csrfToken, $open, $level, $category, $id, $cat, $lang, $lastCatId);
} else {
    require 'noperm.php';
=======
        echo str_repeat("</li>\n\t</ul>\n\t", $open);
    }
    ?>
                    </li>
                </ul>

                <p class="alert alert-info"><?php echo $PMF_LANG['ad_categ_remark'] ?></p>
            </div>
        </div>
    <script src="assets/js/category.js"></script>

<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
>>>>>>> 2.10
}
