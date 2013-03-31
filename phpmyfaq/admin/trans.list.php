<?php
/**
 * List avaliable interface translations and actions
 * depending on user right
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

clearstatcache();
unset($_SESSION['trans']);

$langDir            = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . "lang";
$transDir           = new DirectoryIterator($langDir);
$isTransDirWritable = is_writable($langDir);
$tt                 = new PMF_TransTool;

$templateVars = array(
    'PMF_LANG'                      => $PMF_LANG,
    'isTransDirWritable'            => $isTransDirWritable,
    'renderAddNewTranslationButton' => $permission["addtranslation"] && $isTransDirWritable,
    'translations'                  => array()
);

$sortedLangList = array();

foreach ($transDir as $file) {
    if ($file->isFile() && '.php' == PMF_String::substr($file, -4) && 'bak' != PMF_String::substr($file, -7, -4)) {
        $lang = str_replace(array('language_', '.php'), '', $file);

        /**
         * English is our exemplary language which won't be changed
         */
        if ('en' == $lang) {
            continue;
        }

        $sortedLangList[] = $lang;
    }
}

sort($sortedLangList);

while (list(, $lang) = each($sortedLangList)) {
    $isLangFileWritable   = is_writable($langDir . DIRECTORY_SEPARATOR . "language_$lang.php");
    $showActions          = $isTransDirWritable && $isLangFileWritable;
    $translatedPercentage = $tt->getTranslatedPercentage(
        $langDir . DIRECTORY_SEPARATOR . "language_en.php",
        $langDir . DIRECTORY_SEPARATOR . "language_$lang.php"
    );

    $currentTranslation = array(
        'editButtonUrl'          => '?action=transedit&translang=' . $lang,
        'isLangFileWritable'     => $isLangFileWritable,
        'lang'                   => $lang,
        'name'                   => $languageCodes[strtoupper($lang)],
        'renderDeleteButton'     => $permission["deltranslation"] && $showActions,
        'renderEditButton'       => $permission["edittranslation"] && $showActions,
        'renderSendToTeamButton' => $permission["edittranslation"] && $showActions,
        'translatedPercentage'   => $translatedPercentage . '%'
    );

    $templateVars['translations'][] = $currentTranslation;
}

$twig->loadTemplate('translation/list.twig')
    ->display($templateVars);