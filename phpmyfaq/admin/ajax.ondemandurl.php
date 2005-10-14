<?php
/**
* $Id: ajax.ondemandurl.php,v 1.3 2005-10-14 17:13:01 thorstenr Exp $
*
* AJAX: onDemandURL (not really AJAX)
*
* Usage:
*   index.php?uin=<uin>&aktion=ajax&ajax=onDemandURL&id=<id>&lang=<lang>
*
* Performs link verification at demand of the user.
*
* @author           Minoru TODA <todam@netjapan.co.jp>
* @since            2005-09-30
* @copyright       (c) 2005 NetJapan, Inc.
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
* The Initial Developer of the Original Code is released for external use 
* with permission from NetJapan, Inc. IT Administration Group.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
@header("Cache-Control: no-store, no-cache, must-revalidate");
@header("Cache-Control: post-check=0, pre-check=0", false);
@header("Pragma: no-cache");
@header("Content-type: text/html");
@header("Vary: Negotiate,Accept");

$linkverifier = new link_verifier();		
if ($linkverifier->isReady() == FALSE) {
	ob_clean();
	print "disabled";
	exit();
}

$linkverifier->loadConfigurationFromDB();

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
	$id = $_GET["id"];
}

if (isset($_GET["lang"])) {
	$lang = $_GET["lang"];
}

ob_clean();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print $PMF_CONF["title"]; ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2005 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />
    <style type="text/css"> @import url(../template/admin.css); </style>
</head>
<body id="body" dir="<?php print $PMF_LANG["dir"]; ?>">	
<?php

if (!(isset($id) && isset($lang))) {
	// TODO: ASSIGN STRING
	?>
	Error: Entry ID and Language needs to be specified.
	</body>
	</html>
	<?php
	exit();
}

if (($content = getEntryContent($id, $lang)) === FALSE) {
	// TODO: ASSIGN STRING
	?>
	Error: No entry for #<?php print $id; ?>(<?php print $lang; ?>) available.
	</body>
	</html>
	<?php
	exit();
}

print verifyArticleURL($content, $id, $artlang);
?>
</body>
</html>
<?php

function getEntryContent($id = 0, $lang = "") {
	global $db;
	
	$query = "SELECT content FROM ".SQLPREFIX."faqdata WHERE id = ".$id." AND lang='".$db->escape_string($lang)."'";
	$result = $db->query($query);
	if ($db->num_rows($result) != 1) {
		return FALSE;
	}
	
	list($content) = $db->fetch_row($result);
	return $content;
}