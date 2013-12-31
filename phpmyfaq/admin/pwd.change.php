<?php
/**
 * Form to change password of the current user
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission["passwd"]) {
    $templateVars = array(
        'PMF_LANG'       => $PMF_LANG,
        'successMessage' => '',
        'errorMessage'   => ''
    );

    // If we have to save a new password, do that first
    $save = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_SANITIZE_STRING);
    if (!is_null($save)) {

        // Define the (Local/Current) Authentication Source
        $auth       = new PMF_Auth($faqConfig);
        $authSource = $auth->selectAuth($user->getAuthSource('name'));
        $authSource->selectEncType($user->getAuthData('encType'));
        $authSource->setReadOnly($user->getAuthData('readOnly'));

        $oldPassword     = PMF_Filter::filterInput(INPUT_POST, 'opass', FILTER_SANITIZE_STRING);
        $newPassword     = PMF_Filter::filterInput(INPUT_POST, 'npass', FILTER_SANITIZE_STRING);
        $retypedPassword = PMF_Filter::filterInput(INPUT_POST, 'bpass', FILTER_SANITIZE_STRING);

        if (($authSource->checkPassword($user->getLogin(), $oldPassword)) && ($newPassword == $retypedPassword)) {
            if (!$user->changePassword($newPassword)) {
                $templateVars['errorMessage'] = $PMF_LANG["ad_passwd_fail"];
            } else {
                $templateVars['successMessage'] = $PMF_LANG["ad_passwdsuc"];
            }
        } else {
            $templateVars['errorMessage'] = $PMF_LANG["ad_passwd_fail"];
        }
    }

    $twig->loadTemplate('user/passwd.twig')
        ->display($templateVars);

    unset($templateVars);
} else {
    require 'noperm.php';
}