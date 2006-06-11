<?php
/**
* $Id: stat.ratings.php,v 1.10 2006-06-11 15:26:21 matteo Exp $
*
* The page with the ratings of the votings
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
	$tree = new PMF_Category();
?>
	<h2><?php print $PMF_LANG["ad_rs"] ?></h2>
    <table class="list">
<?php
	$result = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, ('.SQLPREFIX.'faqvoting.vote / '.SQLPREFIX.'faqvoting.usr) AS num, '.SQLPREFIX.'faqvoting.usr FROM '.SQLPREFIX.'faqvoting, '.SQLPREFIX.'faqdata LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvoting.artikel GROUP BY '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqvoting.vote, '.SQLPREFIX.'faqvoting.usr ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id');
	$anz = $db->num_rows($result);
	$old = "";
	while ($row = $db->fetch_object($result)) {
		if ($row->category_id != $old) {
?>
    <tr>
        <td colspan="5" class="list"><strong><?php print $tree->categoryName[$row->category_id]["name"]; ?></strong></td>
    </tr>
<?php
		}
?>
    <tr>
        <td class="list"><?php print $row->id; ?></td>
        <td class="list"><?php print $row->lang; ?></td>
        <td class="list"><a href="../index.php?action=artikel&amp;cat=<?php print $row->category_id;?>&amp;id=<?php print $row->id;?>&amp;artlang=<?php print $row->lang; ?>"><?php print $row->thema; ?></a></td>
        <td class="list"><?php print $row->usr; ?></td>
        <td class="list" style="background-color: #d3d3d3;"><img src="stat.bar.php?num=<?php print $row->num; ?>" border="0" alt="<?php print round($row->num * 20); ?> %" width="50" height="15" title="<?php print round($row->num * 20); ?> %" /></td>
    </tr>
<?php
		$old = $row->category_id;
	}
	if ($anz > 0) {
?>
    <tr>
        <td colspan="5" class="list"><span style="color: green; font-weight: bold;"><?php print $PMF_LANG["ad_rs_green"] ?></span> <?php print $PMF_LANG["ad_rs_ahtf"] ?>, <span style="color: red; font-weight: bold;"><?php print $PMF_LANG["ad_rs_red"] ?></span> <?php print $PMF_LANG["ad_rs_altt"] ?></td>
    </tr>
<?php
	} else {
?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" class="list"><?php print $PMF_LANG["ad_rs_no"] ?></td>
        </tr>
    </tfoot>
<?php
	}
?>
	</table>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}
?>
