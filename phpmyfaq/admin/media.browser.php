<?php

/**
 * Media browser backend for TinyMCE v4
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-18
 */

use phpMyFAQ\Language;
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

$auth = false;
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if ($user) {
    $auth = true;
} else {
    $error = $PMF_LANG['ad_auth_sess'];
    $user = null;
    unset($user);
}
?>
<!DOCTYPE html>
<html lang="<?= $PMF_LANG['metaLanguage']; ?>">
<head>
    <meta charset='UTF-8'>
    <title>phpMyFAQ Media Browser</title>
    <style>
        @import url('../assets/dist/admin-styles.css');

        body {
            padding: 10px;
        }
    </style>
</head>
<body>

<form action="" method="post">
    <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="<?= $PMF_LANG['ad_media_name_search'] ?>" id="filter"
               value="" aria-label="Recipient's username" aria-describedby="search">
        <span class="input-group-text" id="search"><i aria-hidden="true" class="fa fa-search"></i></span>
    </div>
</form>

<?php
$allowedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'mov', 'mpg', 'mp4', 'ogg', 'wmv', 'avi', 'webm'];

if (!is_dir(PMF_ROOT_DIR . '/images')) {
    echo '<p class="alert alert-danger">' . sprintf($PMF_LANG['ad_dir_missing'], '/images') . '</p>';
} else {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PMF_ROOT_DIR . '/images/'));
    foreach ($files as $file) {
        if ($file->isDir() || !in_array($file->getExtension(), $allowedExtensions)) {
            continue;
        }
        $path = str_replace(dirname(__DIR__) . '/', '', $file->getPath());
        printf(
            '<div class="mce-file" data-src="%s"><img src="%s" class="mce-file-preview">%s</div>',
            $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename(),
            $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename(),
            $faqConfig->getDefaultUrl() . $path . '/' . $file->getFilename()
        );
    }
}
?>
<script src="../assets/dist/vendors.js"></script>
<script src="../assets/dist/phpmyfaq.js"></script>
<script src="../assets/dist/backend.js"></script>
<script src="assets/js/editor/tinymce.min.js"></script>
<script>
    /**
     * Search for image names
     * @todo Needs to be refactored: drop jQuery dependency
     *
     * @param {string} filter
     */
    $('#filter').on('keyup', function () {
        const filter = $(this).val()
        $('div.mce-file').each(function () {
            if ($(this).text().search(new RegExp(filter, 'i')) < 0) {
                $(this).fadeOut()
            } else {
                $(this).show()
            }
        })
    })

    /**
     * @todo Needs to be refactored: drop jQuery dependency
     */
    $(document).on('click', 'div.mce-file', function () {
        window.parent.postMessage({
            mceAction: 'phpMyFAQMediaBrowserAction',
            url: $(this).data('src'),
        }, '*')
    })
</script>

</body>
</html>
