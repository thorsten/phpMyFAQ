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
<<<<<<< HEAD
 * @category  phpMyFAQ
 * @package   Administration
=======
 * @category  phpMyFAQ 
 *
>>>>>>> 2.10
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2017 phpMyFAQ Team
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

<<<<<<< HEAD
$templateVars = array(
    'PMF_LANG'            => $PMF_LANG,
    'debugInformation'    => DEBUG ? $faqConfig->getDb()->log() : '',
    'formAction'          => 'index.php' . (isset($action) ? '?action=' . $action : ''),
    'isAuthenticated'     => isset($auth),
    'languageSelector'    => PMF_Language::selectLanguages($LANGCODE, true),
    'pmfVersion'          => $faqConfig->get('main.currentVersion'),
    'sessionKeepaliveUrl' => 'session.keepalive.php?lang=' . $LANGCODE,
    'userDisplayName'     => isset($user) ? $user->userdata->get('display_name') : '',
    'userId'              => isset($user) ? $user->userdata->get('user_id') : ''
);

$wysiwygActions = array(
    'takequestion',
    'editentry',
    'editpreview',
    'addnews',
    'editnews',
    'copyentry'
);

if (isset($auth) && in_array($action, $wysiwygActions) && $faqConfig->get('main.enableWysiwygEditor')) {
    $templateVars['wysiwygActive']     = true;
    $templateVars['tinyMceContentCss'] = '../assets/template/' . PMF_Template::getTplSetName() . '/css/style.css';
    $templateVars['tinyMceLanguage']   = PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en';
    if (('addnews' == $action || 'editnews' == $action)) {
        $templateVars['tinyMceElements'] = 'news';
        $templateVars['tinyMceSave']     = '';
    } else {
        $templateVars['tinyMceElements'] = 'answer';
        $templateVars['tinyMceSave']     = 'save,|,';
    }
    if (isset($faqData['id']) && $faqData['id'] == 0) {
        $templateVars['tinyMceSaveCallbackAction'] = 'recordAdd';
    } else {
        $templateVars['tinyMceSaveCallbackAction'] = 'recordSave';
    }
} else {
    $templateVars['wysiwygActive'] = false;

    $twig->loadTemplate('footer.twig')
         ->display($templateVars);

    unset($templateVars, $wysiwygActions);
}
=======
?>
            </div>
        </div>
    </div>
</div>

<footer class="page-footer container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <p class="copyright text-right">
                Proudly powered by <strong>phpMyFAQ <?php echo $faqConfig->get('main.currentVersion'); ?></strong> |
                <a href="http://www.phpmyfaq.de/documentation" target="_blank">phpMyFAQ documentation</a> |
                Follow us on <a href="http://twitter.com/phpMyFAQ"><i aria-hidden="true" class="fa fa-twitter"></i></a> |
                <i aria-hidden="true" class="fa fa-apple"></i> Available on the
                <a target="_blank" href="https://itunes.apple.com/app/phpmyfaq/id977896957">App Store</a> |
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


<?php
if (isset($auth)) {
    ?>
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php echo $LANGCODE ?>" width="0" height="0"
        style="display: none;"></iframe>
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
            'searchreplace wordcount visualblocks visualchars code codesample fullscreen',
            'insertdatetime media nonbreaking save table contextmenu directionality',
            'emoticons template paste textcolor autosave phpmyfaq imageupload'
        ],
        relative_urls: false,
        convert_urls: false,
        remove_linebreaks: false,
        use_native_selects: true,
        paste_remove_spans: true,
        entities : '10',
        entity_encoding: 'raw',
        toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | paste codesample",
        toolbar2: "link image preview media imageupload | forecolor backcolor emoticons | phpmyfaq print",
        image_advtab: true,
        image_class_list: [
            { title: 'None', value: '' },
            { title: 'Responsive', value: 'img-responsive' }
        ],
        image_dimensions: true,

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
                { title: 'pre', block: 'pre' },
                { title: 'code', block: 'code' }
            ]},

            { title: 'Containers', items: [
                { title: 'blockquote', block: 'blockquote', wrapper: true },
                { title: 'figure', block: 'figure', wrapper: true }
            ]}
        ],

        paste_word_valid_elements: "b,strong,i,em,h1,h2,h3,h4,h5,h6",
        paste_data_images: true,
        visualblocks_default_state: true,
        end_container_on_empty_block: true,
        extended_valid_elements : "code[class],video[*],audio[*],source[*]",
        removeformat : [
            { selector : '*', attributes : ['style'], split : false, expand : false, deep : true }
        ],
        importcss_append: true,

        // Save function
        save_onsavecallback: "phpMyFAQSave",

        // phpMyFAQ CSS
        content_css: '../assets/themes/<?php echo PMF_Template::getTplSetName() ?>/css/style.min.css?<?php echo time(); ?>',

        // Replace values for the template plugin
        template_replace_values : {
            username: '<?php echo addslashes($user->userdata->get('display_name')) ?>',
            user_id: '<?php echo $user->userdata->get('user_id') ?>'
        },

        templates: [
            { title: 'Slider', description: 'phpMyFAQ Image Slider', url: 'assets/templates/image-slider.html' }
        ],

        // File browser
        file_browser_callback: function(fieldName, url, type, win){
            var fileBrowser = 'image.browser.php';
            fileBrowser += (fileBrowser.indexOf('?') < 0) ? '?type=' + type : '&type=' + type;
            tinymce.activeEditor.windowManager.open({
                title: 'Select an image',
                url: fileBrowser,
                width: 640,
                height: 480
            }, {
                window: win,
                input: fieldName
            });

            return false;
        },

        // Custom params
        csrf: $('#csrf').val()
    });

    function phpMyFAQSave()
    {
        var indicator = $('#saving_data_indicator'),
            input = document.createElement('input');
        indicator.html('<img src="images/indicator.gif"> Saving ...');
        input.setAttribute('name', $('button:submit')[0].name);
        input.setAttribute('id', 'temporarySaveButton');
        $('#answer')[0].parentNode.appendChild(input);
        // Submit the form by an ajax request
        <?php if (isset($faqData['id']) && $faqData['id'] === 0): ?>
        var data = {
            action: 'ajax',
            ajax: 'recordAdd'
        };
        <?php else: ?>
        var data = {
            action: 'ajax',
            ajax: 'recordSave'
        };
        <?php endif; ?>

        $.each($('#faqEditor').serializeArray(), function(i, field) {
            data[field.name] = field.value;
        });

        $.post('index.php', data, null);
        indicator.html('<?php echo $PMF_LANG['ad_entry_savedsuc'] ?>');
        $('#temporarySaveButton').remove();
        indicator.fadeOut(5000);
    }
</script>
<?php

        }
    }
}
?>
</body>
</html>
>>>>>>> 2.10
