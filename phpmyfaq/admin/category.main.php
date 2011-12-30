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
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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
    
    $categoryNode      = new PMF_Category_Node();
    $categoryUser      = new PMF_Category_User();
    $categoryGroup     = new PMF_Category_Group();
    $categoryRelations = new PMF_Category_Relations();
    $categoryHelper    = new PMF_Category_Helper();
    
    // Save a new category
    if ($action == 'savecategory') {

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
            
            printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_added']);

            // All the other translations            
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            if ($faqconfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {

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
            printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p class="error">%s</p>', $db->error());
        }
    }

    // Updates an existing category
    if ($action == 'updatecategory') {

        $categoryHelper = new PMF_Category_Helper();
        $categoryId     = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $categoryData   = array(
            'id'          => $categoryId,
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'catlang', FILTER_SANITIZE_STRING),
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
                printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_translated']);
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
                
                printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p class="error">%s</p>', $db->error());
            }
        }
        
        // All the other translations            
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
        if ($faqconfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {

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

        $categoryId   = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $categoryLang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $deleteAll    = PMF_Filter::filterInput(INPUT_POST, 'deleteall', FILTER_SANITIZE_STRING);
        
        if ('yes' == $deleteAll) {
            $categoryNode->setLanguage($categoryLang);
            $categoryRelations->setLanguage($categoryLang);
        }
        
        if ($categoryNode->delete($categoryId) && $categoryRelations->delete($categoryId) &&
            $categoryUser->delete($categoryId) && $categoryGroup->delete($categoryId)) {
            
            printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p class="error">%s</p>', $db->error());
        }
    }

    // Moves a category
    if ($action == 'changecategory') {

        $firstCategoryId  = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $secondCategoryId = PMF_Filter::filterInput(INPUT_POST, 'change', FILTER_VALIDATE_INT);

        if ($categoryHelper->swapCategories($firstCategoryId, $secondCategoryId)) {
            printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Pastes a category
    if ($action == 'pastecategory') {

        $categoryId   = PMF_Filter::filterInput(INPUT_POST, 'cat', FILTER_VALIDATE_INT);
        $parentId     = PMF_Filter::filterInput(INPUT_POST, 'after', FILTER_VALIDATE_INT);
        $categoryData = $categoryNode->fetch($categoryId);
        
        $categoryData->parent_id = $parentId;
        
        if ($categoryNode->update($categoryId, (array)$categoryData)) {
            printf('<p class="success">%s</p>', $PMF_LANG['ad_categ_updated']);
        } else {
            printf('<p class="error">%s<br />%s</p>', $PMF_LANG['ad_categ_paste_error'], $db->error());
        }
    }

    // Lists all categories
    $lang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING, $LANGCODE);

    $categoryDataProvider = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
    $categoryTreeHelper   = new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryDataProvider));
    
    foreach ($categoryTreeHelper as $categoryId => $categoryName) {
        
        $indent       = str_repeat('&nbsp;', $categoryTreeHelper->indent);
        $categoryLang = $categoryTreeHelper->getInnerIterator()->current()->getLanguage();
        $parentId     = $categoryTreeHelper->getInnerIterator()->current()->getParentId();
        
        // show category name
        printf("<p>%s<strong style=\"vertical-align: top;\">&middot; %s</strong> ",
            $indent,
            $categoryName);

        if ($categoryLang == $lang) {
           // add sub category (if actual language)
           printf('<a href="?action=addcategory&amp;cat=%s&amp;lang=%s"><img src="images/add.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
               $categoryId,
               $categoryLang,
               $PMF_LANG['ad_quick_category'],
               $PMF_LANG['ad_quick_category']);

           // rename (sub) category (if actual language)
           printf('<a href="?action=editcategory&amp;cat=%s"><img src="images/edit.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
               $categoryId,
               $PMF_LANG['ad_kateg_rename'],
               $PMF_LANG['ad_kateg_rename']);
        }

        // translate category (always)
        printf('<a href="?action=translatecategory&amp;cat=%s"><img src="images/translate.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>&nbsp;',
            $categoryId,
            $PMF_LANG['ad_categ_translate'],
            $PMF_LANG['ad_categ_translate']);

        // delete (sub) category (if actual language)
        if (!$categoryTreeHelper->callHasChildren() && $categoryLang == $lang) {
            printf('<a href="?action=deletecategory&amp;cat=%s&amp;lang=%s"><img src="images/delete.png" width="16" height="16" alt="%s" title="%s" border="0" /></a>&nbsp;',
                $categoryId,
                $categoryLang,
                $PMF_LANG['ad_categ_delete'],
                $PMF_LANG['ad_categ_delete']);
        }

        if ($categoryLang == $lang) {
            // cut category (if actual language)
            printf('<a href="?action=cutcategory&amp;cat=%s"><img src="images/cut.png" width="16" height="16" alt="%s" border="0" title="%s" /></a>&nbsp;',
                $categoryId,
                $PMF_LANG['ad_categ_cut'],
                $PMF_LANG['ad_categ_cut']);
            
            if ($categoryHelper->numParent($parentId) > 1) {
                // move category (if actual language) AND more than 1 category at the same level)
                printf('<a href="?action=movecategory&amp;cat=%s&amp;parent_id=%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>',
                    $categoryId,
                    $parentId,
                    $PMF_LANG['ad_categ_move'],
                    $PMF_LANG['ad_categ_move']);
            }
        }

        if (count($category->getChildren($cat['id'])) != 0) {
            // Open a div for content all the children
            printf("<div id=\"div_%d\" style=\"display: none;\">", $cat['id']);
            $lastOpen = true;
        }

        $level = $cat['indent'];
    }

    if ($lastOpen) {
        // Close the last div open if is any
        printf("</div>");
    }

    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark']);
?>
<script>
    /**
     * Toggle fieldsets
     *
     * @param string fieldset ID of the fieldset
     *
     * @return void
     */
    function toggleFieldset(fieldset)
    {
        if ($('#div_' + fieldset).css('display') == 'none') {
            $('#div_' + fieldset).fadeIn('fast');
        } else {
            $('#div_' + fieldset).fadeOut('fast');
        }
    }
</script>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
