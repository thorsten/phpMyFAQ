<?php
/**
* $Id: configuration.php,v 1.7 2006-07-30 06:38:52 matteo Exp $
*
* The main configuration frontend
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2006 phpMyFAQ Team
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

    $arrVar = array();
    if (isset($_REQUEST['edit'])) {
        $arrVar = $_REQUEST['edit'];
    }

    // Set the new values into $PMF_CONF
    foreach ($arrVar as $key => $value) {
        $PMF_CONF[$key] = $value;
    }
    // Fix checkbox values: they are not returned during the HTTP POST
    if (is_array($arrVar)) {
        foreach ($PMF_CONF as $key => $value) {
            if (!array_key_exists($key, $arrVar)) {
                $PMF_CONF[$key] = 'false';
            }
        }
    }

    $faqconfig->update($PMF_CONF);
}
// Lists the current configuration
if ('listConfig' == $userAction) {
    $message = '';
    $userAction = $defaultConfigAction;
?>

<h2><?php print $PMF_LANG['ad_config_edit']; ?></h2>

<div id="user_message"><?php print $message; ?></div>

<form id="config_list" name="config_list" action="?action=config&amp;config_action=saveConfig" method="post">
    <fieldset>
        <legend><?php print $PMF_LANG['ad_config_edit']; ?></legend>
        <div id="configStd"></div>
    </fieldset>
    <fieldset>
        <legend><?php print $PMF_LANG['spamControlCenter']; ?></legend>
        <div id="configSpam"></div>
    </fieldset>
    <p align="center">
        <input class="submit" type="submit" value="<?php print $PMF_LANG['ad_config_save']; ?>" />
        <input class="submit" type="reset" value="<?php print $PMF_LANG['ad_config_reset']; ?>" />
    </p>
</form>

<script type="text/javascript">
/* <![CDATA[ */

function getConfigList()
{
    var ajax = new Ajax.Updater('configStd',  'index.php?action=ajax&ajax=config_list');
    var ajax = new Ajax.Updater('configSpam', 'index.php?action=ajax&ajax=config_list&conf=spam');
}

getConfigList();

/* ]]> */
</script>

<?php
}
