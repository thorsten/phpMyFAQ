<?php
/**
 * Sessionbrowser
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
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2009 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
if ($permission['viewlog']) {

	$perpage   = 50;
	$day       = PMF_Filter::filterInput(INPUT_POST, 'day', FILTER_VALIDATE_INT);
	$firstHour = mktime (0, 0, 0, date('m', $day), date('d', $day), date('Y', $day));
	$lastHour  = mktime (23, 59, 59, date('m', $day), date('d', $day), date('Y', $day));

	$session     = new PMF_Session();
    $sessiondata = $session->getSessionsbyDate($firstHour, $lastHour);
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
	foreach ($sessiondata as $sid => $data) {
?>
        <tr>
            <td class="list"><?php print $data['ip']; ?></td>
            <td class="list"><?php print date("Y-m-d H:i:s", $data['time']); ?></td>
            <td class="list"><a href="?action=viewsession&amp;id=<?php print $sid; ?>"><?php print $sid; ?></a></td>
	</tr>
<?php
    }
?>
    </tbody>
    </table>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}