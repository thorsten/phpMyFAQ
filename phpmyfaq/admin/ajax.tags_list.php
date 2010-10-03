<?php
/**
 * AJAX: searches the tags
 *
 * @todo Switch code and logic to jQuery and PHP JSON extension
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
 * @package   Ajax
 * @author    Matteo Scaramuccia <matteo@scaramuccia.com>
 * @copyright 2005-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-12-15
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Vary: Negotiate,Accept");

$oTag              = new PMF_Tags();
$autoCompleteValue = PMF_Filter::filterInput(INPUT_GET, 'q', FILTER_SANITIZE_STRIPPED);
if (!is_null($autoCompleteValue)) {
    $tags = $oTag->getAllTags($autoCompleteValue);
} else {
    $tags = $oTag->getAllTags();
}

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}

if ($permission['editbt']) {
    $i = 0;
    foreach ($tags as $tagName) {
        $i++;
        if ($i <= PMF_TAGS_AUTOCOMPLETE_RESULT_SET_SIZE) {
            print $tagName . "\n";
        }
    }
}
