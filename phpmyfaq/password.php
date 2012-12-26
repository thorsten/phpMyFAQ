<?php
/**
 * This is the page there a user can request a new password.
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-26
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}



$tpl->parse(
    'writeContent',
    array(
        'headerChangePassword'  => $PMF_LANG["ad_passwd_cop"],
        'msgUsername'           => $PMF_LANG["ad_auth_user"],
        'msgEmail'              => $PMF_LANG["ad_entry_email"],
        'msgSubmit'             => $PMF_LANG["msgNewContentSubmit"],

    )
);

$tpl->merge('writeContent', 'index');
