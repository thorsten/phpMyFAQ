<?php
/**
 * User has successfully authenticated with Twitter. Access tokens saved to
 * session and database.
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

if (empty($_SESSION['access_token']) ||
    empty($_SESSION['access_token']['oauth_token']) ||
    empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}

$accessToken = $_SESSION['access_token'];

$connection = new TwitterOAuth($faqconfig->get('socialnetworks.twitterConsumerKey'),
                               $faqconfig->get('socialnetworks.twitterConsumerSecret'),
                               $accessToken['oauth_token'],
                               $accessToken['oauth_token_secret']);

$content = $connection->get('account/verify_credentials');

if (isset($content->screen_name)) {
    header('../admin/index.php');
}
