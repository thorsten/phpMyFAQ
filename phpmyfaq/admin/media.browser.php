<?php

/**
 * Media browser backend for TinyMCE v6
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-18
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));
const IS_VALID_PHPMYFAQ = null;

require PMF_ROOT_DIR . '/src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);

//
// Get language (default: english)
//
$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
$faqConfig->setLanguage($Language);

if (!Language::isASupportedLanguage($faqLangCode)) {
    $faqLangCode = 'en';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_TRANSLATION_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode)
        ->setMultiByteLanguage();
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

$allowedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'mov', 'mpg', 'mp4', 'ogg', 'wmv', 'avi', 'webm'];

$twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
$template = $twig->loadTemplate('./admin/content/media.browser.twig');

$images = [];
if (is_dir(PMF_CONTENT_DIR . '/user/images')) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PMF_CONTENT_DIR . '/user/images'));
    foreach ($files as $file) {
        if ($file->isDir() || !in_array(strtolower($file->getExtension()), $allowedExtensions)) {
            continue;
        }
        $path = str_replace(dirname(__DIR__) . '/', '', (string)$file->getPath());
        $images[] = $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename();
    }
}

$templateVars = [
    'metaLanguage' => Translation::get('metaLanguage'),
    'notAuthenticated' => !$user->isLoggedIn(),
    'msgNotAuthenticated' => Translation::get('err_NotAuth'),
    'msgMediaSearch' => Translation::get('ad_media_name_search'),
    'isImageDirectoryMissing' => !is_dir(PMF_CONTENT_DIR . '/user/images'),
    'msgImageDirectoryMissing' => sprintf(Translation::get('ad_dir_missing'), '/images'),
    'images' => $images,
];

echo $template->render($templateVars);
