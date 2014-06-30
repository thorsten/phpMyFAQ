<?php
/**
 * Footer of the admin area
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
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2014 phpMyFAQ Team
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
</div>

<footer>
    <div class="container-fluid">
        <div class="row">
            <form action="index.php<?php print (isset($action) ? '?action=' . $action : ''); ?>" method="post" class="pull-right" accept-charset="utf-8">
            <?php print PMF_Language::selectLanguages($LANGCODE, true); ?>
            </form>
        </div>
        <div class="row">
            <p class="copyright pull-right">
                Proudly powered by <strong>phpMyFAQ <?php print $faqConfig->get('main.currentVersion'); ?></strong> |
                <a href="http://www.phpmyfaq.de/documentation.php" target="_blank">phpMyFAQ documentation</a> |
                Follow us on <a href="http://twitter.com/phpMyFAQ">Twitter</a> |
                &copy; 2001-<?php echo date('Y') ?> <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a>
            </p>
        </div>
    </div>
<?php
    if (DEBUG) {
        print '<div class="container">DEBUG INFORMATION:<br>'.$faqConfig->getDb()->log().'</div>';
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
$().tooltip({placement: 'bottom'})
KindEditor.ready(function(K) {
    window.editor = K.create('#answer', {
        width: '675px',
        height: '300px',
	minWidth: '650px'
    });
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
