<?php
/**
 * Footer of the admin area
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @since     2003-02-26
 * @copyright 2003-2008 phpMyFAQ Team
 * @version   CVS: $Id: footer.php,v 1.36 2008-05-23 12:00:48 thorstenr Exp $
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
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
</div>

<div class="clearing"></div>

<!-- Footer -->
<div id="footer">
    <div id="copyright"><strong>phpMyFAQ <?php print $PMF_CONF['main.currentVersion']; ?></strong> | &copy; 2001-2008 <a href="http://www.phpmyfaq.de/" target="_blank">phpMyFAQ Team</a></div>
</div>

<?php
if (isset($auth)) {
?>
<iframe id="keepPMFSessionAlive" src="session.keepalive.php?lang=<?php print $LANGCODE; ?>" style="border: none;" width="0" height="0"></iframe>

<?php
}

if (    isset($auth) &&
    (
        // FAQ
        ('takequestion' == $_action)
     || ('editentry'    == $_action)
     || ('editpreview'  == $_action)
        // News
     || ('news'         == $_action)
    )
    ) {
?>
<!-- tinyMCE -->
<script type="text/javascript">
/*<![CDATA[*/ <!--
    tinyMCE.init({
        mode : "exact",
        elements : "content",
        editor_deselector : "mceNoEditor",
        document_base_url : "<?php print(PMF_Link::getSystemRelativeUri('admin/index.php')); ?>",
        theme : "advanced",
        plugins : "table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,zoom,print,paste,directionality,fullscreen,noneditable,contextmenu",
        theme_advanced_disable : "styleselect",
        theme_advanced_buttons1_add_before : "newdocument,separator",
        theme_advanced_buttons1_add : "fontselect,fontsizeselect",
        theme_advanced_buttons2_add : "separator,insertdate,inserttime,preview,zoom,separator,forecolor,backcolor,liststyle",
        theme_advanced_buttons2_add_before: "cut,copy,paste,pastetext,pasteword,separator,search,replace,separator",
        theme_advanced_buttons3_add_before : "tablecontrols,separator",
        theme_advanced_buttons3_add : "emotions,iespell,flash,advhr,separator,print,separator,ltr,rtl,separator,fullscreen",
        theme_advanced_buttons4 : "PMFIntFaqLink",
        theme_advanced_toolbar_location : "top",
        theme_advanced_toolbar_align : "left",
        theme_advanced_statusbar_location : "bottom",
        plugin_insertdate_dateFormat : "%Y-%m-%d",
        plugin_insertdate_timeFormat : "%H:%M:%S",
        extended_valid_elements : "hr[class|width|size|noshade]",
        file_browser_callback : "fileBrowserCallBack",
        paste_use_dialog : false,
        theme_advanced_resizing : true,
        theme_advanced_resize_horizontal : false,
        theme_advanced_link_targets : "_something=My somthing;_something2=My somthing2;_something3=My somthing3;",
        apply_source_formatting : true,
        entity_encoding : "raw"
    });

    function fileBrowserCallBack(field_name, url, type, win) {
        var connector = "../../filemanager/browser.html?Connector=connectors/php/connector.php";
        var enableAutoTypeSelection = true;

        var cType;
        tinyfck_field = field_name;
        tinyfck = win;

        switch (type) {
            case "image":
                cType = "Image";
                break;
            case "flash":
                cType = "Flash";
                break;
            case "file":
                cType = "File";
                break;
        }

        if (enableAutoTypeSelection && cType) {
            connector += "&Type=" + cType;
        }

        window.open(connector, "tinyfck", "modal,width=600,height=400");
    }
// --> /*]]>*/
</script>
<!-- /tinyMCE -->
<!-- tinyMCE PMFIntFaqLink Plugin -->
<script type="text/javascript">
/*<![CDATA[*/ <!--
    function insertFaqLink() {
        var sHTML = document.forms['faqEditor'].intfaqlink.value;
        aParams = sHTML.split('_');
        if (aParams.length == 4) {
            var inst = tinyMCE.getInstanceById(tinyMCE.getWindowArg('editor_id'));

            tinyMCE.execCommand("mceBeginUndoLevel");

            // Write down the HTML anchor for the selected FAQ record
            // <option value="%d_%d_%s_%s">%s</option>
            sHTML = '<a class="intfaqlink" href="index.php?action=artikel&amp;cat=' + aParams[0] + '&amp;id='
+ aParams[1] + '&amp;artlang=' + aParams[2] + '">' + aParams[3] + '</a>';
            tinyMCE.execCommand('mceInsertContent', false, sHTML);

            tinyMCE.execCommand("mceEndUndoLevel");
        }
        document.forms['faqEditor'].intfaqlink[0].selected = true;
    }

    var TinyMCE_PMFIntFaqLinkPlugin = {
        getInfo : function() {
            return {
                longname    : 'phpMyFAQ internal FAQ link plugin',
                author      : 'phpMyFAQ Development Team',
                authorurl   : 'http://www.phpmyfaq.de',
                infourl     : 'http://www.phpmyfaq.de/dokumentation.2.0.en.php',
                version     : '1.0'
            };
        },

        getControlHTML : function(cn) {
            switch (cn) {
                case 'PMFIntFaqLink':
<?php
    $output = '';

    $output .= '<select id="intfaqlink" name="intfaqlink" onchange="insertFaqLink()" title="'.$PMF_LANG['ad_entry_intlink'].'">';
    $output .= '<option value="">'.$PMF_LANG['ad_entry_intlink'].'<option>';

    $faq->getAllRecords(FAQ_SORTING_TYPE_FAQTITLE_FAQID);
    foreach ($faq->faqRecords as $record) {
        $_title = htmlspecialchars(str_replace(array("\n", "\r", "\r\n"), '', $record['title']), ENT_QUOTES, $PMF_LANG['metaCharset']);
        $output .= sprintf(
                    '<option value="%d_%d_%s_%s">%s</option>',
                    $record['category_id'],
                    $record['id'],
                    $record['lang'],
                    // FAQ title could contains < and >
                    htmlspecialchars($_title, ENT_NOQUOTES, $PMF_LANG['metaCharset']),
                    PMF_Utils::makeShorterText($_title, 8));
    }
    
    $output .= '</select>';

    print "return '".$output."'\n";
?>
            }

            return '';
        },

        handleNodeChange : function(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
            return true;
        }
    };

    // Adds the plugin class to the list of available TinyMCE plugins
    tinyMCE.addPlugin("PMFIntFaqLink", TinyMCE_PMFIntFaqLinkPlugin);
// --> /*]]>*/
</script>
<!-- /TinyMCE PMFIntFaqLink Plugin -->
<?php
}
?>
</body>
</html>
