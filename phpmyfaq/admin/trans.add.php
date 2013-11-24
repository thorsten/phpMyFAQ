<?php
/**
 * Read in files for the translation and show them inside a form.
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
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission["addtranslation"]) {
    print $PMF_LANG['err_NotAuth'];
    return;
}

if (isset($_SESSION['trans'])) {
    unset($_SESSION['trans']);
}

printf('<header><h2><i class="icon-wrench"></i> %s</h2></header>', $PMF_LANG['ad_menu_translations']);
?>
        <form id="newTranslationForm" accept-charset="utf-8">
        <table class="list" style="width: 100%">
        <tr>
            <td><?php print $PMF_LANG['msgLanguage'] ?></td>
            <td><select name="translang" id="translang">
            <?php
            $avaliableLanguages = array_keys(PMF_Language::getAvailableLanguages());
            foreach ($languageCodes as $langCode => $langName):
                if (!in_array(strtolower($langCode), $avaliableLanguages)):
            ?>
            <option value="<?php print $langCode ?>"><?php print $langName ?></option>
            <?php endif; endforeach; ?>
            </select></td>
        </tr>
        <tr>
            <td><?php print $PMF_LANG['msgTransToolLanguageDir'] ?></td>
            <td><select name="langdir"><option>ltr</option><option>rtl</option></select></td>
        </tr>
        <tr>
            <td><?php print $PMF_LANG['msgTransToolLanguageNumberOfPlurals'] ?></td>
            <td><input name="langnplurals" /></td>
        </tr>
        <tr>
            <td><?php print $PMF_LANG['msgTransToolLanguageDesc'] ?></td>
            <td><textarea name="langdesc"></textarea></td>
        </tr>
        <tr class="author_1_container">
            <td><?php print $PMF_LANG['msgAuthor'] ?></td>
            <td><input name="author[]" /></td>
        </tr>
        <tr>
            <td colspan="2"><a href="javascript: addAuthorContainer();"><?php print $PMF_LANG['msgTransToolAddAuthor'] ?></a></td>
        </tr>
        <tr>
            <td colspan="2"><input type="button" value="<?php print $PMF_LANG['msgTransToolCreateTranslation'] ?>" onclick="save()" /></td>
        </tr>
        </table>
        </form>
        <script>
        var max_author = 1

        /**
         * Add an author input field to the form
         * @return void
         */
        function addAuthorContainer()
        {
            var next_max_author = max_author + 1;
            var next_author_html = '<tr class="author_' + next_max_author + '_container">' +
                                    '<td>Author</td><td><input name="author[]" />' +
                                    '<a href="javascript: delAuthorContainer(\'author_' + next_max_author + '_container\');void(0);" >' +
                                    ' <?php print $PMF_LANG['msgTransToolRemove']?></a></td></tr>';
            $('.author_' + max_author + '_container').after(next_author_html);
            max_author++
        }


        function delAuthorContainer(id)
        {
            $('.' + id).fadeOut('slow');
            $('.' + id).removeAttr('innerHTML');
        }


        /**
         * Send the form data to the server to save
         * a new translation and redirect to the edit form
         * @return void
         */
        function save()
        {
            $('#saving_data_indicator').html('<img src="images/indicator.gif" /> <?php print $PMF_LANG['msgAdding3Dots'] ?>');

            var data = {}
            var form = document.getElementById('newTranslationForm')
            var author = []
            for(var i=0; i < form.elements.length;i++) {
                if('author[]' == form.elements[i].name) {
                    author.push(form.elements[i].value)
                } else {
                    data[form.elements[i].name] = form.elements[i].value
                }
            }
            data['author[]'] = author

            $.post('index.php?action=ajax&ajax=trans&ajaxaction=save_added_trans',
                   data,
                   function(retval, status) {
                       if(1*retval > 0 && 'success' == status) {
                           $('#saving_data_indicator').html('<?php print $PMF_LANG['msgTransToolTransCreated'] ?>');
                           document.location = '?action=transedit&translang=' + $('#translang').val().toLowerCase()
                       } else {
                           $('#saving_data_indicator').html('<?php print $PMF_LANG['msgTransToolCouldntCreateTrans'] ?>');
                       }
                   }
            );
        }
        </script>
