<?php
/**
 * List all categories in the admin section
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editcateg']) {
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

    // Save a new category
    if ($action == 'savecategory') {
        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId     = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = array(
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT)
        );

        $permissions = array();
        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_user' => array(
                    -1
                )
            );
        } else {
            $permissions += array(
                'restricted_user' => array(
                    PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT)
                )
            );
        }

        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_groups' => array(
                    -1
                )
            );
        } else {
            $permissions += PMF_Filter::filterInputArray(
                INPUT_POST,
                array(
                    'restricted_groups' => array(
                        'filter' => FILTER_VALIDATE_INT,
                        'flags'  => FILTER_REQUIRE_ARRAY
                    )
                )
            );
        }

        $categoryId = $category->addCategory($categoryData, $parentId);

        if ($categoryId) {
            $category->addPermission('user', array($categoryId), $permissions['restricted_user']);
            $category->addPermission('group', array($categoryId), $permissions['restricted_groups']);

            // All the other translations
            $languages                      = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_added'];
        } else {
            $templateVars['errorMessage'] = $faqConfig->getDb()->error();
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {
        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $parentId     = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = array(
            'id'          => PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_STRING),
            'parent_id'   => $parentId,
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        $permissions = array();
        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_user' => array(
                    -1
                )
            );
        } else {
            $permissions += array(
                'restricted_user' => array(
                    PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT)
                )
            );
        }

        if ('all' === PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
            $permissions += array(
                'restricted_groups' => array(
                    -1
                )
            );
        } else {
            $permissions += PMF_Filter::filterInputArray(
                INPUT_POST,
                array(
                    'restricted_groups' => array(
                        'filter' => FILTER_VALIDATE_INT,
                        'flags'  => FILTER_REQUIRE_ARRAY
                    )
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
                $templateVars['successMessage'] = $PMF_LANG['ad_categ_updated'];
            } else {
                $templateVars['errorMessage'] = $faqConfig->getDb()->error();
            }
        }

        // All the other translations
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
    }

    // Deletes an existing category
    if ($permission['delcateg'] && $action == 'removecategory') {
        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $id         = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $lang       = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteall  = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        $delete_all = strtolower($deleteall) == 'yes' ? true : false;

        if ($category->deleteCategory($id, $lang, $delete_all) &&
            $category->deleteCategoryRelation($id, $lang, $delete_all) &&
            $category->deletePermission('user', array($id)) && $category->deletePermission('group', array($id))
        ) {
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_deleted'];
        } else {
            $templateVars['errorMessage'] = $faqConfig->getDb()->error();
        }
        unset($category, $id, $lang, $deleteall, $delete_all);
    }

    // Moves a category
    if ($action == 'changecategory') {
        $category = new PMF_Category($faqConfig, array(), false);
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
        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $categoryId = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $parentId   = PMF_Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
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
    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->getMissingCategories();
    $category->buildTree();

    $open = $lastCatId = $openDiv = 0;
    $templateVars['categoryTree'] .= '<ul>';
    foreach ($category->catTree as $id => $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }

        // Category translated in this language?
        if ($cat['lang'] == $lang) {
            $categoryName = $cat['name'];
        } else {
            $categoryName = $cat['name'] . ' (' . $languageCodes[strtoupper($cat['lang'])] . ')';
        }

        $level     = $cat['indent'];
        $leveldiff = $open - $level;

        if ($leveldiff > 1) {

            $templateVars['categoryTree'] .= '</li>';
            for ($i = $leveldiff; $i > 1; $i--) {
                $templateVars['categoryTree'] .= '</ul></div></li>';
            }
        }

        if ($level < $open) {
            if (($level - $open) == -1) {
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

        $open      = $level;
        $lastCatId = $cat['id'];
    }

    if ($open > 0) {
        $templateVars['categoryTree'] .= str_repeat("</li>\n\t</ul>\n\t", $open);
    }

    $templateVars['categoryTree'] .= "</li>\n</ul>";

    $twig->loadTemplate('category/main.twig')
        ->display($templateVars);

    unset($templateVars, $csrfToken, $open, $level, $category, $id, $cat, $lang, $lastCatId);
} else {
    require 'noperm.php';
}
