<?php
/**
 * Image upload backend for TinyMCE v4
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2015-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2015-10-18
 */
define('PMF_ROOT_DIR', dirname(__DIR__));
define('IS_VALID_PHPMYFAQ', null);

require PMF_ROOT_DIR.'/inc/Bootstrap.php';

$Language = new PMF_Language($faqConfig);
$LANGCODE = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));

require_once PMF_ROOT_DIR.'/lang/language_en.php';

if (isset($LANGCODE) && PMF_Language::isASupportedLanguage($LANGCODE)) {
    require_once PMF_ROOT_DIR.'/lang/language_'.$LANGCODE.'.php';
} else {
    $LANGCODE = 'en';
}

$auth = false;
$user = PMF_User_CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof PMF_User_CurrentUser) {
    $user = PMF_User_CurrentUser::getFromSession($faqConfig);
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


<form class="form-inline" name="upload" action="index.php?action=ajax&ajax=image&ajaxaction=upload"
      method="post" enctype="multipart/form-data" target="pmf-upload-iframe" onsubmit="pmfImageUpload.inProgress();">
    <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">

    <div id="pmf-upload-progress" class="hidden">
        <i aria-hidden="true" class="fa fa-cog fa-spin"></i> Upload in progress&hellip;
        <div id="pmf-upload-more-info"></div>
    </div>
    <div id="pmf-upload-info"></div>

    <p id="pmf-upload-form">
        <input id="uploader" name="upload" type="file"
               onchange="document.upload.submit(); pmfImageUpload.inProgress();">
    </p>

</form>

<iframe id="pmf-upload-iframe" name="pmf-upload-iframe" src="index.php?action=ajax&ajax=image"></iframe>

<script src="../assets/js/phpmyfaq.min.js"></script>
<script>
    var pmfImageUpload = {

        iframeOpened : false,
        timeoutStore : false,

        inProgress : function() {
            $('#upload_infobar').show();
            $('#pmf-upload-more-info').empty();
            $('#pmf-upload-info').empty();
            $('#pmf-upload-form').hide();
            $('#pmf-upload-progress').show();
            this.timeoutStore = window.setTimeout(function(){
                $('#pmf-upload-more-info').append('Looks like an error occurred.');
            }, 20000);
        },

        uploadFinished : function (result) {
            if ('failed' === result.resultCode) {
                window.clearTimeout(this.timeoutStore);
                $('#pmf-upload-progress').hide();
                $('#pmf-upload-info').show().addClass('alert alert-danger').append(result.result);
                $('#pmf-upload-form').show();
            } else {
                $('#pmf-upload-progress').hide();
                $('#pmf-upload-info').show().append('<i aria-hidden="true" class="fa fa-check"></i> Upload successfully completed.');

                this.getWIndow().tinymce.EditorManager.activeEditor.insertContent(
                    '<img src="' + result.filename +'" height="' + result.height + '" width="' + result.width + '">'
                );

                this.close();
            }
        },

        getWIndow: function () {
            return (!window.frameElement && window.dialogArguments) || opener || parent || top;
        },

        close: function () {
            function close () {
                this.getWIndow().tinymce.EditorManager.activeEditor.windowManager.close(window);
                tinymce = tinyMCE = this.editor = this.params = this.dom = this.dom.doc = null;
            }
            close();
        }

    };
</script>