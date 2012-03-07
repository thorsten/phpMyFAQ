<?php
/**
 * Footer of the admin area
 *
 * PHP Version 5.2.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
            </div>
        </div>
    </div>
</div>

<footer>
    <div class="container-fluid">
        <div class="row">
            <form action="index.php<?php print (isset($action) ? '?action=' . $action : ''); ?>" method="post" class="pull-right">
            <?php print PMF_Language::selectLanguages($LANGCODE, true); ?>
            </form>
        </div>
        <div class="row">
            <p class="copyright pull-right">
                Proudly powered by <strong>phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?></strong> |
                <a href="http://www.phpmyfaq.de/documentation.php" target="_blank">phpMyFAQ documentation</a> |
                Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> |
                &copy; 2001-2012 <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a>
            </p>
        </div>
    </div>
<?php
    if (DEBUG) {
        print '<div class="container">DEBUG INFORMATION:<br>'.$db->log().'</div>';
    }
?>
</footer>

<?php
if (isset($auth)) {
?>
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php print $LANGCODE; ?>" style="border: none;" width="0" height="0"></iframe>
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
 
tinyMCE.init({
    // General options
    mode     : "exact",
    language : "<?php print (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en'); ?>",
    elements : "<?php print ('addnews' == $action || 'editnews' == $action) ? 'news' : 'answer' ?>",
    width    : "500",
    height   : "480",
    theme    : "advanced",
    plugins  : "spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,syntaxhl,phpmyfaq",
    theme_advanced_blockformats : "p,div,h1,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp",

    // Theme options
    theme_advanced_buttons1 : "<?php print $tinyMceSave ?>bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontsizeselect",
    theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,phpmyfaq,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,syntaxhl,|,preview,|,forecolor,backcolor",
    theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,ltr,rtl,|,fullscreen",
    theme_advanced_toolbar_location : "top",
    theme_advanced_toolbar_align : "left",
    theme_advanced_statusbar_location : "bottom",
    relative_urls           : false,
    convert_urls            : false,
    remove_linebreaks       : false, 
    use_native_selects      : true,
    entity_encoding         : "raw",
    extended_valid_elements : "code",

    // Ajax-based file manager
    file_browser_callback : "ajaxfilemanager",

    // Save function
    save_onsavecallback : "phpMyFAQSave",

    // Example content CSS (should be your site CSS)
    content_css : "../template/<?php print PMF_Template::getTplSetName(); ?>/css/style.css,css/style.css",

    // Drop lists for link/image/media/template dialogs
    template_external_list_url : "js/template_list.js",

    // Replace values for the template plugin
    template_replace_values : {
        username : "<?php print $user->userdata->get('display_name'); ?>",
        user_id  : "<?php print $user->userdata->get('user_id'); ?>"
    }
});

function ajaxfilemanager(field_name, url, type, win)
{
    var ajaxfilemanagerurl = "editor/plugins/ajaxfilemanager/ajaxfilemanager.php";
    switch (type) {
        case "image":
        case "media":
        case "flash": 
        case "file":
            break;
        default:
            return false;
    }
    tinyMCE.activeEditor.windowManager.open({
        url            : "editor/plugins/ajaxfilemanager/ajaxfilemanager.php",
        width          : 640,
        height         : 480,
        inline         : "yes",
        close_previous : "no"
    },{
        window : win,
        input  : field_name
    });
}

/**
 *
 */
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
    $.each($('#' + id).serializeArray(), function(i, field) {
        data[field.name] = field.value;
    });
    $.post("index.php", data, null);
    $('#saving_data_indicator').html('<?php print $PMF_LANG['ad_entry_savedsuc']; ?>');
    $('#temporarySaveButton').remove();
}

// --> /*]]>*/
</script>
<!-- /tinyMCE -->

<!-- SyntaxHighlighter -->
<script src="../js/syntaxhighlighter/scripts/shCore.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushBash.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushCpp.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushCSharp.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushCss.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushDelphi.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushDiff.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushGroovy.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushJava.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushJScript.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushPhp.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushPerl.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushPlain.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushPython.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushRuby.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushScala.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushSql.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushVb.js"></script>
<script src="../js/syntaxhighlighter/scripts/shBrushXml.js"></script>
<link type="text/css" rel="stylesheet" href="../js/syntaxhighlighter/styles/shCore.css"/>
<link type="text/css" rel="stylesheet" href="../js/syntaxhighlighter/styles/shThemeDefault.css"/>
<script type="text/javascript">
    SyntaxHighlighter.config.clipboardSwf = '../js/syntaxhighlighter/scripts/clipboard.swf';
    SyntaxHighlighter.all();
</script>
<!-- /SyntaxHighlighter -->
<?php
        }
    } 

    if (isset($auth) && (('addcategory'    == $action) || ('editcategory' == $action) || 
                         ('updatecategory' == $action) || ('editentry' == $action)   )) {
        if ($faqConfig->get('main.enableGoogleTranslation') == true) {
?>
<!-- Google API functions -->
<script type="text/javascript">
/*<![CDATA[*/ //<!--
/**
 * Call the google API and fill the field with the result.
 *
 * @param string div       id of the input to fill.
 * @param string text      Text to translate.
 * @param string langFrom  Current language. 
 * @param string langTo    Wanted language.
 * @param string fieldType Name of the field for the switch.
 *
 * @return string $code Language code used in Google.
 */
function getGoogleTranslation(div, text, langFrom, langTo, fieldType)
{
    langFrom = convertCodeForGoogle(langFrom);
    langTo   = convertCodeForGoogle(langTo);
    google.language.translate({text: text, type: 'html'}, langFrom, langTo, function(result) {
        if (result.translation) {
            switch(fieldType) {
                case 'answer':
                    tinymce.get(div).setContent(result.translation);
                    break;
                case 'keywords':
                    separator = ',';
                    if ($(div).val() == '') {
                        $(div).val(result.translation);
                    } else {
                        $(div).val($(div).val() + separator + result.translation);
                    }
                    break;
                case 'name':
                case 'description':
                case 'question':
                default:
                    $(div).val(result.translation);
                    break;
            }
        }
    });
}

/**
 * Change some phpMyFAQ language code to the Google ones.
 *
 * @param string $code Language code used in phpMyFAQ.
 *
 * @return string $code Language code used in Google.
 */
function convertCodeForGoogle(code)
{
    switch (code) {
        case 'zh':
            code = 'zh-CN';
            break;
        case 'tw':
            code = 'zh-TW';
            break;
        case 'pt-br':
            code = 'pt';
            break;
        case 'pt':
            code = 'pt-PT';
            break;
        case 'nb':
            code = 'no';
            break;
        case 'he':
            code = 'iw';
            break;
    }

    return code;
}
// --> /*]]>*/
</script>
<!-- /Google API functions -->    
<?php            
        }
    }
}
?>
</body>
</html>