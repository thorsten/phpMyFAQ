<?php
/******************************************************************************
 * File:				record.add.php
 * Description:			add a record
 * Authors:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-23
 * Last change:			2004-11-01
 * Copyright:           (c) 2001-2004 Thorsten Rinne
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
 ******************************************************************************/
if ($permission["editbt"]) {
	$submit = $_REQUEST["submit"];
	
	if (isset($submit[1]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
		// new entry
		adminlog("Beitragcreatesave");
		$thema = addslashes($_REQUEST["thema"]);
		$content = addslashes($_REQUEST["content"]);
		$keywords = addslashes($_REQUEST["keywords"]);
		$author = addslashes($_REQUEST["author"]);
        if (isset($_REQUEST["comment"])) {
            $comment = $_REQUEST["comment"];
            }
        else {
			$comment = "n";
			}
		$datum = date("YmdHis");
		
		if ($db->query("INSERT INTO ".SQLPREFIX."faqdata (lang, thema, content, keywords, author, rubrik, active, datum, email, comment) VALUES ('".$_REQUEST["language"]."', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$_REQUEST["rubrik"]."', '".$_REQUEST["active"]."', '".$datum."', '".$_REQUEST["email"]."', '".$comment."')")) {
			if ($db->query("INSERT INTO ".SQLPREFIX."faqvisits (id, lang, visits, last_visit) VALUES ('".$db->insert_id(SQLPREFIX."faqdata", "id")."', '".$_REQUEST["language"]."', '1', '".time()."')")) {
				print $PMF_LANG["ad_entryins_suc"];
				}
			else {
				print $PMF_LANG["ad_entryins_fail"];
				}
			}
		else {
			print $PMF_LANG["ad_entryins_fail"];
			}
		}
	
	if (isset($submit[2]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
		// Preview
		$rubrik = $_REQUEST["rubrik"];
        $cat = new Category;
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $id = $_REQUEST["id"];
            }
        else {
            $id = "";
            }
?>
	<h2><?php print $PMF_LANG["ad_entry_preview"]; ?></h2>
	<p><strong><?php print $cat->categoryName[$rubrik]["name"]; ?>:</strong> <?php print stripslashes($_REQUEST["thema"]); ?></p>
<?php
        $content = preg_replace_callback("{(<pre>.*</pre>)}siU", "pre_core", $_REQUEST["content"]);
        $content = preg_replace_callback("{(<pre+.*</pre>)}siU", "pre_core", $content);
        print stripslashes($content);
?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br /><?php print $PMF_LANG["msgAuthor"].$_REQUEST["author"]; ?></p>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=editpreview" method="post">
    <input type="hidden" name="id" value="<?php print $id; ?>" />
    <input type="hidden" name="thema" value="<?php print htmlspecialchars($_REQUEST["thema"]); ?>" />
    <input type="hidden" name="content" value="<?php print htmlspecialchars($_REQUEST["content"]); ?>" />
    <input type="hidden" name="lang" value="<?php print $_REQUEST["language"]; ?>" />
    <input type="hidden" name="keywords" value="<?php print $_REQUEST["keywords"]; ?>" />
    <input type="hidden" name="author" value="<?php print $_REQUEST["author"]; ?>" />
    <input type="hidden" name="email" value="<?php print $_REQUEST["email"]; ?>" />
    <input type="hidden" name="rubrik" value="<?php print $_REQUEST["rubrik"]; ?>" />
    <input type="hidden" name="active" value="<?php print $_REQUEST["active"]; ?>" />
    <input type="hidden" name="changed" value="<?php print $_REQUEST["changed"]; ?>" />
    <input type="hidden" name="comment" value="<?php print $_REQUEST["comment"]; ?>" />
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
		}
	elseif (!isset($_REQUEST["thema"]) || $_REQUEST["thema"] == "") {
		print "<p>".$PMF_LANG["ad_entryins_fail"]."</p>";
		print "<p><a href=\"javascript:history.back();\">".$PMF_LANG["ad_entry_back"]."</a></p>";
		}
	}
else {
	print $PMF_LANG["err_NotAuth"];
	}
?>
