<?php
/******************************************************************************
 * File:				record.delete.php
 * Description:			delete a record
 * Author:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-23
 * Last change:			2004-07-29
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["delbt"]) {
	if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_yes"]) {
		// "yes" -> delete it
		adminlog("Beitragdel, ".$_REQUEST["id"]);
		if (@is_dir("../attachments/".$_REQUEST["id"]."/")) {
			$do = dir("../attachments/".$_REQUEST["id"]."/");
			while ($dat = $do->read()) {
				if ($dat != "." && $dat != "..") {
					unlink("../attachments/".$_REQUEST["id"]."/".$dat);
					}
				}
			rmdir ("../attachments/".$_REQUEST["id"]."/");
			}
		$db->query("DELETE FROM ".SQLPREFIX."faqdata WHERE id = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
		$db->query("DELETE FROM ".SQLPREFIX."faqvoting WHERE artikel = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
		$db->query("DELETE FROM ".SQLPREFIX."faqcomments WHERE id = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
		$db->query("DELETE FROM ".SQLPREFIX."faqvisits WHERE id = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
		$db->query("DELETE FROM ".SQLPREFIX."faqchanges WHERE artikel = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
		print "<p>".$PMF_LANG["ad_entry_delsuc"]."</p>\n";
		}
	if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_no"]) {
		print "<p>".$PMF_LANG["ad_entry_delfail"]."<br />&nbsp;<br /><a href=\"javascript:history.back()\">".$PMF_LANG["ad_entry_back"]."</p></a>\n";
		}
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>