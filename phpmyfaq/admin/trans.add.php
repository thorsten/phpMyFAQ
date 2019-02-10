<?php
/**
 * Read in files for the translation and show them inside a form.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2009-05-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$user->perm->checkRight($user->getUserId(), 'addtranslation')) {
    echo $PMF_LANG['err_NotAuth'];

    return;
}

if (isset($_SESSION['trans'])) {
    unset($_SESSION['trans']);
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-wrench fa-fw"></i> <?php echo $PMF_LANG['ad_menu_translations'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
                <form class="form-horizontal" id="newTranslationForm" accept-charset="utf-8">
                <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            <?php echo $PMF_LANG['msgLanguage'] ?>
                        </label>
                        <div class="col-lg-4">
                            <select name="translang" id="translang" class="form-control">
                                <?php
                                $avaliableLanguages = array_keys(PMF_Language::getAvailableLanguages());
                                foreach ($languageCodes as $langCode => $langName):
                                    if (!in_array(strtolower($langCode), $avaliableLanguages)):
                                        ?>
                                        <option value="<?php echo $langCode ?>"><?php echo $langName ?></option>
                                    <?php endif; endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            <?php echo $PMF_LANG['msgTransToolLanguageDir'] ?>
                        </label>
                        <div class="col-lg-4">
                            <select name="langdir" class="form-control">
                                <option>ltr</option>
                                <option>rtl</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            <?php echo $PMF_LANG['msgTransToolLanguageNumberOfPlurals'] ?>
                        </label>
                        <div class="col-lg-4">
                            <input type="number" min="1" max="10" step="1" class="form-control" name="langnplurals">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            <?php echo $PMF_LANG['msgTransToolLanguageDesc'] ?>
                        </label>
                        <div class="col-lg-4">
                            <textarea class="form-control" rows="3" name="langdesc"></textarea>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-2 control-label">
                            <?php echo $PMF_LANG['msgAuthor'] ?>
                        </label>
                        <div class="col-lg-4">
                            <input type="text" class="form-control" name="author[]">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-4">
                            <input type="button" class="btn btn-primary"
                                   value="<?php echo $PMF_LANG['msgTransToolCreateTranslation'] ?>"
                                   onclick="save()">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <script>
        /**
         * Send the form data to the server to save
         * a new translation and redirect to the edit form
         * @return void
         */
        function save()
        {
            $('#saving_data_indicator').html(
                '<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> <?php echo $PMF_LANG['msgAdding3Dots'] ?>'
            );

            var data = {};
            var form = document.getElementById('newTranslationForm');
            var author = [];
            for (var i=0; i < form.elements.length;i++) {
                if ('author[]' == form.elements[i].name) {
                    author.push(form.elements[i].value);
                } else {
                    data[form.elements[i].name] = form.elements[i].value;
                }
            }
            data['author[]'] = author;

            $.post(
                'index.php?action=ajax&ajax=trans&ajaxaction=save_added_trans&csrf=<?php echo $user->getCsrfTokenFromSession() ?>',
                data,
                function (retval, status) {
                    if (1 * retval > 0 && 'success' === status) {
                       $('#saving_data_indicator').html(
                           '<?php echo $PMF_LANG['msgTransToolTransCreated'] ?>'
                       );
                       document.location = '?action=transedit&translang=' + $('#translang').val().toLowerCase()
                    } else {
                       $('#saving_data_indicator').html(
                           '<?php echo $PMF_LANG['msgTransToolCouldntCreateTrans'] ?>'
                       );
                    }
                }
            );
        }
        </script>
