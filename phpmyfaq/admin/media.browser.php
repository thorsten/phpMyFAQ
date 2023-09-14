<?php

/**
 * Media browser backend for TinyMCE v6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-18
 */

use phpMyFAQ\Component\Alert;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));
const IS_VALID_PHPMYFAQ = null;

require PMF_ROOT_DIR . '/src/Bootstrap.php';

$Language = new Language($faqConfig);
$faqLangCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR . '/lang/language_en.php';

if (isset($faqLangCode) && Language::isASupportedLanguage($faqLangCode)) {
    require_once PMF_ROOT_DIR . '/lang/language_' . $faqLangCode . '.php';
} else {
    $faqLangCode = 'en';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($faqLangCode);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Initializing static string wrapper
//
Strings::init($faqLangCode);

$user = CurrentUser::getCurrentUser($faqConfig);
?>
<!DOCTYPE html>
<html lang="<?= Translation::get('metaLanguage'); ?>">
<head>
    <meta charset="UTF-8">
    <title>phpMyFAQ Media Browser</title>
    <style>
        @import url('../assets/dist/admin.css');
        body { padding: 10px; }
    </style>
</head>
<body>

<form action="" method="post">
    <div class="input-group mb-3">
        <input type="search" class="form-control" placeholder="<?= Translation::get('ad_media_name_search') ?>"
               id="filter" value="" aria-label="Recipient's username" aria-describedby="search">
        <span class="input-group-text" id="search"><i aria-hidden="true" class="fa fa-search"></i></span>
    </div>
</form>

<?php
$allowedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'mov', 'mpg', 'mp4', 'ogg', 'wmv', 'avi', 'webm'];

if (!$user->isLoggedIn() && $error) {
    Alert::danger('err_NotAuth');
} else {
    if (!is_dir(PMF_ROOT_DIR . '/images')) {
        echo '<p class="alert alert-danger">' . sprintf(Translation::get('ad_dir_missing'), '/images') . '</p>';
    } else {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PMF_ROOT_DIR . '/images/'));
        foreach ($files as $file) {
            if ($file->isDir() || !in_array($file->getExtension(), $allowedExtensions)) {
                continue;
            }
            $path = str_replace(dirname(__DIR__) . '/', '', (string) $file->getPath());
            printf(
                '<div class="mce-file" data-src="%s"><img src="%s" class="mce-file-preview" alt="%s">%s</div>',
                $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename(),
                $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename(),
                $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename(),
                $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename()
            );
        }
    }
}

?>
<script src="../assets/dist/backend.js?<?= time(); ?>"></script>
<script>
    const filterInput = document.getElementById('filter');
    const fileDivs = document.querySelectorAll('div.mce-file');

    if (filterInput) {
        filterInput.addEventListener('keyup', function () {
            const filter = this.value;
            fileDivs.forEach((fileDiv) => {
                if (fileDiv.textContent.search(new RegExp(filter, 'i')) < 0) {
                    fileDiv.style.display = 'none';
                } else {
                    fileDiv.style.display = 'block';
                }
            });
        });
    }

    document.addEventListener('click', (event) => {
        if (event.target.matches('div.mce-file')) {
            const src = event.target.getAttribute('data-src');
            window.parent.postMessage({
                mceAction: 'phpMyFAQMediaBrowserAction',
                url: src,
            }, '*');
        }
    });
</script>

</body>
</html>
