<?php
/**
 * Overview of actions in the admin section
 *
 * PHP Version 5.2
 *
 *  http://www.mozilla.org/MPL/
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$logging = new PMF_Logging();

if ($permission['adminlog'] && 'adminlog' == $action) {

    $perpage = 15;
    $pages   = PMF_Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page    = PMF_Filter::filterInput(INPUT_GET, 'page' , FILTER_VALIDATE_INT, 1);
    
    if (is_null($pages)) {
        $pages = round(($logging->getNumberOfEntries() + ($perpage / 3)) / $perpage, 0);
    }
    
    $start = ($page - 1) * $perpage;
    $ende  = $start + $perpage;

    $PageSpan = PageSpan("<a href=\"?action=adminlog&amp;pages=".$pages."&amp;page=<NUM>\">", 1, $pages, $page);

    $logging_data = $logging->getAll();
?>
    <h2><?php print $PMF_LANG["ad_adminlog"]; ?></h2>
    <table class="table table-striped">
    <thead>
        <tr>
            <th><?php print $PMF_LANG["ad_categ_id"]; ?></th>
            <th><?php print $PMF_LANG["ad_adminlog_date"]; ?></th>
            <th><?php print $PMF_LANG["ad_adminlog_user"]; ?></th>
            <th><?php print $PMF_LANG["ad_adminlog_ip"]; ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <td colspan="4"><?php print $PageSpan; ?></td>
        </tr>
    </tfoot>
    <tbody>
<?php
    $counter = $displayedCounter = 0;

    foreach ($logging_data as $logging_id => $logging_value) {

        if ($displayedCounter >= $perpage) {
            $displayedCounter++;
            continue;
        }

        $counter++;
        if ($counter <= $start) {
            continue;
        }
        $displayedCounter++;
        
        $user->getUserById($logging_value['usr']);
?>
        <tr class="cell">
            <td><?php print $logging_id; ?></td>
            <td><?php print PMF_Date::format(date('Y-m-d H:i', $logging_value['time'])); ?></td>
            <td><?php print $user->getLogin(); ?></td>
            <td><?php print $logging_value['ip']; ?></td>
        </tr>
        <tr class="cell">
            <td colspan="4"><?php
        $text = $logging_value['text'];
        $text = str_replace("Loginerror", $PMF_LANG["ad_log_lger"], $text);
        $text = str_replace("Session expired", $PMF_LANG["ad_log_sess"], $text);
        $text = str_replace("Useredit, ", $PMF_LANG["ad_log_edit"], $text);
        $text = str_replace("Beitragcreatesave", $PMF_LANG["ad_log_crsa"], $text);
        $text = str_replace("Beitragcreate", $PMF_LANG["ad_log_crea"], $text);
        $text = str_replace("Usersave, ", $PMF_LANG["ad_log_ussa"], $text);
        $text = str_replace("Userdel, ", $PMF_LANG["ad_log_usde"], $text);
        $text = str_replace("Beitragedit, ", $PMF_LANG["ad_log_beed"], $text);
        $text = str_replace("Beitragdel, ", $PMF_LANG["ad_log_bede"], $text);
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

} elseif ($permission['adminlog'] && 'deleteadminlog' == $action) {

    if ($logging->delete()) {
        printf('<p class="success">%s</p>', $PMF_LANG['ad_adminlog_delete_success']);
    } else {
        printf('<p class="error">%s</p>', $PMF_LANG['ad_adminlog_delete_failure']);
    }
} else {
    print $PMF_LANG["err_NotAuth"];
}
