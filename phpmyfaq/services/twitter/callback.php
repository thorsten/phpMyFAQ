<?php

/**
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2021 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-09-18
 */

use Abraham\TwitterOAuth\TwitterOAuth;
use phpMyFAQ\Filter;

//
// Prepend and start the PHP session
//
define('IS_VALID_PHPMYFAQ', null);

//
// Bootstrapping
//
require dirname(dirname(__DIR__)) . '/src/Bootstrap.php';

$requestToken = [];
$requestToken['oauth_token'] = $_SESSION['oauth_token'];
$requestToken['oauth_token_secret'] = $_SESSION['oauth_token_secret'];

$oAuthToken = Filter::filterInput(INPUT_GET, 'oauth_token', FILTER_SANITIZE_STRING);
$oAuthVerifier = Filter::filterInput(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_STRING);

if (isset($_REQUEST['denied'])) {
    exit('Permission was denied. Please start over.');
}

if (isset($oAuthToken) && $requestToken['oauth_token'] !== $oAuthToken) {
    $_SESSION['oauth_status'] = 'oldtoken';
    header('Location: ./clearsessions.php');
    exit;
}

$connection = new TwitterOAuth(
    $faqConfig->get('socialnetworks.twitterConsumerKey'),
    $faqConfig->get('socialnetworks.twitterConsumerSecret'),
    $requestToken['oauth_token'],
    $requestToken['oauth_token_secret']
);

$accessToken = $connection->oauth('oauth/access_token', ['oauth_verifier' => $oAuthVerifier]);

if (200 === $connection->getLastHttpCode()) {
    unset($_SESSION['oauth_token']);
    unset($_SESSION['oauth_token_secret']);
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['status'] = 'verified';

    header('Location: ./index.php');
} else {
    header('Location: ./clearsessions.php');
}
