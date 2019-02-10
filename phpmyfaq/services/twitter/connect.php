<?php

/**
 * Clears PHP sessions and redirects to the connect page.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2010-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-18
 */

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(dirname(__DIR__)));
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require PMF_ROOT_DIR.'/inc/Bootstrap.php';

if ($faqConfig->get('socialnetworks.twitterConsumerKey') === '' ||
    $faqConfig->get('socialnetworks.twitterConsumerSecret') === '') {
    print 'Get a consumer key and secret from <a href="https://twitter.com/apps">twitter.com</a>.';
    exit;
}

print '<a href="./redirect.php"><img src="../assets/img/twitter.signin.png" alt="Sign in with Twitter"/></a>';
