<?php

/**
 * Builds a table of all categories in all languages.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Rudi Ferrari <bookcrossers@gmx.de>
 * @copyright 2006-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-09-18
 */

use phpMyFAQ\Category;
use phpMyFAQ\Component\Alert;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

?>

  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-folder"></i> <?= Translation::get('ad_menu_categ_structure') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
      <div class="btn-group mr-2">
        <a class="btn btn-sm btn-success" href="?action=addcategory">
          <i aria-hidden="true" class="fa fa-folder-plus"></i> <?= Translation::get('ad_kateg_add'); ?>
        </a>
      </div>
    </div>
  </div>

<?php
if ($user->perm->hasPermission($user->getUserId(), 'editcateg')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $currentLanguage = LanguageCodes::get($faqLangCode);
    $allLanguages = [];
    $all_lang = [];
    $showCategory = Filter::filterInput(INPUT_POST, 'showCategory', FILTER_SANITIZE_SPECIAL_CHARS);

    // translate an existing category
    if ($showCategory === 'yes') {
        $parentId = Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT);
        $categoryData = [
            'id' => Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT),
            'lang' => Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS),
            'parent_id' => $parentId,
            'name' => Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS),
            'description' => Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS),
            'user_id' => Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT)
        ];

        // translate.category only returns non-existent languages to translate too
        if ($category->addCategory($categoryData, $parentId, $categoryData['id'])) {
            echo Alert::success('ad_categ_translated');
        } else {
            echo Alert::danger('ad_adus_dberr', $faqConfig->getDb()->error());
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
                $allLanguages = $faqConfig->getLanguage()->isLanguageAvailable(0, $table = 'faqcategories');
                foreach ($allLanguages as $lang) {
                    $all_lang[$lang] = LanguageCodes::get($lang);
                }
                asort($all_lang);
                foreach ($all_lang as $language) {
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

        $indent = str_repeat('&nbsp;&nbsp;&nbsp;', $cat['indent']);
        // category translated in this language?
        ($cat['lang'] == $faqLangCode) ? $categoryName = $cat['name'] : $categoryName = $cat['name'] . ' (' . LanguageCodes::get($cat['lang']) . ')';

        // show category name in actual language
        print '<td>';
        if ($cat['lang'] != $faqLangCode) {
            // translate category
            printf(
                '<a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s"><span title="%s" class="fa fa-globe"></span></a></a>',
                $request->getBasePath(),
                $cat['id'],
                $faqLangCode,
                Translation::get('ad_categ_translate'),
                Translation::get('ad_categ_translate')
            );
        }
        printf(
            '&nbsp;%s<strong>%s</strong>',
            $indent,
            $categoryName
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
                    Translation::get('ad_categ_titel'),
                    $spokenLanguage
                );
                printf(
                    '<span title="%s: %s" class="badge bg-success"><i aria-hidden="true" class="fa fa-check"></i></span></td>',
                    Translation::get('ad_categ_titel'),
                    $spokenLanguage
                );
            } else {
                printf(
                    '<td class="text-center"><a href="%s?action=translatecategory&amp;cat=%s&amp;trlang=%s" title="%s">',
                    $request->getBasePath(),
                    $cat['id'],
                    $lang,
                    Translation::get('ad_categ_translate')
                );
                printf(
                    '<span title="%s" class="badge bg-primary"><i aria-hidden="true" class="fa fa-globe fa-white"></i></span></a>',
                    Translation::get('ad_categ_translate')
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
    printf('<p>%s</p>', Translation::get('ad_categ_remark_overview'));
} else {
    print Translation::get('err_NotAuth');
}
