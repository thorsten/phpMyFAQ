<?php
/**
* $Id: pwd.save.php,v 1.2 2004-12-13 20:26:43 thorstenr Exp $
*
* Save the password of the current user in the database
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2004 phpMyFAQ Team
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

if (md5($_REQUEST["opass"]) == $auth_pass && $_REQUEST["npass"] == $_REQUEST["bpass"]) {
	$db->query("UPDATE ".SQLPREFIX."faquser SET pass = '".md5(addslashes($_REQUEST["bpass"]))."' WHERE id = ".$auth_user);
	$db->query("UPDATE ".SQLPREFIX."faqadminsessions SET pass = '".md5(addslashes($_REQUEST["bpass"]))."' WHERE uin = '".$uin."'");
	print $PMF_LANG["ad_passwdsuc"]."<br />";
	
	if (isset($_COOKIE['cuser'])) {
		if ($_COOKIE["cuser"] == $user) {
			print $PMF_LANG["ad_passwd_remark"]."<br /><a href=\"".$_SERVER["PHP_SELF"].$linkext."&aktion=setcookie\">".$PMF_LANG["ad_cookie_set"]."</a>\n";
			}
		}
	}
else {
	print $PMF_LANG["ad_passwd_fail"];
	}
?>
