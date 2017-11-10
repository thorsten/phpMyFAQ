<?php

/**
 * AJAX: verifyurl.
 *
 * Usage:
 *   index.php?uin=<uin>&action=ajax&ajax=verifyURL&id=<id>&artlang=<lang>
 *
 * Performs link verification when entries are shown in record.show.php
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * The Initial Developer of the Original Code is released for external use
 * with permission from NetJapan, Inc. IT Administration Group.
 *
 * @category  phpMyFAQ
 *
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2017 NetJapan, Inc. and phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */
<<<<<<< HEAD

use Symfony\Component\HttpFoundation\Response;
use PMF\Helper\ResponseWrapper;

=======
>>>>>>> 2.10
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$response = new Response;
$responseWrapper = new ResponseWrapper($response);
$responseWrapper->addCommonHeaders();

$linkVerifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
if ($linkVerifier->isReady() === false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
<<<<<<< HEAD
    $response
        ->setContent("disabled")
        ->send();
    exit;
=======
    print 'disabled';
    exit();
>>>>>>> 2.10
}

$id = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$lang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);

if (!(isset($id) && isset($lang))) {
<<<<<<< HEAD
    $response
        ->setStatusCode(401)
        ->send();
    exit;
=======
    header('HTTP/1.0 401 Unauthorized');
    header('Status: 401 Unauthorized');
    exit();
>>>>>>> 2.10
}

$faq->faqRecord = null;
$faq->getRecord($id);

if (!isset($faq->faqRecord['content'])) {
<<<<<<< HEAD
    $response
        ->setStatusCode(401)
        ->send();
    exit;
=======
    header('HTTP/1.0 401 Unauthorized');
    header('Status: 401 Unauthorized');
    exit();
>>>>>>> 2.10
}

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}

<<<<<<< HEAD
$linkverifier->parse_string($faq->faqRecord['content']);
$linkverifier->VerifyURLs($faqConfig->get('main.referenceURL'));
$linkverifier->markEntry($id, $lang);
$response
    ->setContent($linkverifier->getLinkStateString())
    ->send();
=======
$linkVerifier->parseString($faq->faqRecord['content']);
$linkVerifier->verifyURLs($faqConfig->getDefaultUrl());
$linkVerifier->markEntry($id, $lang);
print $linkVerifier->getLinkStateString();
exit();
>>>>>>> 2.10
