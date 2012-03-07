<?php
/**
 * build table of all categories in all languages
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header>
            <h2><?php print $PMF_LANG['ad_menu_categ_structure'] ?></h2>
        </header>
<?php
if ($permission['editcateg']) {

    $category        = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $currentLink     = $_SERVER['SCRIPT_NAME'];
    $currentLanguage = $languageCodes[strtoupper($LANGCODE)];
    $all_languages   = array();
    $all_lang        = array();
    $showcat         = PMF_Filter::filterInput(INPUT_POST, 'showcat', FILTER_SANITIZE_STRING);

    // translate an existing category
    if (!is_null($showcat) && $showcat == 'yes') {

        $parent_id     = PMF_Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $category_data = array(
            'id'          => PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang'        => PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING),
            'parent_id'   => $parent_id,
            'name'        => PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING),
            'description' => PMF_Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_STRING),
            'user_id'     => PMF_Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        // translate.category only returns non-existent languages to translate too
        if ($category->addCategory($category_data, $parent_id, $category_data['id'])) {
            printf('<p>%s</p>', $PMF_LANG['ad_categ_translated']);
        } else {
            printf('<p>%s</p>', $db->error());
        }
    }

    $category->getMissingCategories();
    $category->buildTree();
?>
        <table class="table table-striped">
        <thead>
            <tr>
                <th><?php print $currentLanguage ?></th>
                <?php
                // get languages in use for all categories
                $all_languages = PMF_Utils::languageAvailable(0, $table='faqcategories');
                foreach ($all_languages as $lang) {
                   $all_lang[$lang] = $languageCodes[strtoupper($lang)];
                }
                asort($all_lang);
                foreach($all_lang as $lang => $language) {
                   if ($language != $currentLanguage) {
                      printf("<th>" . $language . "</th>\n", $language);
                   }
                }
                ?>
            </tr>
        </thead>
        <tbody>
<?php
    foreach ($category->catTree as $cat) {

        print "<tr>\n";

        $indent = '';
        for ($i = 0; $i < $cat['indent']; $i++) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        // category translated in this language?
        ($cat['lang'] == $LANGCODE) ? $catname = $cat['name'] : $catname = $cat['name'].' ('.$languageCodes[strtoupper($cat['lang'])].')';

        // show category name in actual language
        print '<td>';
        if ($cat['lang'] != $LANGCODE) {
           // translate category
           printf(
               '<a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><img src="images/translate.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>',
               $currentLink,
               $cat['id'],
               $LANGCODE,
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate'],
               $PMF_LANG['ad_categ_translate']);
        }
        printf("&nbsp;%s<strong>%s</strong>",
            $indent,
            $catname);
        print "</td>\n";


        // get languages in use for categories
        $id_languages = $category->getCategoryLanguagesTranslated($cat["id"]);

        foreach($all_lang as $lang => $language) {
            if ($language == $currentLanguage) {
                continue;
            }

            if (array_key_exists($language, $id_languages)) {
                $spokenLanguage = PMF_String::preg_replace('/\(.*\)/', '', $id_languages[$language]);
                printf('<td title="%s: %s">',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage);
                printf('<img src="images/ok.gif" width="22" height="18" border="0" title="%s: %s" alt=%s: %s\" /></td>',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage,
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage);
            } else {
                printf('<td><a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s">',
                    $currentLink,
                    $cat['id'],
                    $lang,
                    $PMF_LANG['ad_categ_translate']);
                printf('<img src="images/translate.png" width="16" height="16" border="0" title="%s" alt="%s" /></a>',
                    $PMF_LANG['ad_categ_translate'],
                    $PMF_LANG['ad_categ_translate']);
            }
            print "</td>\n";
        }
        print "</tr>\n";
    }
?>
        </tbody>
        </table>
<?php
    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_overview']);
} else {
    print $PMF_LANG['err_NotAuth'];
}