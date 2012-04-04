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
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header>
            <h2><?php print $PMF_LANG['ad_menu_categ_edit']; ?></h2>
        </header>
        <ul>
            <li><a href="?action=addcategory"><?php print $PMF_LANG['ad_kateg_add']; ?></a></li>
            <li><a href="?action=showcategory"><?php print $PMF_LANG['ad_categ_show'];?></a></li>
        </ul>
<?php

$csrfToken = PMF_Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);
if ('category' != $action && 'content' != $action && 
    (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken)) {
    $permission['editcateg'] = false; 
}

if ($permission['editcateg']) {

    // Save a new category
    if ($action == 'savecategory') {

        $category = new PMF_Category($faqConfig, false);
        $category->setUser($current_admin_user);
        $category->setGroups($current_admin_groups);
        $parent_id     = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $category_data = array(
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        $userperm      = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
        $user_allowed  = ('all' == $userperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
        $groupperm     = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
        $group_allowed = ('all' == $groupperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);

        $category_id = $category->addCategory($category_data, $parent_id);
        if ($category_id) {
            $category->addPermission('user', array($category_id), $user_allowed);
            $category->addPermission('group', array($category_id), $group_allowed);
                        
            // All the other translations            
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            if ($faqConfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {

                $languages     = explode(",", $languages);
                $category_lang = $category_data['lang'];
                $user_id       = $category_data['user_id'];                
                foreach ($languages as $translated_lang) {
                    if ($translated_lang == $category_lang) {
                        continue;
                    }
                    $translated_name        = PMF_Filter::filterInput(INPUT_POST, 'name_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
                    $translated_description = PMF_Filter::filterInput(INPUT_POST, 'description_translated_' . $translated_lang, FILTER_SANITIZE_STRING);

                    $category_data = array_merge($category_data, array(
                        'id'          => $category_id,
                        'lang'        => $translated_lang,
                        'parent_id'   => $parent_id,
                        'name'        => $translated_name,
                        'description' => $translated_description,
                        'user_id'     => $user_id));

                    if (!$category->checkLanguage($category_id, $translated_lang)) {
                        $category->addCategory($category_data, $parent_id, $category_id);
                    } else {
                        $category->updateCategory($category_data);
                    }
                }
            }
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p class="alert alert-error">%s</p>', $db->error());
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {

        $category = new PMF_Category($faqConfig, false);
        $category->setUser($current_admin_user);
        $category->setGroups($current_admin_groups);
        $parent_id     = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $category_data = array(
            'id'          => PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_STRING),
            'parent_id'   => $parent_id,
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        $userperm      = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
        $user_allowed  = ('all' == $userperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
        $groupperm     = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
        $group_allowed = ('all' == $groupperm) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
        
        if (!$category->checkLanguage($category_data['id'], $category_data['lang'])) {
            if ($category->addCategory($category_data, $parent_id, $category_data['id']) &&
                $category->addPermission('user', array($category_data['id']), $user_allowed) &&
                $category->addPermission('group', array($category_data['id']), $group_allowed)) {
                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_translated']);
            } else {
                printf('<p class="alert alert-error">%s</p>', $db->error());
            }
        } else {
            if ($category->updateCategory($category_data)) {
                $category->deletePermission('user', array($category_data['id']));
                $category->deletePermission('group', array($category_data['id']));
                $category->addPermission('user', array($category_data['id']), $user_allowed);
                $category->addPermission('group', array($category_data['id']), $group_allowed);
                printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p class="alert alert-error">%s</p>', $db->error());
            }
        }
        
        // All the other translations            
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
        if ($faqConfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {

            $languages     = explode(",", $languages);
            $category_lang = $category_data['lang'];
            $category_id   = $category_data['id'];
            $user_id       = $category_data['user_id'];
            foreach ($languages as $translated_lang) {
                if ($translated_lang == $category_lang) {
                    continue;
                }
                $translated_name        = PMF_Filter::filterInput(INPUT_POST, 'name_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
                $translated_description = PMF_Filter::filterInput(INPUT_POST, 'description_translated_' . $translated_lang, FILTER_SANITIZE_STRING);

                $category_data = array_merge($category_data, array(
                    'id'          => $category_id,
                    'lang'        => $translated_lang,
                    'parent_id'   => $parent_id,
                    'name'        => $translated_name,
                    'description' => $translated_description,
                    'user_id'     => $user_id));

                if (!$category->checkLanguage($category_id, $translated_lang)) {
                    $category->addCategory($category_data, $parent_id, $category_id);
                } else {
                    $category->updateCategory($category_data);
                }
            }
        }
    }

    // Deletes an existing category
    if ($permission['delcateg'] && $action == 'removecategory') {

        $category = new PMF_Category($faqConfig, false);
        $category->setUser($current_admin_user);
        $category->setGroups($current_admin_groups);
        $id         = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $lang       = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteall  = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        $delete_all = strtolower($deleteall) == 'yes' ? true : false;

        if ($category->deleteCategory($id, $lang, $delete_all) && 
            $category->deleteCategoryRelation($id, $lang, $delete_all) &&
            $category->deletePermission('user', array($id)) && $category->deletePermission('group', array($id))) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p class="alert alert-error">%s</p>', $db->error());
        }
    }

    // Moves a category
    if ($action == 'changecategory') {

        $category = new PMF_Category($faqConfig, false);
        $category->setUser($current_admin_user);
        $category->setGroups($current_admin_groups);
        $category_id_1 = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $category_id_2 = PMF_Filter::filterInput(INPUT_POST, 'change', FILTER_VALIDATE_INT);

        if ($category->swapCategories($category_id_1, $category_id_2)) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="alert alert-error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Pastes a category
    if ($action == 'pastecategory') {

        $category = new PMF_Category($faqConfig, false);
        $category->setUser($current_admin_user);
        $category->setGroups($current_admin_groups);
        $category_id = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $parent_id   = PMF_Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
        if ($category->updateParentCategory($category_id, $parent_id)) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="alert alert-error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Lists all categories
    $lang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING, $LANGCODE);

    // If we changed the category tree, unset the object
    if (isset($category)) {
        unset($category);
    }
    $category = new PMF_Category($faqConfig, false);
    $category->setUser($current_admin_user);
    $category->setGroups($current_admin_groups);
    $category->getMissingCategories();
    $category->buildTree();

    $open = $lastCatId = $openDiv = 0;
    print '<ul>';
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

        $level     = $cat['level'];
        $leveldiff = $open - $level;

        if ($leveldiff > 1) {

            print '</li>';
            for ($i = $leveldiff; $i > 1; $i--) {
                print '</ul></div></li>';
            }
        }

        if ($level < $open) {
            if (($level - $open) == -1) {
                print '</li>';
            }
            print '</ul></li>';
        } elseif ($level == $open && $id != 0) {
            print '</li>';
        }

        if ($level > $open) {
            printf('<div id="div_%d" style="display: none;">', $lastCatId);
            print '<ul><li>';
        } else {
            print '<li>';
        }

        if (count($category->getChildren($cat['id'])) != 0) {
            // Show name and icon for expand the sub-categories
            printf(
                "<strong><a href=\"javascript:;\" onclick=\"toggleFieldset('%d');\">%s</a></strong> ",
                $cat['id'],
                $categoryName
            );
        } else {
            // Show just the name
            printf("<strong>%s</strong> ", $categoryName);
        }

        if ($cat["lang"] == $lang) {
           // add sub category (if current language)
           printf('
            <a href="?action=addcategory&amp;cat=%s&amp;lang=%s"><img src="images/add.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
               $cat['id'],
               $cat['lang'],
               $PMF_LANG['ad_quick_category'],
               $PMF_LANG['ad_quick_category']
           );

           // rename (sub) category (if current language)
           printf('
               <a href="?action=editcategory&amp;cat=%s"><img src="images/edit.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
               $cat['id'],
               $PMF_LANG['ad_kateg_rename'],
               $PMF_LANG['ad_kateg_rename']
           );
        }

        // translate category (always)
        printf(
            '<a href="?action=translatecategory&amp;cat=%s"><img src="images/translate.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
            $cat['id'],
            $PMF_LANG['ad_categ_translate'],
            $PMF_LANG['ad_categ_translate']
        );

        // delete (sub) category (if current language)
        if (count($category->getChildren($cat['id'])) == 0 && $cat["lang"] == $lang) {
            printf(
                '<a href="?action=deletecategory&amp;cat=%s&amp;catlang=%s"><img src="images/delete.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
                $cat['id'],
                $cat['lang'],
                $PMF_LANG['ad_categ_delete'],
                $PMF_LANG['ad_categ_delete']
            );
        }

        if ($cat["lang"] == $lang) {
           // cut category (if current language)
           printf(
               '<a href="?action=cutcategory&amp;cat=%s"><img src="images/cut.png" width="16" height="16" alt="%s" border="0" title="%s" /></a>&nbsp;',
               $cat['id'],
               $PMF_LANG['ad_categ_cut'],
               $PMF_LANG['ad_categ_cut']
           );

           if ($category->numParent($cat['parent_id']) > 1) {
              // move category (if current language) AND more than 1 category at the same level)
              printf(
                  '<a href="?action=movecategory&amp;cat=%s&amp;parent_id=%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>',
                  $cat['id'],
                  $cat['parent_id'],
                  $PMF_LANG['ad_categ_move'],
                  $PMF_LANG['ad_categ_move']
              );
           }
        }

        $open      = $level;
        $lastCatId = $cat['id'];
    }

    if ($open > 0) {
        print str_repeat("</li>\n\t</ul>\n\t", $open);
    }

    print "</li>\n</ul>";
    
    printf('<p class="alert alert-info">%s</p>', $PMF_LANG['ad_categ_remark']);
} else {
    print $PMF_LANG['err_NotAuth'];
}
