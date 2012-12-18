<?php
/**
 * Adds a category
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-12-20
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header>
            <h2><?php echo $PMF_LANG['ad_categ_new'] ?></h2>
        </header>
<?php
if ($permission['addcateg']) {

    $category = new PMF_Category($faqConfig, array(), false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $parentId = PMF_Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT, 0);
?>
        <form class="form-horizontal" action="?action=savecategory" method="post">
            <input type="hidden" id="lang" name="lang" value="<?php echo $LANGCODE ?>">
            <input type="hidden" name="parent_id" value="<?php echo $parentId ?>">
            <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession() ?>">
<?php
    if ($parentId > 0) {
        $user_allowed  = $category->getPermissions('user', array($parentId));
        $group_allowed = $category->getPermissions('group', array($parentId));
?>
            <input type="hidden" name="restricted_users" value="<?php echo $user_allowed[0] ?>">
            <input type="hidden" name="restricted_groups" value="<?php echo $group_allowed[0] ?>">
<?php
        printf(
            '<div class="control-group">%s: %s (%s)</div>',
            $PMF_LANG['msgMainCategory'],
            $category->categoryName[$parentId]['name'],
            $languageCodes[PMF_String::strtoupper($category->categoryName[$parentId]['lang'])]
        );
    }
?>
            <div class="control-group">
                <label class="control-label" for="name"><?php echo $PMF_LANG['ad_categ_titel'] ?>:</label>
                <div class="controls">
                    <input type="text" id="name" name="name" required="required">
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="description"><?php echo $PMF_LANG['ad_categ_desc'] ?>:</label>
                <div class="controls">
                    <textarea id="description" name="description" rows="3" cols="80" ></textarea>
                </div>
            </div>

            <div class="control-group">
                <label class="control-label" for="user_id"><?php echo $PMF_LANG['ad_categ_owner'] ?>:</label>
                <div class="controls">
                    <select name="user_id" id="user_id" size="1">
                    <?php echo $user->getAllUserOptions() ?>
                    </select>
                </div>
            </div>

<?php
    if ($parentId == 0) {
        if ($faqConfig->get('security.permLevel') != 'basic') {
?>
            <div class="control-group">
                <label class="control-label"><?php echo $PMF_LANG['ad_entry_grouppermission'] ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="all" checked="checked">
                        <?php echo $PMF_LANG['ad_entry_all_groups'] ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="grouppermission" value="restricted">
                        <?php echo $PMF_LANG['ad_entry_restricted_groups'] ?>
                    </label>
                    <select name="restricted_groups[]" size="3" multiple>
                        <?php echo $user->perm->getAllGroupsOptions() ?>
                    </select>
                </div>
            </div>

<?php
        } else {
?>
                <input type="hidden" name="grouppermission" value="all">
<?php
        }
?>
            <div class="control-group">
                <label class="control-label"><?php echo $PMF_LANG['ad_entry_userpermission'] ?></label>
                <div class="controls">
                    <label class="radio">
                        <input type="radio" name="userpermission" value="all" checked="checked">
                        <?php echo $PMF_LANG['ad_entry_all_users'] ?>
                    </label>
                    <label class="radio">
                        <input type="radio" name="userpermission" value="restricted">
                        <?php echo $PMF_LANG['ad_entry_restricted_users'] ?>
                    </label>
                    <select name="restricted_users" size="1">
                        <?php echo $user->getAllUserOptions(1) ?>
                    </select>
                </div>
            </div>

<?php
    }

    if ($faqConfig->get('main.enableGoogleTranslation') === true) {
?>    
            <header>
                <h3><?php echo $PMF_LANG['ad_menu_translations'] ?></h3>
            </header>
            <div id="editTranslations">
                <?php
                if ($faqConfig->get('main.googleTranslationKey') == '') {
                    echo $PMF_LANG['msgNoGoogleApiKeyFound'];
                } else {
                ?>
                <div class="control-group">
                    <label class="control-label" for="langTo"><?php echo $PMF_LANG['ad_entry_locale'] ?>:</label>
                    <div class="controls">
                        <?php echo PMF_Language::selectLanguages($LANGCODE, false, array(), 'langTo') ?>
                    </div>
                </div>
                <input type="hidden" name="used_translated_languages" id="used_translated_languages" value="">
                <div id="getedTranslations">
                </div>
                <?php } ?>
            </div>
<?php
    }
?>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit" name="submit">
                    <?php echo $PMF_LANG['ad_categ_add'] ?>
                </button>
            </div>
        </form>
    
<?php    
    if ($faqConfig->get('main.enableGoogleTranslation') === true) {
?>        
    <script src="https://www.google.com/jsapi?key=<?php echo $faqConfig->get('main.googleTranslationKey')?>" type="text/javascript"></script>
    <script type="text/javascript">
    /* <![CDATA[ */
    google.load("language", "1");

    var langFromSelect = $("#lang");
    var langToSelect   = $("#langTo");       
    
    $("#langTo").val($("#lang").val());
        
    // Add a onChange to the translation select
    langToSelect.change(
        function() {
            var langTo = $(this).val();

            if (!document.getElementById('name_translated_' + langTo)) {

                // Add language value
                var languages = $('#used_translated_languages').val();
                if (languages == '') {
                    $('#used_translated_languages').val(langTo);
                } else {
                    $('#used_translated_languages').val(languages + ',' + langTo);
                }
               
                var fieldset = $('<fieldset></fieldset>')
                    .append($('<legend></legend>').html($("#langTo option:selected").text()));

                // Text for title
                fieldset
                    .append('<p>' +
                            '<label for="name_translated_' + langTo + '">' +
                            '<?php echo $PMF_LANG['ad_categ_titel'] ?>:' +
                            '</label>' +
                            '<input type="text" id="name_translated_' + langTo + '" name="name_translated_' + langTo + '" maxlength="255" >' +
                            '</p>');

                // Textarea for description
                fieldset
                    .append('<p>' +
                            '<label for="description_translated_' + langTo + '">' +
                            '<?php echo $PMF_LANG['ad_categ_desc'] ?>:' +
                            '</label>' +
                            '<textarea id="description_translated_' + langTo + '" name="description_translated_' + langTo + '" cols="80" rows="3" ></textarea>' +
                            '</p>');

                $('#getedTranslations').append(fieldset);
            }

            // Set the translated text
            var langFrom = $('#lang').val();
            getGoogleTranslation('#name_translated_' + langTo, $('#name').val(), langFrom, langTo);
            getGoogleTranslation('#description_translated_' + langTo, $('#description').val(), langFrom, langTo);
        }
    );
    /* ]]> */
    </script>
<?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}