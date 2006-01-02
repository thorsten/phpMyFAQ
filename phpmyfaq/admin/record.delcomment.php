<?php
/**
* $Id: record.delcomment.php,v 1.5 2006-01-02 16:51:26 thorstenr Exp $
*
* Deletes a user comment
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["delcomment"]) {
	if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_yes"]) {
		// Wenn ja geklickt wurde
		$db->query("DELETE FROM ".SQLPREFIX."faqcomments WHERE id = ".$_REQUEST["artid"]." AND id_comment = ".$_REQUEST["cmtid"]);
		print "<p align=\"center\">".$PMF_LANG["ad_entry_commentdelsuc"]."<p><a href=\"".$_SERVER["PHP_SELF"].$linkext."&aktion=editentry&id=".$_REQUEST["artid"]."&amp;lang=".$_REQUEST["lang"]."\">".$PMF_LANG["ad_entry_back"]."</a></p>";
	}
	if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_no"]) {
		// Wenn nein geklickt wurde
		print "<p align=\"center\">".$PMF_LANG["ad_entry_commentdelfail"]."<p><a href=\"".$_SERVER["PHP_SELF"].$linkext."&aktion=editentry&id=".$_REQUEST["artid"]."&amp;lang=".$_REQUEST["lang"]."\">".$PMF_LANG["ad_entry_back"]."</a></p>";
	}
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>