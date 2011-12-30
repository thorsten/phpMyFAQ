<?php
/**
 * Read in files for the translation and show them inside a form.
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 * 
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Anatoliy Belsky <ab@php.net>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-05-11
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if (!$permission["addtranslation"]) {
    print $PMF_LANG['err_NotAuth'];
    return;
}

if (isset($_SESSION['trans'])) {
    unset($_SESSION['trans']);
}

printf('<header><h2>%s</h2></header>', $PMF_LANG['ad_menu_translations']);
?>
        <form id="newTranslationForm">
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
