<?php
/**
 * User Control Panel
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
 * @since     2012-01-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user instanceof PMF_User) {

    $tpl->parse(
        'writeContent',
        array(
            'headerUserControlPanel' => $PMF_LANG['headerUserControlPanel'],
            'userid'                 => $user->getUserId(),
            'msgRealName'            => $PMF_LANG['ad_user_name'],
            'realname'               => $user->getUserData('display_name'),
            'msgEmail'               => $PMF_LANG['msgNewContentMail'],
            'email'                  => $user->getUserData('email'),
            'msgPassword'            => $PMF_LANG['ad_auth_passwd'],
            'msgConfirm'             => $PMF_LANG['ad_user_confirm'],
            'msgSave'                => $PMF_LANG['msgSave'],
            'msgCancel'              => $PMF_LANG['msgCancel']
        )
    );

    $tpl->merge('writeContent', 'index');
} else {
    // Redirect to login
    header('Location: ' . $faqConfig->get('main.referenceURL') . '/');
    exit();
}
