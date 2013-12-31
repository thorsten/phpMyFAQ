<?php
/**
 * Read in files for the translation and show them inside a form.
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-11
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["addtranslation"]) {
    unset($_SESSION['trans']);


    $templateVars = array(
        'PMF_LANG'  => $PMF_LANG,
        'languages' => array()
    );

    $avaliableLanguages = array_keys(PMF_Language::getAvailableLanguages());
    foreach ($languageCodes as $langCode => $langName) {
        if (!in_array(strtolower($langCode), $avaliableLanguages)) {
            $templateVars['languages'][$langCode] = $langName;
        }
    }

    $twig->loadTemplate('translation/add.twig')
        ->display($templateVars);
} else {
    require 'noperm.php';
}
