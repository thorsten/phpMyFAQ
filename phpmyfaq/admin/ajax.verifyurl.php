<?php
/**
* $Id: ajax.verifyurl.php,v 1.10 2006-09-18 21:20:46 matteo Exp $
*
* AJAX: verifyurl
*
* Usage:
*   index.php?uin=<uin>&action=ajax&ajax=verifyURL&id=<id>&lang=<lang>
*
* Performs link verification when entries are shown in record.show.php
*
* @author           Minoru TODA <todam@netjapan.co.jp>
* @since            2005-09-30
* @copyright        (c) 2005-2006 NetJapan, Inc.
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
* The Initial Developer of the Original Code is released for external use
* with permission from NetJapan, Inc. IT Administration Group.
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header("HTTP/1.0 401 Unauthorized");
    header("Status: 401 Unauthorized");
    exit();
}

@header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
@header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
@header("Cache-Control: no-store, no-cache, must-revalidate");
@header("Cache-Control: post-check=0, pre-check=0", false);
@header("Pragma: no-cache");
@header("Content-type: text/html");
@header("Vary: Negotiate,Accept");

$linkverifier = new PMF_Linkverifier();
if ($linkverifier->isReady() == false) {
    if (count(ob_list_handlers()) > 0) {
        ob_clean();
    }
    print "disabled";
    exit();
}

$linkverifier->loadConfigurationFromDB();

if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $id = $_GET["id"];
}

if (isset($_GET["lang"])) {
    $lang = $_GET["lang"];
}

if (!(isset($id) && isset($lang))) {
    //header("X-DenyReason: id/lang bad");
    header("HTTP/1.0 401 Unauthorized");
    header("Status: 401 Unauthorized");
    exit();
}

$faq->faqRecord = null;
$faq->getRecord($id);

if (!isset($faq->faqRecord['content'])) {
    header("HTTP/1.0 401 Unauthorized");
    header("Status: 401 Unauthorized");
    exit();
}

if (count(ob_list_handlers()) > 0) {
    ob_clean();
}

$linkverifier->parse_string($faq->faqRecord['content']);
$linkverifier->VerifyURLs($PMF_CONF['referenceURL']);
$linkverifier->markEntry($id, $lang);
print $linkverifier->getLinkStateString();
exit();
