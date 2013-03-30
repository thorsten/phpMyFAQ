<?php
/**
 * build table of all categories in all languages
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
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2006-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['SERVER_NAME'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editcateg']) {

    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $currentLink     = $_SERVER['SCRIPT_NAME'];
    $currentLanguage = $languageCodes[strtoupper($LANGCODE)];
    $all_languages   = array();
    $all_lang        = array();
    $showcat         = PMF_Filter::filterInput(INPUT_POST, 'showcat', FILTER_SANITIZE_STRING);

    $templateVars = array(
        'PMF_LANG'       => $PMF_LANG,
        'allLanguages'   => array($currentLanguage),
        'categoryTable'  => array(),
        'errorMessage'   => '',
        'successMessage' => ''
    );

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
            $templateVars['successMessage'] = $PMF_LANG['ad_categ_translated'];
        } else {
            $templateVars['errorMessage'] = $faqConfig->getDb()->error();
        }
    }

    $category->getMissingCategories();
    $category->buildTree();

    // get languages in use for all categories
    $all_languages = $faqConfig->getLanguage()->languageAvailable(0, $table = 'faqcategories');
    foreach ($all_languages as $lang) {
        $all_lang[$lang] = $languageCodes[strtoupper($lang)];
    }
    asort($all_lang);
    foreach ($all_lang as $lang => $language) {
        if ($language != $currentLanguage) {
            $templateVars['allLanguages'][] = $language;
        }
    }

    foreach ($category->catTree as $cat) {
        $currentRow = '';

        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['indent']);
        // category translated in this language?
        ($cat['lang'] == $LANGCODE) ? $catname = $cat['name'] : $catname = $cat['name'] . ' (' . $languageCodes[strtoupper($cat['lang'])] . ')';

        // show category name in actual language
        $currentRow .= '<td>';
        if ($cat['lang'] != $LANGCODE) {
            // translate category
            $currentRow .= sprintf(
                '<a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><span title="%s" class="icon-share"></span></a></a>',
                $currentLink,
                $cat['id'],
                $LANGCODE,
                $PMF_LANG['ad_categ_translate'],
                $PMF_LANG['ad_categ_translate']
            );
        }
        $currentRow .= sprintf("&nbsp;%s<strong>%s</strong>",
            $indent,
            $catname);
        $currentRow .= "</td>\n";

        // get languages in use for categories
        $id_languages = $category->getCategoryLanguagesTranslated($cat["id"]);

        foreach ($all_lang as $lang => $language) {
            if ($language == $currentLanguage) {
                continue;
            }

            if (array_key_exists($language, $id_languages)) {
                $spokenLanguage = PMF_String::preg_replace('/\(.*\)/', '', $id_languages[$language]);
                $currentRow .= sprintf('<td title="%s: %s">',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage
                );
                $currentRow .= sprintf(
                    '<span title="%s: %s" class="label label-success"><i class="icon-check icon-white"></i></span></td>',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage
                );
            } else {
                $currentRow .= sprintf('<td><a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s">',
                    $currentLink,
                    $cat['id'],
                    $lang,
                    $PMF_LANG['ad_categ_translate']);
                $currentRow .= sprintf(
                    '<span title="%s" class="label label-inverse"><i class="icon-share icon-white"></i></span></a>',
                    $PMF_LANG['ad_categ_translate']
                );
            }
            $currentRow .= "</td>\n";
        }

        $templateVars['categoryTable'][] = $currentRow;
    }

    $twig->loadTemplate('category/showstructure.twig')
        ->display($templateVars);
} else {
    require 'noperm.php';
}