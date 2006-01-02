<?php
/**
* $Id: pwd.change.php,v 1.4 2006-01-02 16:51:26 thorstenr Exp $
*
* Form to change password of the current user
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

if ($permission["passwd"]) {
?>
	<h2><?php print $PMF_LANG["ad_passwd_cop"]; ?></h2>
	<form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<fieldset>
	<legend><?php print $PMF_LANG["ad_passwd_cop"]; ?></legend>
	<input type="hidden" name="aktion" value="savepwd" />
	
	<label class="left" for="opass"><?php print $PMF_LANG["ad_passwd_old"]; ?></label>
    <input class="admin" type="password" name="opass" size="30" /><br />
    
	<label class="left" for="npass"><?php print $PMF_LANG["ad_passwd_new"]; ?></label>
    <input class="admin" type="password" name="npass" size="30" /><br />
    
	<label class="left" for="bpass"><?php print $PMF_LANG["ad_passwd_con"]; ?></label>
    <input class="admin" type="password" name="bpass" size="30" /><br />
    
	<input class="submit" type="submit" value="<?php print $PMF_LANG["ad_passwd_change"]; ?>" /></div>
    </fieldset>
	</form>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
?>