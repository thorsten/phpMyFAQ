<?php
/**
* $Id: category.main.php,v 1.25 2006-09-19 20:21:30 matteo Exp $
*
* List all categories in the admin section
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-12-20
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_categ_edit']);

if ($permission['editcateg']) {

    $category = new PMF_Category($LANGCODE);

    // Save a new category
    if (isset($_POST['action']) && $_POST['action'] == 'savecategory') {

        $parent_id = (int)$_POST['parent_id'];

        $category_data = array(
            'lang'          => $db->escape_string($_POST['lang']),
            'name'          => $db->escape_string($_POST['name']),
            'description'   => $db->escape_string($_POST['description']),
            'user_id'       => (int)$_POST['user_id']);

        if ($category->addCategory($category_data, $parent_id)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    // Updates an existing category
    if (isset($_POST['action']) && $_POST['action'] == 'updatecategory') {

        $parent_id = (int)$_POST['parent_id'];

        $category_data = array(
            'id'            => (int)$_POST['id'],
            'lang'          => $db->escape_string($_POST['lang']),
            'parent_id'     => $parent_id,
            'name'          => $db->escape_string($_POST['name']),
            'description'   => $db->escape_string($_POST['description']),
            'user_id'       => (int)$_POST['user_id']);

        if (!$category->checkLanguage($category_data['id'], $category_data['lang'])) {
            if ($category->addCategory($category_data, $parent_id, $category_data['id'])) {
                printf('<p>%s</p>', $PMF_LANG['ad_categ_translated']);
            } else {
                printf('<p>%s</p>', $db->error());
            }
        } else {
            if ($category->updateCategory($category_data)) {
                   printf('<p>%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p>%s</p>', $db->error());
            }
        }
    }

    // Deletes an existing category
    if ($permission['delcateg'] && isset($_POST['action']) && $_POST['action'] == 'removecategory') {

        $id = (int)$_POST['cat'];
        $lang = $db->escape_string($_POST['lang']);
        $delete_all = strtolower($db->escape_string($_POST['deleteall'])) == 'yes' ? true : false;

        if ($category->deleteCategory($id, $lang, $delete_all) && $category->deleteCategoryRelation($id, $lang, $delete_all)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    // Moves a category
    if (isset($_POST['action']) && $_POST['action'] == 'movecategory') {

        $category_id_1 = (int)$_POST['cat'];
        $category_id_2 = (int)$_POST['change'];

        if ($category->swapCategories($category_id_1, $category_id_2)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p>%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Pastes a category
    if (isset($_POST['action']) && $_POST['action'] == 'movecategory') {

        $category_id = $_GET['cat'];
        $parent_id = $_GET['after'];

        if ($category->updateParentCategory($category_id, $parent_id)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p>%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Lists all categories
    if (isset($_POST['language'])) {
        $lang = $_POST['language'];
    } else {
        $lang = $LANGCODE;
    }
    $currentLink = $_SERVER['PHP_SELF'].$linkext;

    $tree = new PMF_Category($lang);
    $tree->getMissingCategories();
    $tree->buildTree();

    foreach ($tree->catTree as $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        // category translated in this language?
        ($cat['lang'] == $lang) ? $catname = $cat['name'] : $catname = $cat['name'].' ('.$languageCodes[strtoupper($cat['lang'])].')';

        // show category name
        printf("%s<strong style=\"vertical-align: top;\">&middot; %s</strong> ",
            $indent,
            $catname);

        if ($cat["lang"] == $lang) {
           // add sub category (if actual language)
           printf('<a href="%s&amp;action=addcategory&amp;cat=%s&amp;lang=%s" title="%s"><img src="images/add.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>',
               $currentLink,
               $cat['id'],
               $cat['lang'],
               $PMF_LANG['ad_kateg_add'],
               $PMF_LANG['ad_kateg_add'],
               $PMF_LANG['ad_kateg_add']);

           // rename (sub) category (if actual language)
           printf('<a href="%s&amp;action=editcategory&amp;cat=%s" title="%s"><img src="images/edit.gif" width="18" height="18" border="0" title="%s" alt="%s" /></a>',
               $currentLink,
               $cat['id'],
               $PMF_LANG['ad_kateg_rename'],
               $PMF_LANG['ad_kateg_rename'],
               $PMF_LANG['ad_kateg_rename']);
        }

        // translate category (always)
        printf('<a href="%s&amp;action=translatecategory&amp;cat=%s" title="%s"><img src="images/translate.gif" width="18" height="18" border="0" title="%s" alt="%s" /></a>',
            $currentLink,
            $cat['id'],
            $PMF_LANG['ad_categ_translate'],
            $PMF_LANG['ad_categ_translate'],
            $PMF_LANG['ad_categ_translate']);

        // delete (sub) category (if actual language)
        if (count($tree->getChildren($cat['id'])) == 0 && $cat["lang"] == $lang) {
            printf('<a href="%s&amp;action=deletecategory&amp;cat=%s&amp;lang=%s" title="%s"><img src="images/delete.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>',
                $currentLink,
                $cat['id'],
                $cat['lang'],
                $PMF_LANG['ad_categ_delete'],
                $PMF_LANG['ad_categ_delete'],
                $PMF_LANG['ad_categ_delete']);
        }

        if ($cat["lang"] == $lang) {
           // cut category (if actual language)
           printf('<a href="%s&amp;action=cutcategory&amp;cat=%s" title="%s"><img src="images/cut.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>',
               $currentLink,
               $cat['id'],
               $PMF_LANG['ad_categ_cut'],
               $PMF_LANG['ad_categ_cut'],
               $PMF_LANG['ad_categ_cut']);

           // move category (if actual language)
           printf('<a href="%s&amp;action=movecategory&amp;cat=%s&amp;parent_id=%s" title="%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>',
               $currentLink,
               $cat['id'],
               $cat['parent_id'],
               $PMF_LANG['ad_categ_move'],
               $PMF_LANG['ad_categ_move'],
               $PMF_LANG['ad_categ_move']);
        }
        print "<br />";
    }

    printf('<p><img src="images/arrow.gif" width="11" height="11" alt="" border="0" /> <a href="%s&amp;action=addcategory">%s</a></p>',
        $currentLink,
        $PMF_LANG['ad_kateg_add']);
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark']);
} else {
    print $PMF_LANG['err_NotAuth'];
}
