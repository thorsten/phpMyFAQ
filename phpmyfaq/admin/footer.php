<?php
/**
 * Footer of the admin area.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

?>
            </div>
        </div>
    </div>

    <footer>
        <div class="row">
            <div class="col-lg-12">
                <p class="copyright pull-right">
                    Proudly powered by <strong>phpMyFAQ <?php echo $faqConfig->get('main.currentVersion'); ?></strong> |
                    <a href="http://www.phpmyfaq.de/documentation" target="_blank">phpMyFAQ documentation</a> |
                    Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> |
                    &copy; 2001-<?php echo date('Y') ?> <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a>
                </p>
            </div>
        </div>
    <?php
        if (DEBUG) {
            printf('<div class="container">DEBUG INFORMATION:<br>%s</div>', $faqConfig->getDb()->log());
        }
    ?>
    </footer>

</div>

<?php
if (isset($auth)) {
    ?>
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php echo $LANGCODE;
    ?>" width="0" height="0"></iframe>
<?php
    if (isset($auth) && (('takequestion' == $action) || ('editentry' == $action) || ('editpreview'  == $action) ||
                         ('addnews' == $action) || ('editnews' == $action) || ('copyentry' == $action))) {
        if ($faqConfig->get('main.enableWysiwygEditor') == true) {
            ?>
<script>

    $().tooltip({placement: 'bottom'});

    tinyMCE.init({
        // General options
        mode     : 'exact',
        language : '<?php echo(PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en') ?>',
        elements : '<?php echo ('addnews' == $action || 'editnews' == $action) ? 'news' : 'answer' ?>',
        theme    : 'modern',
        plugins: [
            'advlist autolink lists link image charmap print preview hr anchor pagebreak',
            'searchreplace wordcount visualblocks visualchars code fullscreen',
            'insertdatetime media nonbreaking save table contextmenu directionality',
            'emoticons template paste textcolor autosave phpmyfaq imageupload'
        ],
        relative_urls: false,
        convert_urls: false,
        remove_linebreaks: false,
        use_native_selects: true,
        paste_remove_spans: true,
        entity_encoding: 'raw',

        toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
        toolbar2: "link image preview media imageupload | forecolor backcolor emoticons | phpmyfaq print",
        image_advtab: true,

        // Formatting
        style_formats: [
            { title: 'Headers', items: [
                { title: 'h1', block: 'h1' },
                { title: 'h2', block: 'h2' },
                { title: 'h3', block: 'h3' },
                { title: 'h4', block: 'h4' },
                { title: 'h5', block: 'h5' },
                { title: 'h6', block: 'h6' }
            ]},

            { title: 'Blocks', items: [
                { title: 'p', block: 'p' },
                { title: 'div', block: 'div' },
                { title: 'pre', block: 'pre' }
            ]},

            { title: 'Containers', items: [
                { title: 'blockquote', block: 'blockquote', wrapper: true },
                { title: 'figure', block: 'figure', wrapper: true }
            ]}
        ],
        visualblocks_default_state: true,
        end_container_on_empty_block: true,
        extended_valid_elements : "code[class],video[*],audio[*],source[*]",
        removeformat : [
            { selector : '*', attributes : ['style'], split : false, expand : false, deep : true }
        ],
        importcss_append: true,

        // Save function
        save_onsavecallback : "phpMyFAQSave",

        // phpMyFAQ CSS
        content_css : '../assets/template/<?php echo PMF_Template::getTplSetName() ?>/css/style.min.css?<?php echo time();
            ?>',

        // Replace values for the template plugin
        template_replace_values : {
            username : "<?php echo $user->userdata->get('display_name');
            ?>",
            user_id  : "<?php echo $user->userdata->get('user_id');
            ?>"
        },

        // File browser
        file_browser_callback: function(fieldName, url, type, win){
            var fileBrowser = 'image.browser.php';
            fileBrowser += (fileBrowser.indexOf('?') < 0) ? '?type=' + type : '&type=' + type;
            tinymce.activeEditor.windowManager.open({
                title: 'Select an image',
                url: fileBrowser,
                width: 650,
                height: 550
            }, {
                window: win,
                input: fieldName
            });
            return false;
        },

        // Image upload

        // Custom params
        csrf: $('#csrf').val()
    });

    /*
    function phpMyFAQSave () {
        $('#saving_data_indicator').html('<i class="fa fa-spinner fa-spin"></i> Saving ...');
        // Create an input field with the save button name
        var input = document.createElement("input");
        input.setAttribute('name', $('input:submit')[0].name);
        input.setAttribute('id', 'temporarySaveButton');
        $('#answer')[0].parentNode.appendChild(input);
        // Submit the form by an ajax request
        <?php if (isset($faqData['id']) && $faqData['id'] == 0): ?>
        var data = {action: "ajax", ajax: 'recordAdd'};
        <?php else: ?>
        var data = {action: "ajax", ajax: 'recordSave'};
        <?php endif;
            ?>
        var id = $('#answer')[0].parentNode.parentNode.id;
        $.each($('#' + id).serialize[], function(i, field) {
            data[field.name] = field.value;
        });
        $.post("index.php", data, null);
        $('#saving_data_indicator').html('<?php echo $PMF_LANG['ad_entry_savedsuc'];
            ?>');
        $('#temporarySaveButton').remove();
    }
    */

</script>
<?php

        }
    }
}
?>
</body>
</html>