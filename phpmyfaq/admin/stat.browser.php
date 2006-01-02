<?php
/******************************************************************************
 * File:				stat.browser.php
 * Description:			sessionbrowser
 * Authors:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-24
 * Last change:			2004-06-18
 * Copyright:           (c) 2001-2006 phpMyFAQ Team
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
if ($permission["viewlog"]) {
	$perpage = 50;
	$day = $_REQUEST["day"];
	$firstHour = mktime (0, 0, 0, date("m", $day), date("d", $day), date("Y", $day));
	$lastHour = mktime (23, 59, 59, date("m", $day), date("d", $day), date("Y", $day));
	$query = "SELECT sid, ip, time from ".SQLPREFIX."faqsessions WHERE time > ".$firstHour." AND time < ".$lastHour." ORDER BY time";
	$laktion = "presentsessionresult&day=".$day;
?>
	<h2><?php print "Session ".date("Y-m-d", $day); ?></h2>
    <table class="list">
    <thead>
        <tr>
            <th class="list">IP</th>
            <th class="list">&nbsp;</th>
            <th class="list">Session</th>
        </tr>
    </thead>
    <tbody>
<?php
	$result = $db->query($query);
	while ($row = $db->fetch_object($result)) {
?>
        <tr>
            <td class="list"><?php print $row->ip; ?></td>
            <td class="list"><?php print date("Y-m-d H:i:s", $row->time); ?></td>
            <td class="list"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=viewsession&amp;id=<?php print $row->sid; ?>"><?php print $row->sid; ?></a></td>
	</tr>
<?php
		}
?>	
    </tbody>
    </table>
<?php
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>