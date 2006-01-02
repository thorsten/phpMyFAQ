<?php
/**
* $Id: stat.form.php,v 1.4 2006-01-02 16:51:27 thorstenr Exp $
*
* Form for the session search
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright    (c) 2001-12006 phpMyFAQ Team
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
$dir = opendir(PMF_ROOT_DIR."/data");
while ($dat = readdir($dir)) {
	if ($dat != "." && $dat != "..") {
		$arrDates[] = FileToDate($dat);
		}
	}
$statstart = reset($arrDates);
$statend = end($arrDates);
closedir($dir);
?>
	<h2><?php print $PMF_LANG["ad_sess_sfs"]; ?></h2>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="POST">
	<input type="hidden" name="aktion" value="sessionsearch">
	<table width="400" cellspacing="0" cellpadding="0" border="0" align="center">
	<tr bgcolor="#778899">
		<td>
		<table width="400" border="0" cellspacing="1" cellpadding="5" align="center" bgcolor="#f5f5f5">
		<tr>
			<td><?php print $PMF_LANG["ad_sess_s_ip"]; ?></td>
			<td><input name="sip" size="30"></td>
		</tr>
		<tr>
			<td colspan="2"><strong><?php print $PMF_LANG["ad_sess_s_date"]; ?></strong></td>
		</tr>
		<tr>
			<td><?php print $PMF_LANG["ad_sess_s_after"]; ?></td>
			<td><input name="nach_datum" size="14" value="<?php print date("d.m.Y", $statstart); ?>"> <input name="nach_zeit" size="14" value="<?php print date("H:i:s", $statstart); ?>"></td>
		</tr>
		<tr>
			<td><?php print $PMF_LANG["ad_sess_s_before"]; ?></td>
			<td><input name="vor_datum" size="14" value="<?php print date("d.m.Y", $statend); ?>"> <input name="vor_zeit" size="14" value="<?php print date("H:i:s", $statend); ?>"></td>
		</tr>
		<tr>
			<td colspan="2" align="center"><input class="submit" type="submit" value="<?php print $PMF_LANG["ad_sess_s_search"]; ?>"> <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_gen_reset"]; ?>"></td>
		</tr>
		</table>
		</td>
	</tr>
	</table>
	</form>
