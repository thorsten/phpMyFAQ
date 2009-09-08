<?php
/**
 * phpMyFAQ Upgrade frontend
 * 
 * @todo Implement an automatically upgrade :-)
 * 
 * @package    phpMyFAQ
 * @subpackage Administration
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-09-08
 * @version    SVN: $Id$
 * @copyright  2009 phpMyFAQ Team
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

printf('<h2>%s</h2>', $PMF_LANG['ad_menu_upgrade']);

$step    = PMF_Filter::filterInput(INPUT_GET, 'step', FILTER_VALIDATE_INT, 1);
$version = PMF_Filter::filterInput(INPUT_POST, 'version', FILTER_SANITIZE_STRING);
$query   = array();

// @todo Move this to ../install/update.php
define('NEWVERSION', '2.6.0-alpha');

/**************************** STEP 1 OF 4 ***************************/
if ($step == 1) {
?>
<form action="?action=upgrade&step=2" method="post">
<fieldset>
<legend><strong>phpMyFAQ <?php print NEWVERSION; ?> Update (Step 1 of 4)</strong></legend>
<?php 
    // @todo Add information if installed version is the latest version and the user need no upgrade.
    // update: $PMF_LANG['ad_you_should_update']
    // no update: $PMF_LANG['ad_you_shouldnt_update']

    if (version_compare($faqconfig->get('main.currentVersion'), '2.6.0-alpha', '<') && !is_writeable($templateDir)) {
        echo "<p><strong>Please make the dir $templateDir and its contents writeable (777 on unix).</strong></p>";
    }
?>
<h3 align="center">Your current phpMyFAQ version: <?php print $faqconfig->get('main.currentVersion'); ?></p>
<input name="version" type="hidden" value="<?php print $faqconfig->get('main.currentVersion'); ?>"/>

<p class="center"><input type="submit" value="Go to step 2 of 4" class="button" /></p>
</fieldset>
</form>
<?php
}

/**************************** STEP 2 OF 4 ***************************/
if ($step == 2) {

}

/**************************** STEP 3 OF 4 ***************************/
if ($step == 3) {
    
}
    
/**************************** STEP 4 OF 4 ***************************/
if ($step == 4) {
    
}
