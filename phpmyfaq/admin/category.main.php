<?php
/**
* $Id: category.main.php,v 1.13 2006-06-11 18:09:19 matteo Exp $
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

    // Save a new category
    if (isset($_POST['aktion']) && $_POST['aktion'] == 'savecategory') {

        $id = $db->nextID(SQLPREFIX.'faqcategories', 'id');
        $lang = $db->escape_string($_POST['lang']);
        $parent_id = (int)$_POST['parent_id'];
        $name = $db->escape_string($_POST['name']);
        $description = $db->escape_string($_POST['description']);

        $query = sprintf("INSERT INTO %sfaqcategories (id, lang, parent_id, name, description) VALUES (%d, '%s', %d, '%s', '%s')", SQLPREFIX, $id, $lang, $parent_id, $name, $description);

        if ($db->query($query)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_added']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    // Updates an existing category
    if (isset($_POST['aktion']) && $_POST['aktion'] == 'updatecategory') {

        $id = (int)$_POST['cat'];
        $lang = $db->escape_string($_POST['lang']);
        $name = $db->escape_string($_POST['name']);
        $description = $db->escape_string($_POST['description']);

        // get actual language
        $result_language = $db->query('SELECT lang FROM '.SQLPREFIX.'faqcategories WHERE id = '.$id.' AND lang = "'.$lang.'"');
        if (0 == $db->num_rows($result_language)) {
            $query = sprintf("INSERT INTO %sfaqcategories (id, lang, name, description) VALUES (%d, '%s', '%s', '%s')", SQLPREFIX, $id, $lang, $name, $description);
            if ($db->query($query)) {
                printf('<p>%s</p>', $PMF_LANG['ad_categ_added']);
            } else {
                printf('<p>%s</p>', $db->error());
            }
        } else {
            $query = sprintf("UPDATE %sfaqcategories SET name = '%s', description = '%s' WHERE id = %d AND lang = '%s'", SQLPREFIX, $name, $description, $id, $lang);
            if ($db->query($query)) {
   	            printf('<p>%s</p>', $PMF_LANG['ad_categ_updated']);
            } else {
                printf('<p>%s</p>', $db->error());
            }
        }
    }

    // Deletes an existing category
    if ($permission['delcateg'] && isset($_POST['aktion']) && $_POST['aktion'] == 'removecategory') {

        $id = (int)$_POST['cat'];
        $lang = $db->escape_string($_POST['lang']);

        $query_cateories = sprintf("DELETE FROM %sfaqcategories WHERE id = %d AND lang = '%s'", SQLPREFIX, $id, $lang);
        $query_relations = sprintf("DELETE FROM %sfaqcategoryrelations WHERE category_id = %d AND category_lang = '%s'", SQLPREFIX, $id, $lang);

        if ($db->query($query_cateories) && $db->query($query_relations)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_deleted']);
        } else {
            printf('<p>%s</p>', $db->error());
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
    $tree->buildTree();

    foreach ($tree->catTree as $cat) {
        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        printf("%s<strong style=\"vertical-align: top;\">&middot; %s</strong> ", $indent, $cat["name"]);
        printf('<a href="%s&amp;aktion=addcategory&amp;cat=%s" title="%s"><img src="images/add.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>', $currentLink, $cat['id'], $PMF_LANG['ad_kateg_add'], $PMF_LANG['ad_kateg_add'], $PMF_LANG['ad_kateg_add']);
        printf('<a href="%s&amp;aktion=editcategory&amp;cat=%s" title="%s"><img src="images/edit.gif" width="18" height="18" border="0" title="%s" alt="%s" /></a>', $currentLink, $cat['id'], $PMF_LANG['ad_kateg_rename'], $PMF_LANG['ad_kateg_rename'], $PMF_LANG['ad_kateg_rename']);
        if (count($tree->getChildren($cat['id'])) == 0) {
            printf('<a href="%s&amp;aktion=deletecategory&amp;cat=%s&amp;lang=%s" title="%s"><img src="images/delete.gif" width="17" height="18" alt="%s" title="%s" border="0" /></a>', $currentLink, $cat['id'], $cat['lang'], $PMF_LANG['ad_categ_delete'], $PMF_LANG['ad_categ_delete'], $PMF_LANG['ad_categ_delete']);
        }
        printf('<a href="%s&amp;aktion=cutcategory&amp;cat=%s" title="%s"><img src="images/cut.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>', $currentLink, $cat['id'], $PMF_LANG['ad_categ_cut'], $PMF_LANG['ad_categ_cut'], $PMF_LANG['ad_categ_cut']);
        printf('<a href="%s&amp;aktion=movecategory&amp;cat=%s&amp;parent_id=%s" title="%s"><img src="images/move.gif" width="16" height="16" alt="%s" border="0" title="%s" /></a>', $currentLink, $cat['id'], $cat['parent_id'], $PMF_LANG['ad_categ_move'], $PMF_LANG['ad_categ_move'], $PMF_LANG['ad_categ_move']);
        print "<br />";
    }
?>
	<p><img src="images/arrow.gif" width="11" height="11" alt="" border="0" /> <a href="<?php print $currentLink.'&amp;aktion=addcategory'; ?>"><?php print $PMF_LANG['ad_kateg_add']; ?></a></p>
	<p><?php print $PMF_LANG['ad_categ_remark']; ?></p>
<?php
} else {
	print $PMF_LANG['err_NotAuth'];
}
