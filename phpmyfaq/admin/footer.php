<?php
/**
 * Footer of the admin area
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
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2003-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$templateVars = array(
    'PMF_LANG'            => $PMF_LANG,
    'debugInformation'    => DEBUG ? $faqConfig->getDb()->log() : '',
    'formAction'          => 'index.php' . (isset($action) ? '?action=' . $action : ''),
    'isAuthenticated'     => isset($auth),
    'languageSelector'    => PMF_Language::selectLanguages($LANGCODE, true),
    'pmfVersion'          => $faqConfig->get('main.currentVersion'),
    'sessionKeepaliveUrl' => 'session.keepalive.php?lang=' . $LANGCODE,
    'userDisplayName'     => isset($user) ? $user->userdata->get('display_name') : '',
    'userId'              => isset($user) ? $user->userdata->get('user_id') : ''
);

$wysiwygActions = array(
    'takequestion',
    'editentry',
    'editpreview',
    'addnews',
    'editnews',
    'copyentry'
);

if (isset($auth) && in_array($action, $wysiwygActions) && $faqConfig->get('main.enableWysiwygEditor')) {
    $templateVars['wysiwygActive']     = true;
    $templateVars['tinyMceContentCss'] = '../assets/template/' . PMF_Template::getTplSetName() . '/css/style.css';
    $templateVars['tinyMceLanguage']   = PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en';
    if (('addnews' == $action || 'editnews' == $action)) {
        $templateVars['tinyMceElements'] = 'news';
        $templateVars['tinyMceSave']     = '';
    } else {
        $templateVars['tinyMceElements'] = 'answer';
        $templateVars['tinyMceSave']     = 'save,|,';
    }
    if (isset($faqData['id']) && $faqData['id'] == 0) {
        $templateVars['tinyMceSaveCallbackAction'] = 'recordAdd';
    } else {
        $templateVars['tinyMceSaveCallbackAction'] = 'recordSave';
    }
} else {
    $templateVars['wysiwygActive'] = false;
}

$twig->loadTemplate('footer.twig')
    ->display($templateVars);

unset($templateVars, $wysiwygActions);
