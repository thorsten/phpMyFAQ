<?php
/**
 * List all categories in the admin section
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2003-12-20
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @copyright 2003-2010 phpMyFAQ Team
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_categ_edit']);

print "<p class=\"hr\">\n";
printf('<img src="images/arrow.gif" width="11" height="11" alt="" border="0" /> <a href="?action=addcategory">%s</a>',
   $PMF_LANG['ad_kateg_add']);
print "&nbsp;&nbsp;&nbsp;";
printf('<img src="images/arrow.gif" width="11" height="11" alt="" border="0" /> <a href="?action=showcategory">%s</a>',
   $PMF_LANG['ad_categ_show']);
print "</p>\n";

if ($permission['editcateg']) {
    
    $categoryNode      = new PMF_Category_Node();
    $categoryUser      = new PMF_Category_User();
    $categoryGroup     = new PMF_Category_Group();
    $categoryRelations = new PMF_Category_Relations();
    $categoryHelper    = new PMF_Category_Helper();
    
    // Save a new category
    if ($action == 'savecategory') {

        $category     = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $categoryData = array(
            'id'          => null,
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'parent_id'   => PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT),
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        $userperm     = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
        $userAllowed  = ('all' == $userperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
        $groupperm    = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
        $groupAllowed = ('all' == $groupperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);

        
        if ($categoryNode->create($categoryData)) {
            
            $userPermission  = array(
                'category_id' => $categoryNode->getCategoryId(),
                'user_id'     => $userAllowed);
            $groupPermission = array(
                'category_id' => $categoryNode->getCategoryId(),
                'group_id'    => $groupAllowed);
            
            $categoryUser->create($userPermission);
            $categoryGroup->create($groupPermission);
            
            printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p class="error">%s</p>', $db->error());
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {

        $category       = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $categoryHelper = new PMF_Category_Helper();
        $categoryId     = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $categoryData   = array(
            'id'          => $categoryId,
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'parent_id'   => PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT),
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        $userperm      = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
        $userAllowed  = ('all' == $userperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
        $groupperm     = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
        $groupAllowed = ('all' == $groupperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
        
        if ($categoryHelper->hasTranslation($categoryData['id'], $categoryData['lang'])) {
            if ($categoryNode->create($categoryData)) {
                printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_translated']);
            } else {
                printf('<p class="error">%s</p>', $db->error());
            }
        } else {
            if ($categoryNode->update($categoryId, $categoryData)) {
                
                $userPermission  = array(
                    'category_id' => $categoryNode->getCategoryId(),
                    'user_id'     => $userAllowed);
                $groupPermission = array(
                    'category_id' => $categoryNode->getCategoryId(),
                    'group_id'    => $groupAllowed);
                
                $categoryUser->update($categoryId, $userPermission);
                $categoryGroup->update($categoryId, $groupPermission);
                
                printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p class="error">%s</p>', $db->error());
            }
        }
    }

    // Deletes an existing category
    if ($permission['delcateg'] && $action == 'removecategory') {

        $category     = new PMF_Category($current_admin_user, $current_admin_groups, false);
        
        $categoryId   = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $categoryLang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteAll    = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        
        if ('yes' == $deleteAll) {
            $categoryNode->setLanguage($categoryLang);
            $categoryRelations->setLanguage($categoryLang);
        }
        
        if ($categoryNode->delete($categoryId) && $categoryRelations->delete($categoryId) &&
            $categoryUser->delete($categoryId) && $categoryGroup->delete($categoryId)) {
            
            printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p class="error">%s</p>', $db->error());
        }
    }

    // Moves a category
    if ($action == 'changecategory') {

        $category      = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $category_id_1 = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $category_id_2 = PMF_Filter::filterInput(INPUT_POST, 'change', FILTER_VALIDATE_INT);

        if ($category->swapCategories($category_id_1, $category_id_2)) {
            printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Pastes a category
    if ($action == 'pastecategory') {

        $category    = new PMF_Category($current_admin_user, $current_admin_groups, false);
        $category_id = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $parent_id   = PMF_Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
        if ($category->updateParentCategory($category_id, $parent_id)) {
            printf('<p class="message">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Lists all categories
    $lang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING, $LANGCODE);

    // If we changed the category tree, unset the object
    if (isset($category)) {
        unset($category);
    }
    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->getMissingCategories();
    $category->buildTree();

    foreach ($category->catTree as $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        // category translated in this language?
        ($cat['lang'] == $lang) ? $catname = $cat['name'] : $catname = $cat['name'].' ('.$languageCodes[strtoupper($cat['lang'])].')';

        // show category name
        printf("<p>%s<strong style=\"vertical-align: top;\">&middot; %s</strong> ",
            $indent,
            $catname);

        if ($cat["lang"] == $lang) {
           // add sub category (if actual language)
           printf('<a href="?action=addcategory&amp;cat=%s&amp;lang=%s"><img src="images/add.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
               $cat['id'],
               $cat['lang'],
               $PMF_LANG['ad_quick_category'],
               $PMF_LANG['ad_quick_category']);

           // rename (sub) category (if actual language)
           printf('<a href="?action=editcategory&amp;cat=%s"><img src="images/edit.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
               $cat['id'],
               $PMF_LANG['ad_kateg_rename'],
               $PMF_LANG['ad_kateg_rename']);
        }

        // translate category (always)
        printf('<a href="?action=translatecategory&amp;cat=%s"><img src="images/translate.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
            $cat['id'],
            $PMF_LANG['ad_categ_translate'],
            $PMF_LANG['ad_categ_translate']);

        // delete (sub) category (if actual language)
        if (count($category->getChildren($cat['id'])) == 0 && $cat["lang"] == $lang) {
            printf('<a href="?action=deletecategory&amp;cat=%s&amp;lang=%s"><img src="images/delete.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
                $cat['id'],
                $cat['lang'],
                $PMF_LANG['ad_categ_delete'],
                $PMF_LANG['ad_categ_delete']);
        }

        if ($cat["lang"] == $lang) {
           // cut category (if actual language)
           printf('<a href="?action=cutcategory&amp;cat=%s"><img src="images/cut.png" width="16" height="16" alt="%s" border="0" title="%s" /></a>&nbsp;',
               $cat['id'],
               $PMF_LANG['ad_categ_cut'],
               $PMF_LANG['ad_categ_cut']);

           if ($categoryHelper->numParent($cat['parent_id']) > 1) {
              // move category (if actual language) AND more than 1 category at the same level)
              printf('<a href="?action=movecategory&amp;cat=%s&amp;parent_id=%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>',
                  $cat['id'],
                  $cat['parent_id'],
                  $PMF_LANG['ad_categ_move'],
                  $PMF_LANG['ad_categ_move']);
           }
        }
        print "</p>\n";
    }

    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark']);
} else {
    print $PMF_LANG['err_NotAuth'];
}
