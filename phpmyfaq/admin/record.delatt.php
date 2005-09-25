<?php
/******************************************************************************
 * File:				record.delatt.php
 * Description:			delete an attachment
 * Authors:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-24
 * Last change:			2004-11-01
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
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["delatt"]) {
	if (unlink(PMF_ROOT_DIR."/attachments/".$_REQUEST["id"]."/".$_REQUEST["which"])) {
		print "<p>".$PMF_LANG["ad_att_delsuc"]."</p>\n";
		}
	else {
		print "<p>".$PMF_LANG["ad_att_delfail"]."</p>\n";
		}
	print "<p><a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=editentry&amp;id=".$_REQUEST["id"]."&amp;lang=".$_REQUEST["lang"]."\">".$PMF_LANG["ad_entry_back"]."</a></p>";
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>