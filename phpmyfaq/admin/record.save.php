<?php
/**
* $Id: record.save.php,v 1.21 2005-03-19 14:01:34 thorstenr Exp $
*
* Save or update a FAQ record
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2005 phpMyFAQ Team
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

$submit = $_REQUEST["submit"];

if (isset($submit[2]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "" && isset($_REQUEST['rubrik']) && is_array($_REQUEST['rubrik'])) {
	// Preview
	$rubrik = $_REQUEST["rubrik"];
    $cat = new Category;
    $cat->transform(0);
    $categorylist = '';
    foreach ($rubrik as $categories) {
        $categorylist .= $cat->getPath($categories).'<br />';
    }
?>
	<h2><?php print $PMF_LANG["ad_entry_preview"]; ?></h2>
    
	<h3><strong><em><?php print $categorylist; ?></em>
    <?php print stripslashes($_REQUEST["thema"]); ?></strong></h3>
    <?php print stripslashes($_REQUEST["content"]); ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].$_REQUEST["author"]; ?></p>

    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=editpreview" method="post">
    <input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>" />
    <input type="hidden" name="thema" value="<?php print htmlspecialchars($_REQUEST["thema"]); ?>" />
    <input type="hidden" name="content" value="<?php print htmlspecialchars($_REQUEST["content"]); ?>" />
    <input type="hidden" name="lang" value="<?php print $_REQUEST["language"]; ?>" />
    <input type="hidden" name="keywords" value="<?php print $_REQUEST["keywords"]; ?>" />
    <input type="hidden" name="author" value="<?php print $_REQUEST["author"]; ?>" />
    <input type="hidden" name="email" value="<?php print $_REQUEST["email"]; ?>" />
<?php
    foreach ($rubrik as $key => $categories) {
        print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
    }
?>
    <input type="hidden" name="active" value="<?php print $_REQUEST["active"]; ?>" />
    <input type="hidden" name="changed" value="<?php print $_REQUEST["changed"]; ?>" />
    <input type="hidden" name="comment" value="<?php print $_REQUEST["comment"]; ?>" />
    <p align="center"><input type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
}

if (isset($submit[1]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
	// Wenn auf Speichern geklickt wurde...
	adminlog("Beitragsave", $_REQUEST["id"]);
    print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
	$db->query("INSERT INTO ".SQLPREFIX."faqchanges (id, beitrag, usr, datum, what) VALUES (".$db->nextID(SQLPREFIX."faqchanges", "id").", ".$_REQUEST["id"].",'".$auth_user."','".time()."','".nl2br(addslashes($_REQUEST["changed"]))."')");
	$thema = $db->escape_string($_REQUEST["thema"]);
	$content = $db->escape_string($_REQUEST["content"]);
	$keywords = $db->escape_string($_REQUEST["keywords"]);
	$author = $db->escape_string($_REQUEST["author"]);
    
    if (isset($_REQUEST["comment"]) && $_REQUEST["comment"] != "") {
        $comment = $_REQUEST["comment"];
    } else {
        $comment = "n";
    }
	
    $datum = date("YmdHis");
    $rubrik = $_REQUEST["rubrik"];
	
	$result = $db->query("SELECT id, lang FROM ".SQLPREFIX."faqdata WHERE id = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
	$num = $db->num_rows($result);
	
    // save or update the FAQ record
	if ($num == "1") {
		$query = "UPDATE ".SQLPREFIX."faqdata SET thema = '".$thema."', content = '".$content."', keywords = '".$keywords."', author = '".$author."', active = '".$_REQUEST["active"]."', datum = '".$datum."', email = '".$db->escape_string($_REQUEST["email"])."', comment = '".$comment."' WHERE id = ".$_REQUEST["id"]." AND lang = '".$_REQUEST["language"]."'";
    } else {
		$query = "INSERT INTO ".SQLPREFIX."faqdata (id, lang, thema, content, keywords, author, active, datum, email, comment) VALUES (".$_REQUEST["id"].", '".$_REQUEST["language"]."', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$_REQUEST["active"]."', '".$datum."', '".$db->escape_string($_REQUEST["email"])."', '".$comment."')";
    }
    
	if ($db->query($query)) {
		print $PMF_LANG["ad_entry_savedsuc"];
    } else {
		print $PMF_LANG["ad_entry_savedfail"].$db->error();
    }
    
    // delete category relations
    $db->query('DELETE FROM '.SQLPREFIX.'faqcategoryrelations WHERE record_id = '.$_REQUEST["id"].' and record_lang = "'.$_REQUEST["language"].'"');
	// save or update the category relations
    foreach ($rubrik as $categories) {
        $db->query('INSERT INTO '.SQLPREFIX.'faqcategoryrelations VALUES ('.$categories.', "'.$_REQUEST["language"].'", '.$_REQUEST["id"].', "'.$_REQUEST["language"].'")');
        }
    }
}

if (isset($submit[0])) {
	if ($permission["delbt"])	{
        if (isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
            $thema = "<strong>".$_REQUEST["thema"]."</strong>";
        } else {
            $thema = "";
        }
        if (isset($_REQUEST["author"]) && $_REQUEST["author"] != "") {
            $author = $PMF_LANG["ad_entry_del_2"]."<strong>".$_REQUEST["author"]."</strong>";
        } else {
            $author = "";
        }
?>
	<p align="center"><?php print $PMF_LANG["ad_entry_del_1"]." ".$thema." ".$author." ".$PMF_LANG["ad_entry_del_3"]; ?></p>
	<div align="center">
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="POST">
    <input type="hidden" name="aktion" value="delentry">
    <input type="hidden" name="referer" value="<?php print $_SERVER["HTTP_REFERER"]; ?>">
    <input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>">
    <input type="hidden" name="language" value="<?php print $_REQUEST["language"]; ?>">
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_yes"] ?>" name="subm">
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_no"] ?>" name="subm">
    </form>
    </div>
<?php
    } else {
		print $PMF_LANG["err_NotAuth"];
    }
} elseif (!isset($_REQUEST["thema"]) || $_REQUEST["thema"] == "") {
	print "<p>".$PMF_LANG["ad_entryins_fail"]."</p>";
	print "<p><a href=\"javascript:history.back();\">".$PMF_LANG["ad_entry_back"]."</a></p>";
}
?>
=======
<?php
/**
* $Id: record.save.php,v 1.21 2005-03-19 14:01:34 thorstenr Exp $
*
* Save or update a FAQ record
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @copyright    (c) 2001-2005 phpMyFAQ Team
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

$submit = $_REQUEST["submit"];

if (isset($submit[2]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
	// Preview
	$rubrik = $_REQUEST["rubrik"];
    $cat = new Category;
    $cat->transform(0);
    $categorylist = '';
    foreach ($rubrik as $categories) {
        $categorylist .= $cat->getPath($categories).'<br />';
    }
?>
	<h2><?php print $PMF_LANG["ad_entry_preview"]; ?></h2>
    
	<h3><strong><em><?php print $categorylist; ?></em>
    <?php print stripslashes($_REQUEST["thema"]); ?></strong></h3>
    <?php print stripslashes($_REQUEST["content"]); ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].$_REQUEST["author"]; ?></p>

    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=editpreview" method="post">
    <input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>" />
    <input type="hidden" name="thema" value="<?php print htmlspecialchars($_REQUEST["thema"]); ?>" />
    <input type="hidden" name="content" value="<?php print htmlspecialchars($_REQUEST["content"]); ?>" />
    <input type="hidden" name="lang" value="<?php print $_REQUEST["language"]; ?>" />
    <input type="hidden" name="keywords" value="<?php print $_REQUEST["keywords"]; ?>" />
    <input type="hidden" name="author" value="<?php print $_REQUEST["author"]; ?>" />
    <input type="hidden" name="email" value="<?php print $_REQUEST["email"]; ?>" />
<?php
    foreach ($rubrik as $key => $categories) {
        print '    <input type="hidden" name="rubrik['.$key.']" value="'.$categories.'" />';
    }
?>
    <input type="hidden" name="active" value="<?php print $_REQUEST["active"]; ?>" />
    <input type="hidden" name="changed" value="<?php print $_REQUEST["changed"]; ?>" />
    <input type="hidden" name="comment" value="<?php print $_REQUEST["comment"]; ?>" />
    <p align="center"><input type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
}

if (isset($submit[1]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
	// Wenn auf Speichern geklickt wurde...
	adminlog("Beitragsave", $_REQUEST["id"]);
    print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
	$db->query("INSERT INTO ".SQLPREFIX."faqchanges (id, beitrag, lang, usr, datum, what) VALUES (".$db->nextID(SQLPREFIX."faqchanges", "id").", ".$_REQUEST["id"].", '".$_REQUEST["language"]."', '".$auth_user."', '".time()."', '".nl2br(addslashes($_REQUEST["changed"]))."')");
	$thema = $db->escape_string($_REQUEST["thema"]);
	$content = $db->escape_string($_REQUEST["content"]);
	$keywords = $db->escape_string($_REQUEST["keywords"]);
	$author = $db->escape_string($_REQUEST["author"]);
    
    if (isset($_REQUEST["comment"]) && $_REQUEST["comment"] != "") {
        $comment = $_REQUEST["comment"];
    } else {
        $comment = "n";
    }
	
    $datum = date("YmdHis");
    $rubrik = $_REQUEST["rubrik"];
	
	$result = $db->query("SELECT id, lang FROM ".SQLPREFIX."faqdata WHERE id = '".$_REQUEST["id"]."' AND lang = '".$_REQUEST["language"]."'");
	$num = $db->num_rows($result);
	
    // save or update the FAQ record
	if ($num == "1") {
		$query = "UPDATE ".SQLPREFIX."faqdata SET thema = '".$thema."', content = '".$content."', keywords = '".$keywords."', author = '".$author."', active = '".$_REQUEST["active"]."', datum = '".$datum."', email = '".$db->escape_string($_REQUEST["email"])."', comment = '".$comment."' WHERE id = ".$_REQUEST["id"]." AND lang = '".$_REQUEST["language"]."'";
    } else {
		$query = "INSERT INTO ".SQLPREFIX."faqdata (id, lang, thema, content, keywords, author, active, datum, email, comment) VALUES (".$_REQUEST["id"].", '".$_REQUEST["language"]."', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$_REQUEST["active"]."', '".$datum."', '".$db->escape_string($_REQUEST["email"])."', '".$comment."')";
    }
    
	if ($db->query($query)) {
		print $PMF_LANG["ad_entry_savedsuc"];
    } else {
		print $PMF_LANG["ad_entry_savedfail"].$db->error();
    }
    
	// delete all the category relations and then insert the new ones
    $db->query('DELETE FROM '.SQLPREFIX.'faqcategoryrelations WHERE record_id = '.$_REQUEST["id"].' AND record_lang = "'.$_REQUEST["language"].'"');
    foreach ($rubrik as $categories) {
        $db->query('INSERT INTO '.SQLPREFIX.'faqcategoryrelations VALUES ('.$categories.', "'.$_REQUEST["language"].'", '.$_REQUEST["id"].', "'.$_REQUEST["language"].'")');
    }
}

if (isset($submit[0])) {
	if ($permission["delbt"])	{
        if (isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
            $thema = "<strong>".$_REQUEST["thema"]."</strong>";
        } else {
            $thema = "";
        }
        if (isset($_REQUEST["author"]) && $_REQUEST["author"] != "") {
            $author = $PMF_LANG["ad_entry_del_2"]."<strong>".$_REQUEST["author"]."</strong>";
        } else {
            $author = "";
        }
?>
	<p align="center"><?php print $PMF_LANG["ad_entry_del_1"]." ".$thema." ".$author." ".$PMF_LANG["ad_entry_del_3"]; ?></p>
	<div align="center">
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="POST">
    <input type="hidden" name="aktion" value="delentry">
    <input type="hidden" name="referer" value="<?php print $_SERVER["HTTP_REFERER"]; ?>">
    <input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>">
    <input type="hidden" name="language" value="<?php print $_REQUEST["language"]; ?>">
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_yes"] ?>" name="subm">
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_gen_no"] ?>" name="subm">
    </form>
    </div>
<?php
    } else {
		print $PMF_LANG["err_NotAuth"];
    }
} elseif (!isset($_REQUEST["thema"]) || $_REQUEST["thema"] == "") {
	print "<p>".$PMF_LANG["ad_entryins_fail"]."</p>";
	print "<p><a href=\"javascript:history.back();\">".$PMF_LANG["ad_entry_back"]."</a></p>";
}
?>