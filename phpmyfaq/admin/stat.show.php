<?php
/**
* $Id: stat.show.php,v 1.8 2006-01-02 16:51:27 thorstenr Exp $
*
* Show the session
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
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

if ($permission["viewlog"]) {
	$tree = new Category();
?>
	<h2><?php print $PMF_LANG["ad_sess_session"]; ?> "<span style="color: Red;"><?php print $_REQUEST["id"]; ?></span>"</h2>
<?php
	$row = $db->fetch_object($db->query("SELECT time FROM ".SQLPREFIX."faqsessions WHERE sid = ".$_REQUEST["id"]));
    $time = $row->time;
	$fp = fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $time), "r");
?>
    <table class="list">
    <tbody>
<?php
		$anz = 0;
		while (list($sid, $lentry, $lcontent, $ip, $qstring, $referer, $useragent, $time) = fgetcsv($fp, 1024, ";")) {
			if ($sid == $_REQUEST["id"]) {
				$anz++;
?>
        <tr>
            <td class="list"><?php print date("Y-m-d H:i:s",$time); ?></td>
            <td class="list"><?php print $lentry; ?> (<?php print $lcontent; ?>)</td>
        </tr>
<?php
				if ($anz == 1) {
?>
        <tr>
            <td class="list"><?php print $PMF_LANG["ad_sess_referer"]; ?></td>
            <td class="list"><a href="<?php print $referer ?>" target="_blank"><?php print str_replace("?", "? ", $referer); ?></a></td>
        </tr>
        <tr>
            <td class="list"><?php print $PMF_LANG["ad_sess_browser"]; ?></td>
            <td class="list"><?php print $useragent; ?></td>
        </tr>
        <tr>
            <td class="list"><?php print $PMF_LANG["ad_sess_ip"]; ?>:</td>
            <td class="list"><?php print $ip; ?></td>
        </tr>
<?php
                }
            }
        }
?>
    </tbody>
    <tfoot>
	    <tr>
            <td colspan="2"><a href="javascript:history.back()"><?php print $PMF_LANG["ad_sess_back"]; ?></a></td>
        </tr>
    </tfoot>
    </table>
<?php
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>
