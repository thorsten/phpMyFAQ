<?php
/**
* $Id: ajax.config_list.php,v 1.1 2005-12-26 13:37:56 thorstenr Exp $
*
* AJAX: lists the complete configuration items
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2005-12-26
* @copyright    (c) 2005 phpMyFAQ Team
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
*/

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once(PMF_ROOT_DIR.'/inc/config.php');
require_once(PMF_ROOT_DIR.'/lang/language_en.php');

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: ".gmdate("D, d M Y H:i:s")." GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-type: text/xml");
header("Vary: Negotiate,Accept");

ob_clean();

printf("<?xml version=\"1.0\" encoding=\"%s\" ?>\n", strtolower($PMF_LANG['metaCharset']));
print "<ajax-response>\n";
print "    <response type=\"object\" id=\"configurationDetails\">\n";
print "        <configlist\n>";
foreach ($LANG_CONF as $key => $value) {
    if (isset($PMF_CONF[$key])) {
        print "            <item>\n";
        printf("                <xhtmltag>%s</xhtmltag>\n", $value[0]);
        printf("                <label>%s</label>\n", $value[1]);
        printf("                <key>%s</key>\n", $key);
        printf("                <value>%s</value>\n", $PMF_CONF[$key]);
        print "            </item>\n";
    }
}
print "        </configlist>\n";
print "    </response>\n";
print "</ajax-response>\n";