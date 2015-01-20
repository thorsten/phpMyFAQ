<?php
/**
 * Footer of the admin area
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2015 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
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
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php echo $LANGCODE; ?>" style="border: none;" width="0" height="0"></iframe>
<?php
    if (isset($auth) && (('takequestion' == $action) || ('editentry'    == $action) || ('editpreview'  == $action) ||
                         ('addnews'      == $action) || ('editnews'     == $action) || ('copyentry'  == $action))) {
    
        if ($faqConfig->get('main.enableWysiwygEditor') == true) {

            if (('addnews' == $action || 'editnews' == $action)) {
                $tinyMceSave = '';
            } else {
                $tinyMceSave = 'save,|,';
            }

?>
<!-- tinyMCE -->
<script>
/*<![CDATA[*/ //<!--
$().tooltip({placement: 'bottom'})

tinymce.init({
    // General options
    mode     : "exact",
    //language : "<?php echo (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en'); ?>",
    elements : "<?php echo ('addnews' == $action || 'editnews' == $action) ? 'news' : 'answer' ?>",
    width    : "500",
    height   : "480",
    theme    : "modern",
    plugins: [
        "advlist autolink link image lists charmap echo preview hr anchor pagebreak spellchecker",
        "searchreplace wordcount visualblocks visualchars code fullscreen insertdatetime media nonbreaking",
        "save table contextmenu directionality emoticons template paste textcolor"
    ],
    theme_advanced_blockformats : "p,div,h1,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp",

    // Theme options
    theme_advanced_buttons1 : "<?php echo $tinyMceSave ?>bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontsizeselect",
    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,phpmyfaq,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,syntaxhl,|,forecolor,backcolor",
    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,advhr,|,ltr,rtl,|,fullscreen",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    theme_advanced_resizing : true,
    relative_urls           : false,
    convert_urls            : false,
    remove_linebreaks       : false, 
    use_native_selects      : true,
    paste_remove_spans      : true,
    entity_encoding         : "raw",
    extended_valid_elements : "code",

    // Save function
    save_onsavecallback : "phpMyFAQSave",

    // Example content CSS (should be your site CSS)
    content_css : "../assets/template/<?php echo PMF_Template::getTplSetName(); ?>/css/style.css",

    // Drop lists for link/image/media/template dialogs
    template_external_list_url : "js/template_list.js",

    // Replace values for the template plugin
    template_replace_values : {
        username : "<?php echo $user->userdata->get('display_name'); ?>",
        user_id  : "<?php echo $user->userdata->get('user_id'); ?>"
    }
});

/*
function phpMyFAQSave()
{
    $('#saving_data_indicator').html('<img src="images/indicator.gif" /> Saving ...');
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
    <?php endif; ?>
    var id = $('#answer')[0].parentNode.parentNode.id;
    $.each($('#' + id).serialize[], function(i, field) {
        data[field.name] = field.value;
    });
    $.post("index.php", data, null);
    $('#saving_data_indicator').html('<?php echo $PMF_LANG['ad_entry_savedsuc']; ?>');
    $('#temporarySaveButton').remove();
}
*/

// --> /*]]>*/
</script>
<!-- /tinyMCE -->

<!-- SyntaxHighlighter -->
<script src="../assets/js/syntaxhighlighter/scripts/shCore.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushBash.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushCpp.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushCSharp.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushCss.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushDelphi.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushDiff.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushGroovy.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushJava.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushJScript.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushPhp.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushPerl.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushPlain.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushPython.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushRuby.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushScala.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushSql.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushVb.js"></script>
<script src="../assets/js/syntaxhighlighter/scripts/shBrushXml.js"></script>
<link type="text/css" rel="stylesheet" href="../assets/js/syntaxhighlighter/styles/shCore.css"/>
<link type="text/css" rel="stylesheet" href="../assets/js/syntaxhighlighter/styles/shThemeDefault.css"/>
<script type="text/javascript">
    SyntaxHighlighter.config.clipboardSwf = '../js/syntaxhighlighter/scripts/clipboard.swf';
    SyntaxHighlighter.all();
</script>
<!-- /SyntaxHighlighter -->
<?php
        }
    }
}
?>
</body>
</html>