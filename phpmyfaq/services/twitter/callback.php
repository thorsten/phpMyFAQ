<?php
/**
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
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

$oAuthToken    = PMF_Filter::filterInput(INPUT_GET, 'oauth_token', FILTER_SANITIZE_STRING);
$oAuthVerifier = PMF_Filter::filterInput(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_STRING);

if (!is_null($oAuthToken) && $_SESSION['oauth_token'] !== $oAuthToken) {
  $_SESSION['oauth_status'] = 'oldtoken';
  header('Location: ./clearsessions.php');
}

$connection = new TwitterOAuth($faqConfig->get('socialnetworks.twitterConsumerKey'),
                               $faqConfig->get('socialnetworks.twitterConsumerSecret'),
                               $_SESSION['oauth_token'],
                               $_SESSION['oauth_token_secret']);

$accessToken              = $connection->getAccessToken($oAuthVerifier);
$_SESSION['access_token'] = $accessToken;

unset($_SESSION['oauth_token']);
unset($_SESSION['oauth_token_secret']);

if (200 === $connection->http_code) {
  $_SESSION['status'] = 'verified';
  header('Location: ./index.php');
} else {
  header('Location: ./clearsessions.php');
}