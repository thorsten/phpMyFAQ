<?php
/**
* $Id: record.delquestion.php,v 1.4 2005-11-23 16:37:50 b33blebr0x Exp $
*
* Delete open questions
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-24
* @copyright    (c) 2001-2004 phpMyFAQ
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

if ($permission["delquestion"]) {
    $tree = new Category;
    if (isset($_REQUEST["delete"]) && $_REQUEST["delete"] == "yes") {
		$db->query("DELETE FROM ".SQLPREFIX."faqfragen WHERE id = ".$_REQUEST["id"]);
		print $PMF_LANG["ad_entry_delsuc"];
	} else {
		print "<h2>".$PMF_LANG["msgOpenQuestions"]."</h2>";
		$result = $db->query("SELECT id, ask_username, ask_usermail, ask_rubrik, ask_content, ask_date FROM ".SQLPREFIX."faqfragen ORDER BY ask_date ASC");
		if ($db->num_rows($result) > 0) {
?>
    <table class="list">
    <thead>
        <tr>
            <th class="list"><?php print $PMF_LANG["ad_entry_author"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_entry_theme"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_gen_delete"]; ?>?</th>
        </tr>
    </thead>
    <tbody>
<?php
			while ($row = $db->fetch_object($result)) {
?>
        <tr>
            <td class="list"><?php print makeDate($row->ask_date); ?><br /><a href="mailto:<?php print $row->ask_usermail; ?>"><?php print $row->ask_username; ?></a></td>
            <td class="list"><?php print $tree->categoryName[$row->ask_rubrik]["name"].":<br />".$row->ask_content; ?></td>
            <td class="list"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=question&amp;id=<?php print $row->id; ?>&amp;delete=yes"><?php print $PMF_LANG["ad_gen_delete"]; ?>!</a><br /><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=takequestion&amp;id=<?php print $row->id; ?>"><?php print $PMF_LANG["ad_ques_take"] ?></a></td>
        </tr>
<?php
			}
?>
    </tbody>
	</table>
<?php
		} else {
			print $PMF_LANG["msgNoQuestionsAvailable"];
		}
	}
} else {
    print $PMF_LANG["err_NotAuth"];
}
?>
