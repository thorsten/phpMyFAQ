<?php
/**
* $Id: adminlog.php,v 1.15 2006-11-10 15:47:47 matteo Exp $
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
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

require_once(PMF_ROOT_DIR.'/inc/Logging.php');

$logging = new PMF_Logging($db);

if ($permission['adminlog'] && 'adminlog' == $_action) {

    //
    // Show the adminlog
    //

    $perpage = 15;

    if (!isset($_REQUEST["pages"])) {
        $pages = round(( $logging->getNumberOfEntries() + ($perpage / 3)) / $perpage, 0);
    } else {
        $pages = $_REQUEST["pages"];
    }

    if (!isset($_REQUEST["page"])) {
        $page = 1;
    } else {
        $page = $_REQUEST["page"];
    }

    $start = ($page - 1) * $perpage;
    $ende = $start + $perpage;

    $PageSpan = PageSpan("<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;action=adminlog&amp;pages=".$pages."&amp;page=<NUM>\">", 1, $pages, $page);

    $logging_data = $logging->getAll();
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

    foreach ($logging_data as $logging_id => $logging_value) {

        if ($displayedCounter > $perpage) {
            $displayedCounter++;
            continue;
        }

        $counter++;
        if ($counter <= $start) {
            continue;
        }
        $displayedCounter++;
?>
        <tr class="cell">
            <td class="list"><?php print $logging_id; ?></td>
            <td class="list"><?php print date("Y-m-d H:i:s", $logging_value['time']); ?></td>
            <td class="list"><?php print isset($_user[$logging_value['usr']]) ? $_user[$logging_value['usr']] : '&nbsp;'; ?></td>
            <td class="list"><?php print $logging_value['ip']; ?></td>
        </tr>
        <tr class="cell">
            <td colspan="4" class="list"><?php
        $text = str_replace("Loginerror", $PMF_LANG["ad_log_lger"], $logging_value['text']);
        $text = str_replace("Session expired", $PMF_LANG["ad_log_sess"], $logging_value['text']);
        $text = str_replace("Useredit, ", $PMF_LANG["ad_log_edit"], $logging_value['text']);
        $text = str_replace("Beitragcreatesave", $PMF_LANG["ad_log_crsa"], $logging_value['text']);
        $text = str_replace("Beitragcreate", $PMF_LANG["ad_log_crea"], $logging_value['text']);
        $text = str_replace("Usersave, ", $PMF_LANG["ad_log_ussa"], $logging_value['text']);
        $text = str_replace("Userdel, ", $PMF_LANG["ad_log_usde"], $logging_value['text']);
        $text = str_replace("Beitragedit, ", $PMF_LANG["ad_log_beed"], $logging_value['text']);
        $text = str_replace("Beitragdel, ", $PMF_LANG["ad_log_bede"], $logging_value['text']);
        print $text;
?></td>
        </tr>
<?php
    }
?>
    </tbody>
    </table>
<?php

    printf ('<p><a href="?action=deleteadminlog">%s</a></p>', $PMF_LANG['ad_adminlog_del_older_30d']);

} elseif ($permission['adminlog'] && 'deleteadminlog' == $_action) {

    //
    // Delete logs older than 30 days
    //
    
    if ($logging->delete()) {
        printf('<p>%s</p>', $PMF_LANG['ad_adminlog_delete_success']);
    } else {
        printf('<p>%s</p>', $PMF_LANG['ad_adminlog_delete_failure']);
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
