<?php
/**
* $Id: configuration.php,v 1.1 2005-12-26 21:36:30 thorstenr Exp $
*
* The main configuration frontend
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2005 phpMyFAQ Team
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

if (!$permission['editconfig']) {
	exit();
}
	
// set some parameters
$defaultConfigAction = 'listConfig';

// actions defined by url: user_action=
$userAction = isset($_GET['config_action']) ? $_GET['config_action'] : $defaultConfigAction;

// Save the configuration
if ('saveConfig' == $userAction) {
    $message = '';
    $userAction = $defaultConfigAction;
    
    $fp = @fopen(PMF_ROOT_DIR."/inc/config.php", "w");
    $arrVar = $_REQUEST["edit"];
    if (isset($fp)) {
        @fputs($fp, "<?php \n// Created ".date("Y-m-d H:i:s")."\n\n");
        foreach ($arrVar as $key => $value) {
            @fputs($fp, "// ".$LANG_CONF[$key][1]."\n\$PMF_CONF['".$key."'] = '".htmlspecialchars($value)."';\n\n");
        }
        @fputs($fp, "\n?>");
        @fclose($fp);
        $message = $PMF_LANG['ad_config_saved'];
    } else {
        $message = $PMF_LANG['ad_entryins_fail'];
    }
    @fclose($fp);

}
// Lists the current configuration
if ('listConfig' == $userAction) {
    $message = '';
    $userAction = $defaultConfigAction;
?>

<h2><?php print $PMF_LANG['ad_config_edit']; ?></h2>

<div id="user_message"><?php print $message; ?></div>

<form id="config_list" name="config_list" action="?aktion=config&amp;config_action=saveConfig" method="post">
<fieldset>
<legend><?php print $PMF_LANG['ad_config_edit']; ?></legend>
<div id="configuration"></div>
<p><input class="submit" type="submit" value="<?php print $PMF_LANG['ad_config_save']; ?>" /> <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_config_reset']; ?>" /></p>
</fieldset>
</form>

<script type="text/javascript">
/* <![CDATA[ */

function getConfigList()
{
    var ajax = new Ajax.Updater('configuration', 'index.php?aktion=ajax&ajax=config_list');
}

getConfigList();

/* ]]> */
</script>

<?php
}