<?php
/******************************************************************************
 * File:				pwd.change.php
 * Description:			change password of the current user
 * Authors:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-23
 * Last change:			2004-07-23
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
if ($permission["passwd"]) {
?>
	<h2><?php print $PMF_LANG["ad_passwd_cop"]; ?></h2>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<input type="hidden" name="aktion" value="savepwd" />
	<div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_passwd_old"]; ?></strong></span>
    <input class="admin" type="password" name="opass" size="30" /></div>
	<div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_passwd_new"]; ?></strong></span>
    <input class="admin" type="password" name="npass" size="30" /></div>
	<div class="row"><span class="label"><strong><?php print $PMF_LANG["ad_passwd_con"]; ?></strong></span>
    <input class="admin" type="password" name="bpass" size="30" /></div>
	<div class="row"><span class="label"><strong>&nbsp;</strong></span>
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_passwd_change"]; ?>" /></div>
	</form>
<?php
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
