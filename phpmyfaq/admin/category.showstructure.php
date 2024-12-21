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
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-09-18
 */

use phpMyFAQ\Category;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$request = Request::createFromGlobals();

if ($user->perm->hasPermission($user->getUserId(), PermissionType::CATEGORY_EDIT->value)) {
    $templateVars = [];

    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $currentLanguage = LanguageCodes::get($faqLangCode);
    $allLanguages = [];
    $all_lang = [];
    $showCategory = Filter::filterInput(INPUT_POST, 'showCategory', FILTER_SANITIZE_SPECIAL_CHARS);

    // translate an existing category
    if ($showCategory === 'yes') {
        $categoryEntity = new CategoryEntity();
        $categoryEntity
            ->setId(Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT))
            ->setLang(Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setParentId(Filter::filterInput(INPUT_POST, 'parent_id', FILTER_VALIDATE_INT))
            ->setName(Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setDescription(Filter::filterInput(INPUT_POST, 'description', FILTER_SANITIZE_SPECIAL_CHARS))
            ->setUserId(Filter::filterInput(INPUT_POST, 'user_id', FILTER_VALIDATE_INT));

        // translate.category only returns non-existent languages to translate too
        if ($category->create($categoryEntity)) {
            $templateVars = [
                ...$templateVars,
                'message' => Translation::get('ad_categ_translated'),
                'message_typ' => 'success'
            ];
        } else {
            $templateVars = [
                ...$templateVars,
                'message_heading' => Translation::get('ad_adus_dberr'),
                'error' => $faqConfig->getDb()->error(),
                'message_typ' => 'danger'
            ];
        }
    }

    $category->getMissingCategories();
    $category->buildCategoryTree();

    // get languages in use for all categories
    $allLanguages = $faqConfig->getLanguage()->isLanguageAvailable(0, $table = 'faqcategories');
    foreach ($allLanguages as $lang) {
        $all_lang[$lang] = LanguageCodes::get($lang);
    }
    asort($all_lang);

    $translations = [];

    foreach ($category->getCategoryTree() as $cat) {
        // get languages of category which are already translated
        $id_languages = $category->getCategoryLanguagesTranslated($cat['id']);
        $spokenLanguage = [];

        // collect all languages of a category
        $translation_array = [];
        foreach ($id_languages as $lang => $title) {
            $translation_array[] = $lang;
        }
        $translations[$cat['id']] = $translation_array;
    }

    // convert language names to codes | currentLanguage is always first
    $all_lang_codes = [LanguageCodes::getKey($currentLanguage)];
    foreach ($all_lang as $language) {
        if ($language !== $currentLanguage) {
            $all_lang_codes[] = LanguageCodes::getKey($language);
        }
    }

    $templateVars = [
        ...$templateVars,
        'currentLanguage' => $currentLanguage,
        'allLangs' => $all_lang,
        'allLangCodes' => $all_lang_codes,
        'categoryTree' => $category->getCategoryTree(),
        'basePath' => $request->getBasePath(),
        'faqlangcode' => $faqLangCode,
        'msgCategoryRemark_overview' => Translation::get('msgCategoryRemark_overview'),
        'categoryNameLabel' => Translation::get('categoryNameLabel'),
        'ad_categ_translate' => Translation::get('ad_categ_translate'),
        'ad_menu_categ_structure' => Translation::get('ad_menu_categ_structure'),
        'msgAddCategory' => Translation::get('msgAddCategory'),
        'msgHeaderCategoryOverview' => Translation::get('msgHeaderCategoryOverview'),
        'msgCategory' => Translation::get('msgCategory'),
        'translations' => $translations,
        'ad_categ_translated' => Translation::get('ad_categ_translated')
    ];

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $template = $twig->loadTemplate('@admin/content/category.showstructure.twig');

    echo $template->render($templateVars);
} else {
    require __DIR__ . '/no-permission.php';
}
