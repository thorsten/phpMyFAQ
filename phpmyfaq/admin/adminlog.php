<?php
/**
* $Id: adminlog.php,v 1.10 2006-05-30 09:27:08 thorstenr Exp $
*
* Overview of actions in the admin section
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2006 phpMyFAQ Team
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

if ($permission['adminlog'] && 'adminlog' == $_action) {

    //
    // Show the adminlog
    //

	$perpage = 15;

    $_user = array();

    $_result = $db->query("SELECT id, name FROM ".SQLPREFIX."faquser");
    while ($row = $db->fetch_object($_result)) {
        $_user[$row->id] = $row->name;
    }

	if (!isset($_REQUEST["pages"])) {
        $pages = round(($db->num_rows($db->query("SELECT id FROM ".SQLPREFIX."faqadminlog"))+($perpage/3)) / $perpage,0);
    } else {
        $pages = $_REQUEST["pages"];
    }

	if (!isset($_REQUEST["page"])) {
        $page = 1;
    } else {
        $page = $_REQUEST["page"];
    }

	$start = ($page-1) * $perpage;
	$ende = $start + $perpage;

	$PageSpan = PageSpan("<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=adminlog&amp;pages=".$pages."&amp;page=<NUM>\">", 1, $pages, $page);

	$result = $db->query("SELECT id, time, usr, text, ip FROM ".SQLPREFIX."faqadminlog ORDER BY id DESC");
?>
	<h2><?php print $PMF_LANG["ad_adminlog"]; ?></h2>
    <table class="list">
    <thead>
        <tr>
            <th class="list"><?php print $PMF_LANG["ad_categ_id"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_adminlog_date"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_adminlog_user"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_adminlog_ip"]; ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
		    <td class="list" colspan="4"><?php print $PageSpan; ?></td>
        </tr>
    </tfoot>
    <tbody>
<?php
    $counter = 0;
    $displayedCounter = 0;
    while (($row = $db->fetch_object($result)) && $displayedCounter < $perpage) {
        $counter++;
        if ($counter <= $start) {
            continue;
        }
        $displayedCounter++;
?>
        <tr class="cell">
            <td class="list"><?php print $row->id; ?></td>
            <td class="list"><?php print date("Y-m-d H:i:s",$row->time); ?></td>
            <td class="list"><?php print isset($_user[$row->usr]) ? $_user[$row->usr] : '&nbsp;'; ?></td>
            <td class="list"><?php print $row->ip; ?></td>
        </tr>
        <tr class="cell">
            <td colspan="4" class="list"><?php
		$text = str_replace("Loginerror", $PMF_LANG["ad_log_lger"], $row->text);
		$text = str_replace("Session expired", $PMF_LANG["ad_log_sess"], $row->text);
		$text = str_replace("Useredit, ", $PMF_LANG["ad_log_edit"], $row->text);
		$text = str_replace("Beitragcreatesave", $PMF_LANG["ad_log_crsa"], $row->text);
		$text = str_replace("Beitragcreate", $PMF_LANG["ad_log_crea"], $row->text);
		$text = str_replace("Usersave, ", $PMF_LANG["ad_log_ussa"], $row->text);
		$text = str_replace("Userdel, ", $PMF_LANG["ad_log_usde"], $row->text);
		$text = str_replace("Beitragedit, ", $PMF_LANG["ad_log_beed"], $row->text);
		$text = str_replace("Beitragdel, ", $PMF_LANG["ad_log_bede"], $row->text);
		print $text;
?></td>
        </tr>
<?php
    }
?>
	</tbody>
	</table>
<?php

    printf ('<p><a href="?aktion=deleteadminlog">%s</a></p>', $PMF_LANG['ad_adminlog_del_older_30d']);

} elseif ($permission['adminlog'] && 'deleteadminlog' == $_action) {

    //
    // Delete logs older than 30 days
    //
    $thirtydays = time() - 30 * 86400;

    if ($db->query('DELETE FROM '.SQLPREFIX.'faqadminlog WHERE time < '.$thirtydays)) {
        printf('<p>%s</p>', $MPF_LANG['ad_adminlog_delete_success']);
    } else {
        printf('<p>%s</p>', $MPF_LANG['ad_adminlog_delete_failure']);
    }

} else {
	print $PMF_LANG["err_NotAuth"];
}
