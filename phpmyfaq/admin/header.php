<?php
/**
* $Id: header.php,v 1.7 2005-03-30 06:49:06 thorstenr Exp $
*
* header of the admin area
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-26
* @copyright    (c) 2001-2005 phpMyFAQ Team
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

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/html; charset=".$PMF_LANG["metaCharset"]);
header("Vary: Negotiate,Accept");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print $PMF_CONF["title"]; ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2005 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />
    <style type="text/css"> @import url(../template/admin.css); </style>
    <script language="JavaScript" type="text/javascript" src="../inc/functions.js"></script>
<?php
if (isset($_REQUEST["aktion"]) && ($_REQUEST["aktion"] == "editentry" || $_REQUEST["aktion"] == "news" || $_REQUEST["aktion"] == "editpreview" || $_REQUEST["aktion"] == "takequestion") && !emptyTable(SQLPREFIX."faqcategories")) {
?>
    <style type="text/css"> @import url(editor/htmlarea.css); </style>
    <script language="JavaScript" type="text/javascript">
    //<![CDATA[
    _editor_url = "editor";
    _editor_lang = "en";
    //]]>
    </script>
    <script language="JavaScript" type="text/javascript" src="editor/htmlarea.js"></script>
    <script language="JavaScript" type="text/javascript" src="editor/plugins/ImageManager/image-manager.js"></script>
    <script language="JavaScript" type="text/javascript">
    //<![CDATA[
    HTMLArea.init();
    HTMLArea.loadPlugin("ImageManager");
    HTMLArea.onload = function() {
        var editor = new HTMLArea("content");
        var config = new HTMLArea.Config();
        config.width = "525px";
        config.height = "400px";
        var phpMyFAQLinks = {
<?php
    $output = "'Include internal links' : '',\n";
    $result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema FROM '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.id');
    while ($row = $db->fetch_object($result)) {
        $output .= "'".safeSQL(chopString($row->thema, 8))."' : '<a href=\"index.php?action=artikel&amp;cat=".$row->category_id."&amp;id=".$row->id."&amp;artlang=".$row->lang."\">".safeSQL($row->thema)."</a>',\n";
        };
    $output = substr($output, 0, -2);
    print $output;
?>
        };
        var internalLinks = {
	                id      :   "internalLinks",
                    tooltip :   "internal Link",
                    options :   phpMyFAQLinks,
                    action  :   function(editor)
                                {
                                    var elem = editor._toolbarObjects[this.id].element;
                                    editor.insertHTML(elem.value);
                                    elem.selectedIndex = 0;
                                },
                    refresh :   function(editor) { }
                    };
        config.registerDropdown(internalLinks);
        config.toolbar = [ [ "fontsize", "space", "formatblock", "space", "bold", "italic", "underline", "strikethrough", "separator", "subscript", "superscript", "separator", "copy", "cut", "paste", "space", "undo", "redo", "space", "removeformat", "killword" ], [ "justifyleft", "justifycenter", "justifyright", "justifyfull", "separator", "lefttoright", "righttoleft", "separator", "orderedlist", "unorderedlist", "outdent", "indent", "separator", "forecolor", "hilitecolor", "separator", "inserthorizontalrule", "createlink", "insertimage", "inserttable", "htmlmode" ], [ "internalLinks" ] ];
        config.formatblock = {
		"Heading 3": "h3",
		"Heading 4": "h4",
		"Heading 5": "h5",
		"Heading 6": "h6",
		"Normal": "p",
		"Address": "address",
		"Formatted": "pre",
        "Code": "code"
	};
        HTMLArea.replace("content", config);
        }
    //]]>
    </script>
<?php
}
?>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>"><a name="top"></a>
<!-- Header -->
<div id="header">
    <h1>phpMyFAQ <?php print $PMF_CONF["version"]; ?></h1>
</div>

<!-- Navigation -->
<div class="sideBox">
