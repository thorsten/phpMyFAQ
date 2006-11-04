<?php
/**
* $Id: record.delete.php,v 1.12 2006-11-04 09:03:37 thorstenr Exp $
*
* Deletes a record
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["delbt"]) {
	
    if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_yes"]) {
		// "yes" -> delete it
        $record_id   = (int)$_REQUEST['id'];
        $record_lang = $db->escape_string($_REQUEST['language']);
		adminlog("Beitragdel, ".$record_id);
		if (@is_dir(PMF_ROOT_DIR."/attachments/".$record_id."/")) {
			$do = dir(PMF_ROOT_DIR."/attachments/".$record_id."/");
			while ($dat = $do->read()) {
				if ($dat != "." && $dat != "..") {
					unlink(PMF_ROOT_DIR."/attachments/".$record_id."/".$dat);
				}
			}
			rmdir (PMF_ROOT_DIR."/attachments/".$record_id."/");
		}
		$faq->deleteRecord($record_id, $record_lang);
		print "<p>".$PMF_LANG["ad_entry_delsuc"]."</p>\n";
	}
	
    if ($_REQUEST["subm"] == $PMF_LANG["ad_gen_no"]) {
		print "<p>".$PMF_LANG["ad_entry_delfail"]."<br />&nbsp;<br /><a href=\"javascript:history.back()\">".$PMF_LANG["ad_entry_back"]."</p></a>\n";
	}
    
    print "<p><img src=\"images/arrow.gif\" width=\"11\" height=\"11\" alt=\"\" border=\"0\"> <a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;action=view\">".$PMF_LANG["ad_entry_aor"]."</a></p>\n";

} else {
    print $PMF_LANG["err_NotAuth"];
}
