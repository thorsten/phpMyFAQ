<?php
/**
* $Id: stat.ratings.php,v 1.2 2004-11-30 20:02:42 thorstenr Exp $
*
* The page with the ratings of the votings
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright    (c) 2001-2004 phpMyFAQ Team
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
	<h2><?php print $PMF_LANG["ad_rs"] ?></h2>
    <table class="list">
<?php
	$result = $db->query('SELECT DISTINCT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, ( '.SQLPREFIX.'faqvoting.vote / '.SQLPREFIX.'faqvoting.usr ) AS num, '.SQLPREFIX.'faqvoting.usr FROM '.SQLPREFIX.'faqdata, '.SQLPREFIX.'faqvoting LEFT JOIN '.SQLPREFIX.'faqcategoryrelations ON '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqcategoryrelations.record_id AND '.SQLPREFIX.'faqdata.lang = '.SQLPREFIX.'faqcategoryrelations.record_lang WHERE '.SQLPREFIX.'faqdata.id = '.SQLPREFIX.'faqvoting.artikel GROUP BY '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqcategoryrelations.category_id, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqvoting.vote, '.SQLPREFIX.'faqvoting.usr ORDER BY '.SQLPREFIX.'faqcategoryrelations.category_id');
	$anz = $db->num_rows($result);
	$old = "";
	while (list($id, $lang, $active, $rubrik, $thema, $num, $user) = $db->fetch_row($result)) {
		if ($rubrik != $old) {
?>
    <tr>
        <td colspan="5" class="list"><strong><?php print $tree->categoryName[$rubrik]["name"]; ?></strong></td>
    </tr>
<?php
			}
?>
    <tr>
        <td class="list"><?php print $id; ?></td>
        <td class="list"><?php print $lang; ?></td>
        <td class="list"><a href="../index.php?action=artikel&amp;cat=<?php print $rubrik;?>&amp;id=<?php print $id;?>&amp;artlang=<?php print $lang; ?>"><?php print stripslashes($thema); ?></a></td>
        <td class="list"><?php print $user; ?></td>
        <td class="list" style="background-color: #d3d3d3;"><img src="stat.bar.php?num=<?php print $num; ?>" border="0" alt="<?php print ceil(($num - 1) * 25); ?> %" width="50" height="15" title="<?php print ceil(($num - 1) * 25); ?> %" /></td>
    </tr>
<?php
		$old = $rubrik;
		}
	if ($anz > 0) {
?>
    <tr>
        <td colspan="5" class="list"><span style="color: green; font-weight: bold;"><?php print $PMF_LANG["ad_rs_green"] ?></span> <?php print $PMF_LANG["ad_rs_ahtf"] ?>, <span style="color: red; font-weight: bold;"><?php print $PMF_LANG["ad_rs_red"] ?></span> <?php print $PMF_LANG["ad_rs_altt"] ?></td>
    </tr>
<?php
		}
	else {
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
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
