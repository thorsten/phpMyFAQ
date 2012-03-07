<?php
/**
 * Clears PHP sessions and redirects to the connect page.
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-18
 */

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));
define('IS_VALID_PHPMYFAQ', null);

require_once PMF_ROOT_DIR . '/inc/Bootstrap.php';
require_once PMF_ROOT_DIR . '/inc/libs/twitteroauth/twitteroauth.php';

PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqConfig->get('main.phpMyFAQToken')));
session_start();

$connection = new TwitterOAuth($faqConfig->get('socialnetworks.twitterConsumerKey'),
                               $faqConfig->get('socialnetworks.twitterConsumerSecret'));

$requestToken = $connection->getRequestToken($faqConfig->get('main.referenceURL') .
                                             '/services/twitter/callback.php');

$_SESSION['oauth_token']        = $requestToken['oauth_token'];
$_SESSION['oauth_token_secret'] = $requestToken['oauth_token_secret'];

switch ($connection->http_code) {
    case 200:
        $url = $connection->getAuthorizeURL($requestToken['oauth_token']);
        header('Location: ' . $url);
        break;
    default:
        print 'Could not connect to Twitter. Refresh the page or try again later.';
        break;
}