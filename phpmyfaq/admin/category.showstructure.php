<?php

/**
 * Builds a table of all categories in all languages.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-09-18
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-folder"></i> <?= $PMF_LANG['ad_menu_categ_structure'] ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <a class="btn btn-sm btn-success" href="?action=addcategory">
          <i aria-hidden="true" class="fa fa-folder-plus"></i> <?= $PMF_LANG['ad_kateg_add']; ?>
        </a>
      </div>
    </div>
  </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $currentLink = $_SERVER['SCRIPT_NAME'];
    $currentLanguage = $languageCodes[strtoupper($faqLangCode)];
    $all_languages = [];
    $all_lang = [];
    $showCategory = Filter::filterInput(INPUT_POST, 'showCategory', FILTER_UNSAFE_RAW);

    // translate an existing category
    if (!is_null($showCategory) && $showCategory == 'yes') {
        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = [
            'id' => Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang' => Filter::filterInput(INPUT_POST, 'lang', FILTER_UNSAFE_RAW),
            'parent_id' => $parentId,
            'name' => Filter::filterInput(INPUT_POST, 'name', FILTER_UNSAFE_RAW),
            'description' => Filter::filterInput(INPUT_POST, 'description', FILTER_UNSAFE_RAW),
            'user_id' => Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT)
        ];

        // translate.category only returns non-existent languages to translate too
        if ($category->addCategory($categoryData, $parentId, $categoryData['id'])) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_categ_translated']);
        } else {
            printf('<p class="alert alert-danger">%s</p>', $faqConfig->getDb()->error());
        }
    }

    $category->getMissingCategories();
    $category->buildCategoryTree();
    ?>
        <table class="table table-light table-striped align-middle">
        <thead class="thead-dark">
            <tr>
                <th><?= $currentLanguage ?></th>
                <?php
                // get languages in use for all categories
                $all_languages = $faqConfig->getLanguage()->languageAvailable(0, $table = 'faqcategories');
                foreach ($all_languages as $lang) {
                    $all_lang[$lang] = $languageCodes[strtoupper($lang)];
                }
                asort($all_lang);
                foreach ($all_lang as $lang => $language) {
                    if ($language != $currentLanguage) {
                        printf('<th class="text-center">' . $language . "</th>\n", $language);
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
    <?php
    foreach ($category->getCategoryTree() as $cat) {
        print "<tr>\n";

        $indent = '';
        for ($i = 0; $i < $cat['indent']; ++$i) {
            $indent .= '&nbsp;&nbsp;&nbsp;';
        }
        // category translated in this language?
        ($cat['lang'] == $faqLangCode) ? $catname = $cat['name'] : $catname = $cat['name'] . ' (' . $languageCodes[strtoupper($cat['lang'])] . ')';

        // show category name in actual language
        print '<td>';
        if ($cat['lang'] != $faqLangCode) {
            // translate category
            printf(
                '<a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><span title="%s" class="fa fa-globe"></span></a></a>',
                $currentLink,
                $cat['id'],
                $faqLangCode,
                $PMF_LANG['ad_categ_translate'],
                $PMF_LANG['ad_categ_translate']
            );
        }
        printf(
            '&nbsp;%s<strong>%s</strong>',
            $indent,
            $catname
        );
        print "</td>\n";

        // get languages in use for categories
        $id_languages = $category->getCategoryLanguagesTranslated($cat['id']);

        foreach ($all_lang as $lang => $language) {
            if ($language == $currentLanguage) {
                continue;
            }

            if (array_key_exists($language, $id_languages)) {
                $spokenLanguage = Strings::preg_replace('/\(.*\)/', '', $id_languages[$language]);
                printf(
                    '<td class="text-center" title="%s: %s">',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage
                );
                printf(
                    '<span title="%s: %s" class="badge badge-success"><i aria-hidden="true" class="fa fa-check"></i></span></td>',
                    $PMF_LANG['ad_categ_titel'],
                    $spokenLanguage
                );
            } else {
                printf(
                    '<td class="text-center"><a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s">',
                    $currentLink,
                    $cat['id'],
                    $lang,
                    $PMF_LANG['ad_categ_translate']
                );
                printf(
                    '<span title="%s" class="badge badge-primary"><i aria-hidden="true" class="fa fa-globe fa-white"></i></span></a>',
                    $PMF_LANG['ad_categ_translate']
                );
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
