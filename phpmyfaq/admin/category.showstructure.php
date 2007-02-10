<?php
/**
 * $Id: category.showstructure.php,v 1.4 2007-02-10 21:01:02 thorstenr Exp $
 *
 * build table of all categories in all languages
 *
 * @author      Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author      Rudi Ferrari <bookcrossers@gmx.de>
 * @since       2006-09-18
 * @copyright   (c) 2006-2007 phpMyFAQ Team
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

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_categ_structure']);

if ($permission['editcateg']) {

    $category = new PMF_Category($LANGCODE, $current_admin_user, $current_admin_groups);
    $currentLink = $_SERVER['PHP_SELF'].$linkext;
    $actual_language = $languageCodes[strtoupper($LANGCODE)];
    $all_languages = array();
    $all_lang = array();

    // translate an existing category
    if (isset($_POST['showcat']) && $_POST['showcat'] == 'yes') {

        $parent_id = (int)$_POST['parent_id'];

        $category_data = array(
            'id'            => (int)$_POST['id'],
            'lang'          => $db->escape_string($_POST['lang']),
            'parent_id'     => $parent_id,
            'name'          => $db->escape_string($_POST['name']),
            'description'   => $db->escape_string($_POST['description']),
            'user_id'       => (int)$_POST['user_id']);

        // translate.category only returns non-existent languages to translate too
        if ($category->addCategory($category_data, $parent_id, $category_data['id'])) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_translated']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    $category->getMissingCategories();
    $category->buildTree();

    print "\n\n<table class=\"ssc\">\n";

    print "<tr>\n";
    print "<td class=\"sscTitle\">" . $actual_language . "</th>\n";

    // get languages in use for all categories
    $all_languages = check4Language(0, $table='faqcategories');
    foreach ($all_languages as $lang) {
       $all_lang[$lang] = $languageCodes[strtoupper($lang)];
    }
    asort($all_lang);
    foreach($all_lang as $lang => $language) {
       if ($language != $actual_language) {
          print "<td class=\"sscTitle\">" . $language . "</td>\n";
       }
    }

    print "</tr>\n";

    foreach ($category->catTree as $cat) {

        print "<tr>\n";

        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        // category translated in this language?
        ($cat['lang'] == $LANGCODE) ? $catname = $cat['name'] : $catname = $cat['name'].' ('.$languageCodes[strtoupper($cat['lang'])].')';
        ($cat['lang'] == $LANGCODE) ? $desc = "sscDesc" : $desc = "sscDescNA";

        // show category name in actual language
        printf("<td class=\"%s\">",
            $desc);
        if ($cat['lang'] != $LANGCODE) {
           // translate category 
           printf('<a href="%s&amp;action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><img src="images/translate2.gif" width="13" height="16" border="0" title="%s" alt="%s" /></a>',
               $currentLink,
               $cat['id'],
               $LANGCODE,
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate']);
        }
        printf("&nbsp;%s<strong>&middot; %s</strong>",
            $indent,
            $catname);
        print "</td>\n";
 

        // get languages in use for categories
        $id_languages = $category->getCategoryLanguagesTranslated($cat["id"]);

        foreach($all_lang as $lang => $language) {
           if ($language == $actual_language) {
              continue;
           }
           if (array_key_exists($language,$id_languages)) {
              printf("<td class=\"sscDesc\" title=\"%s: %s\"><img src=\"images/ok.gif\" width=\"22\" height=\"18\" border=\"0\" title=\"%s: %s\" alt=\"%s: %s\" /></td>\n",
                  $PMF_LANG["ad_categ_titel"],
                  preg_replace('/\(.*\)/','',$id_languages[$language]),
                  $PMF_LANG["ad_categ_titel"],
                  preg_replace('/\(.*\)/','',$id_languages[$language]),
                  $PMF_LANG["ad_categ_titel"],
                  preg_replace('/\(.*\)/','',$id_languages[$language]));
           }
           else {
           print "<td class=\"sscDescNA\">";
           printf('<a href="%s&amp;action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><img src="images/translate2.gif" width="13" height="16" border="0" title="%s" alt="%s" /></a>',
               $currentLink,
               $cat['id'],
               $lang,
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate']);
           }
           print "</td>\n";
        }
        print "</tr>\n";
    }
    print "</table>\n";
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_overview']);
} else {
    print $PMF_LANG['err_NotAuth'];
}