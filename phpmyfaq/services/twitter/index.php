<?php

/**
 * User has successfully authenticated with Twitter. Access tokens saved to
 * session and database.
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

if (empty($_SESSION['access_token']) ||
    empty($_SESSION['access_token']['oauth_token']) ||
    empty($_SESSION['access_token']['oauth_token_secret'])) {
    header('Location: ./clearsessions.php');
}

$accessToken = $_SESSION['access_token'];

$connection = new TwitterOAuth(
    $faqConfig->get('socialnetworks.twitterConsumerKey'),
    $faqConfig->get('socialnetworks.twitterConsumerSecret'),
    $accessToken['oauth_token'],
    $accessToken['oauth_token_secret']
);

$content = $connection->get('account/verify_credentials');

if (isset($content->screen_name)) {
    header('Location: ../../admin/index.php');
}
