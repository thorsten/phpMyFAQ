<?php
/**
 * The main configuration frontend
 *
 * PHP 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'editconfig')) {
    // actions defined by url: user_action=
    $userAction = PMF_Filter::filterInput(INPUT_GET, 'config_action', FILTER_SANITIZE_STRING, 'listConfig');

    // Save the configuration
    if ('saveConfig' === $userAction) {

        $checks = array(
            'filter' => FILTER_SANITIZE_STRING,
            'flags'  => FILTER_REQUIRE_ARRAY
        );
        $editData        = PMF_Filter::filterInputArray(INPUT_POST, array('edit' => $checks));
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
        $newConfigValues = [];
        foreach ($editData['edit'] as $key => $value) {
            $newConfigValues[$key] = str_replace($forbiddenValues, '', $value);
            $keyArray              = array_values(explode('.', $key));
            $newConfigClass        = array_shift($keyArray);
        }

        foreach ($oldConfigValues as $key => $value) {
            $keyArray       = array_values(explode('.', $key));
            $oldConfigClass = array_shift($keyArray);
            if (isset($newConfigValues[$key])) {
                continue;
            } else {
                if ($oldConfigClass === $newConfigClass && $oldConfigValues[$key] === 'true') {
                    $newConfigValues[$key] = 'false';
                } else {
                    $newConfigValues[$key] = $oldConfigValues[$key];
                }
            }
        }

        if (! is_null($editData)) {
            $faqConfig->update($newConfigValues);
        }
    }
    // Lists the current configuration
    if ('listConfig' === $userAction) {
        $userAction = 'listConfig';
    }

    $twig->loadTemplate('configuration.twig')
        ->display(
            array('PMF_LANG' => $PMF_LANG)
        );
} else {
    require 'noperm.php';
}
