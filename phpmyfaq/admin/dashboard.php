<?php
/**
 * The start page with some information about the FAQ
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alexander M. Turek <me@derrabus.de>
 * @copyright 2005-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2013-02-05
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$faqTableInfo = $faqConfig->getDb()->getTableStatus();

$templateVars = array(
    'PMF_LANG'                 => $PMF_LANG,
    'dashboardArticles'        => $faqTableInfo[PMF_Db::getTablePrefix() . "faqdata"],
    'dashboardComments'        => $faqTableInfo[PMF_Db::getTablePrefix() . "faqcomments"],
    'dashboardNews'            => $faqTableInfo[PMF_Db::getTablePrefix() . "faqnews"],
    'dashboardOpenQuestions'   => $faqTableInfo[PMF_Db::getTablePrefix() . "faqquestions"],
    'dashboardUsers'           => $faqTableInfo[PMF_Db::getTablePrefix() . 'faquser'] - 1,
    'dashboardVisits'          => $faqTableInfo[PMF_Db::getTablePrefix() . 'faqsessions'],
    'enableUserTracking'       => $faqConfig->get('main.enableUserTracking'),
    'inMaintenanceMode'        => $faqConfig->get('main.maintenanceMode'),
    'onlineVerificationActive' => false,
    'onlineVerificationError'  => false,
    'updateCheckActive'        => false
);

if ($faqConfig->get('main.enableUserTracking')) {
    $session = new PMF_Session($faqConfig);
    $visits  = $session->getLast30DaysVisits();

    $templateVars['visitsData'] = implode(',', $visits);

    unset($session, $visits);
}

// Perform update check
$version = PMF_Filter::filterInput(INPUT_POST, 'param', FILTER_SANITIZE_STRING);
if (!is_null($version) && $version == 'version') {
    $json   = file_get_contents('http://www.phpmyfaq.de/api/version');
    $result = json_decode($json);
    if ($result instanceof stdClass) {
        $installed                         = $faqConfig->get('main.currentVersion');
        $available                         = $result->stable;
        $templateVars['updateCheckActive'] = true;
        $templateVars['updateAvailable']   = -1 == version_compare($installed, $available);
        $templateVars['lastestVersion']    = $available;
    }
}
unset($json, $result, $installed, $available, $version);

// Perform online verification
$getJson = PMF_Filter::filterInput(INPUT_POST, 'getJson', FILTER_SANITIZE_STRING);
if (!is_null($getJson) && 'verify' === $getJson) {
    $templateVars['onlineVerificationActive'] = true;

    $faqSystem    = new PMF_System();
    $localHashes  = $faqSystem->createHashes();
    $remoteHashes = file_get_contents(
        'http://www.phpmyfaq.de/api/verify/' . $faqConfig->get('main.currentVersion')
    );

    if (!is_array(json_decode($remoteHashes, true))) {
        $templateVars['onlineVerificationError'] = true;
    } else {
        $diff = array_diff(
            json_decode($localHashes, true),
            json_decode($remoteHashes, true)
        );

        if (1 < count($diff)) {
            $templateVars['onlineVerificationSuccessful'] = false;
            $templateVars['onlineVerificationDiff']       = array();

            foreach ($diff as $file => $hash) {
                if ('created' === $file) {
                    continue;
                }
                $templateVars['onlineVerificationDiff'][$hash] = $file;
            }
        } else {
            $templateVars['onlineVerificationSuccessful'] = true;
        }
    }
}
unset($getJson, $faqSystem, $localHashes, $remoteHashes, $diff, $file, $hash);

$twig->loadTemplate('dashboard.twig')
    ->display($templateVars);

unset($templateVars);

