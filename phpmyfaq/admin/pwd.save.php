<?php
/**
 * Save the password of the current user in the database.
 *
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @since      2003-02-23
 * @version    SVN: $Id$ 
 * @copyright (c) 2003-2009 phpMyFAQ Team
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

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

// Define the (Local/Current) Authentication Source
$_authSource = PMF_User_Auth::selectAuth($user->_auth_data['authSource']['name']);
$_authSource->selectEncType($user->_auth_data['encType']);
$_authSource->read_only($user->_auth_data['readOnly']);

if (    ($_authSource->encrypt($_REQUEST["opass"]) == $user->_encrypted_password)
     && ($_REQUEST["npass"] == $_REQUEST["bpass"])
    ) {
    if (!$user->changePassword($_REQUEST["npass"])) {
        print $PMF_LANG["ad_passwd_fail"]."<br />";
        exit(0);
    }
    print $PMF_LANG["ad_passwdsuc"]."<br />";

    // TODO: Manage the 'Rembember me' Cookie also under 2.0.0.
    if (isset($_COOKIE['cuser'])) {
        if ($_COOKIE["cuser"] == $user) {
            print $PMF_LANG["ad_passwd_remark"]."<br /><a href=\"?action=setcookie\">".$PMF_LANG["ad_cookie_set"]."</a>\n";
        }
    }
} else {
    print $PMF_LANG["ad_passwd_fail"];
}
