<?php
/**
 * List all categories in the admin section.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
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
$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if ('category' != $action && 'content' != $action &&
    (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken)) {
    $csrfCheck = false;
} else {
    $csrfCheck = true;
}

if ($user->perm->checkRight($user->getUserId(), 'editcateg') && $csrfCheck) {

    // Save a new category
    if ($action == 'savecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = array(
            'lang' => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'name' => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id' => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),
        );

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

            // All the other translations
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p class="alert alert-danger">%s</p>', $faqConfig->getDb()->error());
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = array(
            'id' => PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang' => PMF_Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_STRING),
            'parent_id' => $parentId,
            'name' => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id' => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT),
            'group_id' => PMF_Filter::filterInput(INPUT_POST, 'group_id', FILTER_VALIDATE_INT),
            'active' => PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_VALIDATE_INT),

        );

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
                $category->addPermission('group', array($categoryData['id']), $permissions['restricted_groups'])) {
                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_translated']);
            } else {
                printf('<p class="alert alert-danger">%s</p>', $faqConfig->getDb()->error());
            }
        } else {
            if ($category->updateCategory($categoryData)) {
                $category->deletePermission('user', array($categoryData['id']));
                $category->deletePermission('group', array($categoryData['id']));
                $category->addPermission('user', array($categoryData['id']), $permissions['restricted_user']);
                $category->addPermission('group', array($categoryData['id']), $permissions['restricted_groups']);
                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p class="alert alert-danger">%s</p>', $faqConfig->getDb()->error());
            }
        }

        // All the other translations
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
    }

    // Deletes an existing category
    if ($user->perm->checkRight($user->getUserId(), 'delcateg') && $action == 'removecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $id = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $lang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteall = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        $delete_all = strtolower($deleteall) == 'yes' ? true : false;

        if ($category->deleteCategory($id, $lang, $delete_all) &&
            $category->deleteCategoryRelation($id, $lang, $delete_all) &&
            $category->deletePermission('user', array($id)) && $category->deletePermission('group', array($id))) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p class="alert alert-danger">%s</p>', $faqConfig->getDb()->error());
        }
    }

    // Moves a category
    if ($action == 'changecategory') {
        $category = new PMF_Category($faqConfig, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $categoryId_1 = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $categoryId_2 = PMF_Filter::filterInput(INPUT_POST, 'change', FILTER_VALIDATE_INT);

        if ($category->swapCategories($categoryId_1, $categoryId_2)) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf(
                '<p class="alert alert-danger">%s<br />%s</p>',
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
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf(
                '<p class="alert alert-danger">%s<br />%s</p>',
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
    echo '<ul>';
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
            echo '</li>';
            for ($i = $leveldiff; $i > 1; --$i) {
                echo '</ul></div></li>';
            }
        }

        if ($level < $open) {
            if (($level - $open) == -1) {
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

        // add faq to category (always)
        printf('
           <a class="btn btn-info btn-sm" href="?action=editentry&amp;cat=%s&amp;lang=%s"><span title="%s" class="fa fa-file-text-o fa-fw"></span></a> ',
           $cat['id'],
           $cat['lang'],
           $PMF_LANG['ad_quick_entry']
        );

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

        $open = $level;
        $lastCatId = $cat['id'];
    }

    if ($open > 0) {
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
}
