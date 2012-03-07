<?php
/**
 * The main configuration frontend
 *
 * PHP 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['editconfig']) {
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');

    // Save the configuration
    if ('saveConfig' == $userAction) {

        $checks          = array('filter' => FILTER_SANITIZE_STRING,
                                 'flags'  => FILTER_REQUIRE_ARRAY);
        $editData        = PMF_Filter::filterInputArray(INPUT_POST, array('edit' => $checks));
        $message         = '';
        $userAction      = 'listConfig';
        $oldConfigValues = $faqConfig->config;

        /* XXX the cache concept is designed to be able to activate only one cache engine per time
               so if there are more cache services implemented, respect it here*/
        if (isset($editData['edit']['cache.varnishEnable']) && 'true' == $editData['edit']['cache.varnishEnable']) {
            if (!extension_loaded('varnish')) {
                throw new Exception('Varnish extension is not loaded');
            }
        }

        // Set the new values
        $forbiddenValues = array('{', '}', '$');
        $newConfigValues = array();
        foreach ($editData['edit'] as $key => $value) {
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
        }

        foreach ($oldConfigValues as $key => $value) {
            if (isset($newConfigValues[$key])) {
                continue;
            } else {
                if ($oldConfigValues[$key] == 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $oldConfigValues[$key];
                }
            }
        }

        $faqConfig->update($newConfigValues);
    }
    // Lists the current configuration
    if ('listConfig' == $userAction) {
        $message    = '';
        $userAction = 'listConfig';
?>

        <header>
            <h2><?php print $PMF_LANG['ad_config_edit']; ?></h2>
        </header>

        <div id="user_message"><?php print $message; ?></div>

        <form id="config_list" name="config_list" action="?action=config&amp;config_action=saveConfig" method="post">
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Main');">
                        <?php print $PMF_LANG['mainControlCenter']; ?>
                    </a>
                </legend>
                <div id="configMain" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Records');">
                        <?php print $PMF_LANG['recordsControlCenter']; ?>
                    </a>
                </legend>
                <div id="configRecords" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Search');">
                        <?php print $PMF_LANG['searchControlCenter']; ?>
                    </a>
                </legend>
                <div id="configSearch" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Security');">
                        <?php print $PMF_LANG['securityControlCenter']; ?>
                    </a>
                </legend>
                <div id="configSecurity" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Spam');">
                        <?php print $PMF_LANG['spamControlCenter']; ?>
                    </a>
                </legend>
                <div id="configSpam" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('SocialNetworks');">
                        <?php print $PMF_LANG['socialNetworksControlCenter']; ?>
                    </a>
                </legend>
                <div id="configSocialNetworks" style="display: none;"></div>
            </fieldset>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleConfig('Cache');">
                        <?php print $PMF_LANG['cacheControlCenter']; ?>
                    </a>
                </legend>
                <div id="configCache" style="display: none;"></div>
            </fieldset>
            <p>
                <input class="btn-primary" type="submit" value="<?php print $PMF_LANG['ad_config_save']; ?>" />
                <input class="btn-inverse" type="reset" value="<?php print $PMF_LANG['ad_config_reset']; ?>" />
            </p>
        </form>

        <script type="text/javascript">
        /* <![CDATA[ */

        function getConfigList()
        {
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "main" }, function(data) {
                $('#configMain').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "records" }, function(data) {
                $('#configRecords').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "search" }, function(data) {
                $('#configSearch').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "security" }, function(data) {
                $('#configSecurity').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "spam" }, function(data) {
                $('#configSpam').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "socialnetworks" }, function(data) {
                $('#configSocialNetworks').append(data);
            });
            $.get("index.php", {action: "ajax", ajax: "config_list", conf: "cache" }, function(data) {
                $('#configCache').append(data);
            });
        }

        getConfigList();

        /* ]]> */
        </script>

<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
