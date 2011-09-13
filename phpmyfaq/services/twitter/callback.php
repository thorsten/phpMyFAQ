<?php
/**
 * Take the user when they return from Twitter. Get access tokens.
 * Verify credentials and redirect to based on response from Twitter.
 *
 * PHP Version 5.2
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
 * 
 * @category  phpMyFAQ
 * @package   Services
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-18
 */

//
// Prepend and start the PHP session
//
define('PMF_ROOT_DIR', dirname(dirname(dirname(__FILE__))));
define('IS_VALID_PHPMYFAQ', null);

require_once PMF_ROOT_DIR . '/inc/Init.php';
require_once PMF_ROOT_DIR . '/inc/libs/twitteroauth/twitteroauth.php';

PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$oAuthToken    = PMF_Filter::filterInput(INPUT_GET, 'oauth_token', FILTER_SANITIZE_STRING);
$oAuthVerifier = PMF_Filter::filterInput(INPUT_GET, 'oauth_verifier', FILTER_SANITIZE_STRING);

if (!is_null($oAuthToken) && $_SESSION['oauth_token'] !== $oAuthToken) {
  $_SESSION['oauth_status'] = 'oldtoken';
  header('Location: ./clearsessions.php');
}

$connection = new TwitterOAuth($faqconfig->get('socialnetworks.twitterConsumerKey'),
                               $faqconfig->get('socialnetworks.twitterConsumerSecret'),
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