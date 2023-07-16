<?php

/**
 * Clears PHP sessions and redirects to the connect page.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Thomas Zeithaml <tom@annatom.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-18
 */

use Abraham\TwitterOAuth\TwitterOAuth;
use phpMyFAQ\Configuration;
use Symfony\Component\HttpFoundation\RedirectResponse;

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(__DIR__, 2));
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require PMF_ROOT_DIR . '/src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

$connection = new TwitterOAuth(
    $faqConfig->get('socialnetworks.twitterConsumerKey'),
    $faqConfig->get('socialnetworks.twitterConsumerSecret')
);

$requestToken = $connection->oauth(
    'oauth/request_token',
    ['oauth_callback' => $faqConfig->getDefaultUrl() . 'services/twitter/callback.php']
);

$_SESSION['oauth_token'] = $requestToken['oauth_token'];
$_SESSION['oauth_token_secret'] = $requestToken['oauth_token_secret'];

switch ($connection->getLastHttpCode()) {
    case 200:
        $url = $connection->url('oauth/authorize', ['oauth_token' => $requestToken['oauth_token']]);
        $redirect = new RedirectResponse($url);
        $redirect->send();
        break;
    default:
        echo 'Could not connect to Twitter. Refresh the page or try again later.';
        break;
}
