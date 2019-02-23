<?php
/**
 * Image browser backend for TinyMCE v4
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2015-10-18
 */

use phpMyFAQ\Language;
use phpMyFAQ\User\CurrentUser;

define('PMF_ROOT_DIR', dirname(__DIR__));
define('IS_VALID_PHPMYFAQ', null);

require PMF_ROOT_DIR.'/src/Bootstrap.php';

$Language = new Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR.'/lang/language_en.php';

if (isset($LANGCODE) && Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
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
<style>
    @import url('assets/css/style.min.css');
    body { padding: 10px; }
</style>

<form action="" class="form-inline" method="post">
    <div class="input-group">
        <label class="sr-only" for="filter">
            <?= $PMF_LANG['ad_image_name_search'] ?>
        </label>
        <input type="text" class="form-control" id="filter" value=""
               placeholder="<?= $PMF_LANG['ad_image_name_search'] ?>">
        <span class="input-group-addon"><i aria-hidden="true" class="fas fa-search"></i></span>
    </div>
</form>

<?php
$allowedExtensions = ['png', 'gif', 'jpg', 'jpeg'];

if (!is_dir(PMF_ROOT_DIR.'/images')) {
    echo '<p class="alert alert-danger">'.sprintf($PMF_LANG['ad_dir_missing'], '/images').'</p>';
} else {
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(PMF_ROOT_DIR.'/images/'));
    foreach ($files as $file) {
        if ($file->isDir() || !in_array($file->getExtension(), $allowedExtensions)) {
            continue;
        }
        $path = str_replace(dirname(__DIR__).'/', '', $file->getPath());
        printf(
            '<div class="mce-file" data-src="%s"><img src="%s" class="mce-file-preview">%s</div>',
            $faqConfig->getDefaultUrl().$path.'/'.$file->getFilename(),
            $faqConfig->getDefaultUrl().$path.'/'.$file->getFilename(),
            $path.'/'.$file->getFilename()
        );
    }
}
?>
<script src="../assets/themes/default/js/phpmyfaq.min.js"></script>
<script src="assets/js/editor/tinymce.min.js"></script>
<script>
    $('#filter').on('keyup', function () {
        var filter = $(this).val(), count = 0;
        $('div.mce-file').each(function(){
            if ($(this).text().search(new RegExp(filter, 'i')) < 0) {
                $(this).fadeOut();
            } else {
                $(this).show();
            }
        });
    });

    $(document).on('click', 'div.mce-file', function () {
        var itemUrl = $(this).data('src'),
            args = top.tinymce.activeEditor.windowManager.getParams(),
            win = (args.window),
            input = (args.input);
        
        win.document.getElementById(input).value = itemUrl;
        top.tinymce.activeEditor.windowManager.close();
    });
</script>
