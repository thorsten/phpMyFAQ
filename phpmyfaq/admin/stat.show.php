<?php
/**
* $Id: stat.show.php,v 1.3 2004-12-13 20:26:43 thorstenr Exp $
*
* Show the session
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright    (c) 2001-2004 Thorsten Rinne
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

if ($permission["viewlog"]) {
	$tree = new Category();
?>
	<h2><?php print $PMF_LANG["ad_sess_session"]; ?> "<span style="color: Red;"><?php print $_REQUEST["id"]; ?></span>"</h2>
<?php
	list($time) = $db->fetch_row($db->query("SELECT TIME FROM ".SQLPREFIX."faqsessions WHERE sid = ".$_REQUEST["id"]));
	$fp = fopen(PMF_ROOT_DIR."/data/tracking".date("dmY", $time), "r");
?>
    <table class="list">
    <tbody>
<?php
		$anz = 0;
		while(list($sid, $lentry, $lcontent, $ip, $qstring, $referer, $useragent, $time) = fgetcsv($fp, 1024, ";")) {
			if ($sid == $_REQUEST["id"]) {
				$anz++;
?>
        <tr>
            <td class="list"><?php print date("Y-m-d H:i:s",$time); ?></td>
            <td class="list"><?php print $longn; ?> (<?php print $lentry ?>)</td>
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
				if (strtolower($para) == "rubrik") {
					$para = $PMF_LANG["ad_sess_ai_rubrik"];
					@$paraout = $tree->categoryName[$lcontent]["name"];
					$paraout = "<a href=\"index.php?action=anzeigen&amp;cat=".$para."\" target=\"_blank\">".$paraout."</a>";
					}
				if (strtolower($para) == "artikel") {
					$para = $PMF_LANG["ad_sess_ai_artikel"]; 
					list($rubrik, $thema) = $db->fetch_row($db->query("SELECT rubrik, thema FROM ".SQLPREFIX."faqdata WHERE id='".$lcontent."'"));
					$paraout = "<a href=\"index.php?action=anzeigen&amp;cat=".$rubrik."&id=".$lcontent."\" target=\"_blank\">".$thema."</a>";
					}
				if (strtolower($para)=="suchbegriffe") {
					$para = $PMF_LANG["ad_sess_ai_sb"]; 
					$paraout = $lcontent;
					}
				if (strtoloweR($para) == "session-id") {
					$para = $PMF_LANG["ad_sess_ai_sid"]; 
					$paraout = "<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=viewsession&amp;id=".$lcontent."\">".$lcontent."</a>";
					} 
				if (strlen($para) > 0 && strlen($paraout) > 0) {
?>
        <tr>
            <td class="list"><?php print $para; ?></td>
            <td class="list"><?php print $paraout; ?></td>
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
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
