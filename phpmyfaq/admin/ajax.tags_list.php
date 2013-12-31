<?php
/**
 * AJAX: Search for tags
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Ajax
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

use Symfony\Component\HttpFoundation\JsonResponse;
use PMF\Helper\ResponseWrapper;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$response = new JsonResponse;
$responseWrapper = new ResponseWrapper($response);
$responseWrapper->addCommonHeaders();

$oTag              = new PMF_Tags($faqConfig);
$autoCompleteValue = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
if (! is_null($autoCompleteValue)) {
    if (strpos($autoCompleteValue, ',')) {
        $arrayOfValues     = explode(',', $autoCompleteValue);
        $autoCompleteValue = end($arrayOfValues);
    }
    $tags = $oTag->getAllTags(strtolower($autoCompleteValue), false, true);
} else {
    $tags = $oTag->getAllTags();
}

if ($permission['editbt']) {
    $i = 0;
    $tagNames = [];
    foreach ($tags as $tagName) {
        $i++;
        if ($i <= PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE) {
            $tagNames[] = $tagName;
        }
    }

    $response->setData(array('tags' => $tagNames));
}

$response->send();
