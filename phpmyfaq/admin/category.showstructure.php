<?php
/**
 * build table of all categories in all languages
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
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_categ_structure']);

if ($permission['editcateg']) {

    $categoryNode    = new PMF_Category_Node();
    $currentLanguage = $languageCodes[strtoupper($LANGCODE)];
    $all_lang        = array();
    $showcat         = PMF_Filter::filterInput(INPUT_POST, 'showcat', FILTER_SANITIZE_STRING);

    // translate an existing category
    if (!is_null($showcat) && $showcat == 'yes') {
        $categoryData = array(
            'id'          => PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'parent_id'   => PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT),
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        // translate.category only returns non-existent languages to translate too
        if ($categoryNode->create($categoryData)) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_translated']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    print "\n\n<table class=\"ssc\">\n";

    print "<tr>\n";
    print "<td class=\"sscTitle\">" . $currentLanguage . "</th>\n";

    // get languages in use for all categories
    $allLanguages = PMF_Utils::languageAvailable(0, $table='faqcategories');
    foreach ($allLanguages as $lang) {
       $all_lang[$lang] = $languageCodes[strtoupper($lang)];
    }
    asort($all_lang);
    foreach ($all_lang as $lang => $language) {
        if ($language != $currentLanguage) {
            print "<td class=\"sscTitle\">" . $language . "</td>\n";
        }
    }

    print "</tr>\n";

    $categoryDataProvider = new PMF_Category_Tree_DataProvider_SingleQuery($LANGCODE);
    $categoryTreeHelper   = new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryDataProvider));
    
    foreach ($categoryTreeHelper as $categoryId => $categoryName) {
        
        print "<tr>\n";
        
        $indent       = str_repeat('&nbsp;', $categoryTreeHelper->indent);
        $categoryLang = $categoryTreeHelper->getInnerIterator()->current()->getLanguage();
        
        // category translated in this language?
        ($categoryLang == $LANGCODE) ? $catname = $categoryLang : $catname = $categoryName.' ('.$languageCodes[strtoupper($categoryLang)].')';
        ($categoryLang == $LANGCODE) ? $desc = "sscDesc" : $desc = "sscDescNA";

        // show category name in actual language
        printf("<td class=\"%s\">", $desc);
        
        if ($categoryLang != $LANGCODE) {
           // translate category
           printf('<a href="index.php?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><img src="images/translate2.gif" width="13" height="16" border="0" title="%s" alt="%s" /></a>',
               $categoryId,
               $LANGCODE,
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate']);
        }
        printf("&nbsp;%s<strong>&middot; %s</strong>",
            $indent,
            $categoryName);
        print "</td>\n";

        /*
        // get languages in use for categories
        $id_languages = $category->getCategoryLanguagesTranslated($cat["id"]);

        foreach($all_lang as $lang => $language) {
           if ($language == $currentLanguage) {
              continue;
           }
           if (array_key_exists($language, $id_languages)) {
              printf("<td class=\"sscDesc\" title=\"%s: %s\"><img src=\"images/ok.gif\" width=\"22\" height=\"18\" border=\"0\" title=\"%s: %s\" alt=\"%s: %s\" /></td>\n",
                  $PMF_LANG["ad_categ_titel"],
                  PMF_String::preg_replace('/\(.*\)/', '', $id_languages[$language]),
                  $PMF_LANG["ad_categ_titel"],
                  PMF_String::preg_replace('/\(.*\)/', '', $id_languages[$language]),
                  $PMF_LANG["ad_categ_titel"],
                  PMF_String::preg_replace('/\(.*\)/', '', $id_languages[$language]));
           }
           else {
           print "<td class=\"sscDescNA\">";
           printf('<a href="index.php?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><img src="images/translate2.gif" width="13" height="16" border="0" title="%s" alt="%s" /></a>',
               $cat['id'],
               $lang,
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate']);
           }
           print "</td>\n";
        }
        */
        
        print "</tr>\n";
    }
    print "</table>\n";
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_overview']);
} else {
    print $PMF_LANG['err_NotAuth'];
}