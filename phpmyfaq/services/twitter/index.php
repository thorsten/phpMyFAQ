<?php

/**
 * User has successfully authenticated with Twitter. Access tokens saved to
 * session and database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-18
 */

use Abraham\TwitterOAuth\TwitterOAuth;
use Abraham\TwitterOAuth\TwitterOAuthException;
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

if (
    empty($_SESSION['access_token']) ||
    empty($_SESSION['access_token']['oauth_token']) ||
    empty($_SESSION['access_token']['oauth_token_secret'])
) {
    $redirect = new RedirectResponse('./clearsessions.php');
    $redirect->send();
}

$accessToken = $_SESSION['access_token'];

$connection = new TwitterOAuth(
    $faqConfig->get('socialnetworks.twitterConsumerKey'),
    $faqConfig->get('socialnetworks.twitterConsumerSecret'),
    $accessToken['oauth_token'],
    $accessToken['oauth_token_secret']
);
try {
    $connection->setApiVersion('2');
} catch (TwitterOAuthException $e) {
    // Handle exception
}

$content = $connection->get('account/verify_credentials');

if (isset($content->screen_name)) {
    $redirect = new RedirectResponse('../../admin/index.php');
    $redirect->send();
}
