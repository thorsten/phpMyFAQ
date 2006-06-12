<?php
/**
* $Id: attachment.php,v 1.17 2006-06-12 22:24:26 matteo Exp $
*
* Select an attachment and save it or create the SQL backup files
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-09-17
* @copyright    (c) 2001-2005 phpMyFAQ
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

require_once('../inc/functions.php');
require_once('../inc/Init.php');
define('IS_VALID_PHPMYFAQ_ADMIN', null);
PMF_Init::cleanRequest();

// Just for security reasons - thanks to Johannes for the hint
$_SERVER['PHP_SELF'] = str_replace('%2F', '/', rawurlencode($_SERVER['PHP_SELF']));
$_SERVER['HTTP_USER_AGENT'] = urlencode($_SERVER['HTTP_USER_AGENT']);

define('PMF_ROOT_DIR', dirname(dirname(__FILE__)));

if (isset($_REQUEST["aktion"]) && ($_REQUEST["aktion"] == "sicherdaten" || $_REQUEST["aktion"] == "sicherlog")) {
	Header("Content-Type: application/octet-stream");
	if ($_REQUEST["aktion"] == "sicherdaten") {
		Header("Content-Disposition: attachment; filename=\"phpmyfaq-data.".date("Y-m-d").".sql\"");
	} elseif ($_REQUEST["aktion"] == "sicherlog") {
		Header("Content-Disposition: attachment; filename=\"phpmyfaq-logs.".date("Y-m-d").".sql\"");
	}
	Header("Pragma: no-cache");
}

// get language (default: english)
$pmf = new PMF_Init();
$LANGCODE = $pmf->setLanguage((isset($PMF_CONF['detection']) ? true : false), $PMF_CONF['language']);

if (isset($LANGCODE)) {
    require_once(PMF_ROOT_DIR."/lang/language_".$LANGCODE.".php");
} else {
    require_once (PMF_ROOT_DIR."/lang/language_en.php");
    $LANGCODE = "en";
}

if (!isset($_REQUEST["aktion"]) || isset($_REQUEST["save"])) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $PMF_LANG["metaLanguage"]; ?>" lang="<?php print $PMF_LANG["metaLanguage"]; ?>">
<head>
    <title><?php print $PMF_CONF["title"]; ?> - powered by phpMyFAQ</title>
    <meta name="copyright" content="(c) 2001-2006 phpMyFAQ Team" />
    <meta http-equiv="Content-Type" content="text/html; charset=<?php print $PMF_LANG["metaCharset"]; ?>" />

    <link rel="shortcut icon" href="../template/favicon.ico" type="image/x-icon" />

    <link rel="icon" href="../template/favicon.ico" type="image/x-icon" />
    <style type="text/css">
    @import url(../template/admin.css);
    body { margin: 5px; }
    </style>
    <script type="text/javascript" src="../inc/js/functions.js"></script>
</head>
<body>
<?php
}

$db->query("DELETE FROM ".SQLPREFIX."faqadminsessions WHERE time < ".(time() - ($PMF_CONST["timeout"] * 60)));

$user = "";
$pass = "";

if ($_REQUEST["uin"]) {
	$uin = $_REQUEST["uin"];
}
if (isset($uin)) {
	$query = "SELECT usr, pass FROM ".SQLPREFIX."faqadminsessions WHERE UIN='".$uin."'";
	if (isset($PMF_CONF["ipcheck"])) {
		$query .= " AND ip = '".$_SERVER["REMOTE_ADDR"]."'";
	}
    $_result = $db->query($query);
	if ($row = $db->fetch_object($_result)) {
        $user = $row->usr;
        $pass = $row->pass;
    }
	$db->query("UPDATE ".SQLPREFIX."faqadminsessions SET time = ".time()." WHERE uin = '".$uin."'");
}

if ($pass == "" && $user == "") {
	print $PMF_LANG["ad_attach_3"];
}

if (isset($user) && isset($pass)) {
	$result = $db->query("SELECT id, name, pass, rights FROM ".SQLPREFIX."faquser WHERE name = '".$user."' AND pass = '".$pass."'");
	if ($db->num_rows($result) > 0) {
		$auth = 1;
	} else {
		$auth = 0;
	}
    if ($row = $db->fetch_object($result)) {
        $user = $row->name;
        $pass = $row->pass;
        $permission = array_combine($faqrights, explode(",", substr(chunk_split($row->rights,1,","), 0, -1)));
    }
}

if (!isset($_REQUEST["aktion"]) && $auth && $permission["addatt"]) {
?>
<form action="<?php print $_SERVER["PHP_SELF"]; ?>" enctype="multipart/form-data" method="post">
<fieldset>
<legend><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></legend>
<input type="hidden" name="aktion" value="save" />
<input type="hidden" name="uin" value="<?php print $_REQUEST["uin"]; ?>" />
<input type="hidden" name="MAX_FILE_SIZE" value="<?php print $PMF_CONF["attmax"]; ?>" />
<input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>" />
<input type="hidden" name="save" value="TRUE" />
<?php print $PMF_LANG["ad_att_att"]; ?> <input name="userfile" type="file" />
<input class="submit" type="submit" value="<?php print $PMF_LANG["ad_att_butt"]; ?>" />
</fieldset>
</form>
<?php
}

if (isset($_REQUEST["aktion"]) && $auth && !$permission["addatt"]) {
	print $PMF_LANG["err_NotAuth"];
}

if (isset($_REQUEST["save"]) && $_REQUEST["save"] == TRUE && $auth && $permission["addatt"]) {
?>
<p><strong><?php print $PMF_LANG["ad_att_addto"]." ".$PMF_LANG["ad_att_addto_2"]; ?></strong></p>
<?php
	if (is_uploaded_file($_FILES["userfile"]["tmp_name"]) && !(@filesize($_FILES["userfile"]["tmp_name"]) > $PMF_CONF["attmax"])) {
		if (!is_dir(PMF_ROOT_DIR."/attachments/")) {
			mkdir(PMF_ROOT_DIR."/attachments/", 0777);
		}
		if (!is_dir(PMF_ROOT_DIR."/attachments/".$_REQUEST["id"])) {
			mkdir(PMF_ROOT_DIR."/attachments/".$_REQUEST["id"], 0777);
		}
		if (@move_uploaded_file($_FILES["userfile"]["tmp_name"], PMF_ROOT_DIR."/attachments/".$_REQUEST["id"]."/".$_FILES["userfile"]["name"])) {
            chmod (PMF_ROOT_DIR."/attachments/".$_REQUEST["id"]."/".$_FILES["userfile"]["name"], 0644);
			print "<p>".$PMF_LANG["ad_att_suc"]."</p>";
		}
		else {
			print "<p>".$PMF_LANG["ad_att_fail"]."</p>";
		}
	} else {
		print "<p>".$PMF_LANG["ad_attach_4"]."</p>";
	}
	print "<p align=\"center\"><a href=\"javascript:window.close()\">".$PMF_LANG["ad_att_close"]."</a></p>";
}
if (isset($_REQUEST["save"]) && $_REQUEST["save"] == TRUE && $auth && !$permission["addatt"]) {
	print $PMF_LANG["err_NotAuth"];
}

if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "sicherdaten") {
	$text[] = "-- pmf2.0: ".SQLPREFIX."faqchanges ".SQLPREFIX."faqnews ".SQLPREFIX."faqcategories ".SQLPREFIX."faqcategoryrelations ".SQLPREFIX."faqvoting ".SQLPREFIX."faqdata ".SQLPREFIX."faqcomments ".SQLPREFIX."faquser ". SQLPREFIX."faqvisits ".SQLPREFIX."faqquestions";
	$text[] = "-- DO NOT REMOVE THE FIRST LINE!";
	$text[] = "-- pmftableprefix: ".SQLPREFIX;

	$text[] = "-- DO NOT REMOVE THE LINES ABOVE!";

	$text[] = "-- Otherwise this backup will be broken.";
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqchanges", SQLPREFIX."faqchanges");
	print implode("\r\n",$text);
    $text = build_insert ("SELECT * FROM ".SQLPREFIX."faqcomments", SQLPREFIX."faqcomments");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqdata", SQLPREFIX."faqdata");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqnews", SQLPREFIX."faqnews");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqcategories", SQLPREFIX."faqcategories");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqcategoryrelations", SQLPREFIX."faqcategoryrelations");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faquser", SQLPREFIX."faquser");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqvisits", SQLPREFIX."faqvisits");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqvoting", SQLPREFIX."faqvoting");
    print implode("\r\n",$text);
    $text = build_insert ("SELECT * FROM ".SQLPREFIX."faqquestions", SQLPREFIX."faqquestions");
	print implode("\r\n",$text);
} elseif (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "sicherdaten" && $auth && !$permission["backup"]) {
	print $PMF_LANG["err_NotAuth"];
}

if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "sicherlog") {
	$text[] = "-- pmf2.0: ".SQLPREFIX."faqadminlog ".SQLPREFIX."faqsessions";
	$text[] = "-- DO NOT REMOVE THE FIRST LINE!";
    $text[] = "-- pmftableprefix: ".SQLPREFIX;

    $text[] = "-- DO NOT REMOVE THE LINES ABOVE!";

    $text[] = "-- Otherwise this backup will be broken.";
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqadminlog", SQLPREFIX."faqadminlog");
	print implode("\r\n",$text);
	$text = build_insert ("SELECT * FROM ".SQLPREFIX."faqsessions", SQLPREFIX."faqsessions");
	print implode("\r\n",$text);
}

if (DEBUG == TRUE) {
	print "<p>".$db->sqllog()."</p>";
}

if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] != "sicherdaten" && $_REQUEST["aktion"] != "sicherlog") {
	print "</body></html>";
}

$db->dbclose();
?>
