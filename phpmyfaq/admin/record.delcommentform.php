<?php
/**
* $Id: record.delcommentform.php,v 1.6 2006-01-02 16:51:26 thorstenr Exp $
*
* Form to delete user comment
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
?>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<input type="hidden" name="aktion" value="deletecomment" />
	<input type="hidden" name="artid" value="<?php print $_REQUEST["artid"]; ?>" />
	<input type="hidden" name="cmtid" value="<?php print $_REQUEST["cmtid"]; ?>" />
	<input type="hidden" name="lang" value="<?php print $_REQUEST["lang"]; ?>" />
<?php
	$result = $db->query("SELECT usr, email, comment FROM ".SQLPREFIX."faqcomments WHERE id = ".$_REQUEST["artid"]." AND id_comment = ".$_REQUEST["cmtid"]);
	$row = $db->fetch_object($result);
?>
    <p align="center"><?php print $PMF_LANG["ad_entry_delcom_1"]; ?> <a href="mailto:<?php print $row->email; ?>"><?php print $row->usr; ?></a> <?php print $PMF_LANG["ad_entry_delcom_2"]; ?></p>
    <p align="center"><?php print $row->comment; ?></p>
    <p align="center"><input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_yes"]; ?>" name="subm" /> <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_no"]; ?>" name="subm" /></p>
	</form>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>
