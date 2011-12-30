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
 * @copyright 2006-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
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

    $categoryNode    = new PMF_Category_Node();
    $currentLanguage = $languageCodes[strtoupper($LANGCODE)];
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

    print "\n\n<table>\n";
    print "<tr>\n";
    print "    <th>" . $currentLanguage . "</th>\n";

    // get languages in use for all categories
    $allLanguages = PMF_Utils::languageAvailable(0, $table='faqcategories');
    asort($allLanguages);
    foreach ($allLanguages as $language) {
        if ($languageCodes[strtoupper($language)] != $currentLanguage) {
            print "    <th>" . $languageCodes[strtoupper($language)] . "</th>\n";
        }
    }

    $categoryDataProvider = new PMF_Category_Tree_DataProvider_SingleQuery();
    $categoryTreeHelper   = new PMF_Category_Tree_Helper(new PMF_Category_Tree($categoryDataProvider));
    $categoryHelper       = new PMF_Category_Helper();
    foreach ($categoryTreeHelper as $categoryId => $categoryName) {
        
        $indent       = str_repeat('&nbsp;', $categoryTreeHelper->indent);
        $categoryLang = $categoryTreeHelper->getInnerIterator()->current()->getLanguage();
        
        if ($categoryLang == $LANGCODE) {
            print "</tr>\n";
            print "<tr>\n";
        }
        
        printf("    <td>&nbsp;%s<strong>&middot; %s</strong>&nbsp</td>\n", $indent, $categoryName);
        foreach ($allLanguages as $language) {
            if ($language != $categoryLang && !$categoryHelper->hasTranslation($categoryId, $language)) {
                // translate category
                printf("    <td class=\"needsTranslation\">&nbsp;%s<strong>&middot; %s</strong>&nbsp", 
                    $indent, 
                    $categoryName);
                printf('<a href="index.php?action=translatecategory&amp;cat=%d&amp;trlang=%s" title="%s">',
                    $categoryId,
                    $LANGCODE,
                    $PMF_LANG['ad_categ_translate']);
                printf('<img src="images/translate.png" width="13" height="16" border="0" title="%s" alt="%s" /></a>',
                    $PMF_LANG['ad_categ_translate'],
                    $PMF_LANG['ad_categ_translate']);
                print "</td>\n";
            }
        }


    }
    print "</tr>\n</table>\n";

    printf('<p>%s</p>', $PMF_LANG['ad_categ_remark_overview']);
} else {
    print $PMF_LANG['err_NotAuth'];
}