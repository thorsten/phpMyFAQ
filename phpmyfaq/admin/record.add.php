<?php
/**
* $Id: record.add.php,v 1.6 2004-12-13 20:26:43 thorstenr Exp $
*
* Adds a record in the database
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
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
if ($permission["editbt"]) {
	$submit = $_REQUEST["submit"];
	
	if (isset($submit[1]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
		// new entry
		adminlog("Beitragcreatesave");
        $lang = $_REQUEST["language"];
		$thema = addslashes($_REQUEST["thema"]);
		$content = addslashes($_REQUEST["content"]);
		$keywords = addslashes($_REQUEST["keywords"]);
		$author = addslashes($_REQUEST["author"]);
        if (isset($_REQUEST["comment"])) {
            $comment = $_REQUEST["comment"];
        } else {
			$comment = "n";
	    }
		$datum = date("YmdHis");
        
        $category_query = '';
        $catgories = $_REQUEST['rubrik'];
        foreach ($catgories as $val) {
            $category_query .= 
        }
		
        $result_record = $db->query("INSERT INTO ".SQLPREFIX."faqdata (id, lang, thema, content, keywords, author, active, datum, email, comment) VALUES (".$db->nextID(SQLPREFIX."faqdata", "id").", '".$lang."', '".$thema."', '".$content."', '".$keywords."', '".$author."', '".$_REQUEST["active"]."', '".$datum."', '".$_REQUEST["email"]."', '".$comment."')");
        
        $result_categories = $db->query('INSERT INTO '.SQLPREFIX.'faqcategoryrelations (category_id, catgeory_lang, record_id, record_lang) VALUES ('', '', '.$db->insert_id(SQLPREFIX.'faqdata', 'id').', "'.$lang'"');
        
        $result_visits = $db->query("INSERT INTO ".SQLPREFIX."faqvisits (id, lang, visits, last_visit) VALUES (".$db->insert_id(SQLPREFIX."faqdata", "id") - 1.", '".$lang."', 1, ".time().")");
        
		if () {
			print $PMF_LANG["ad_entryins_suc"];
		}else {
			print $PMF_LANG["ad_entryins_fail"];
		}
	}
	
	if (isset($submit[2]) && isset($_REQUEST["thema"]) && $_REQUEST["thema"] != "") {
		// Preview
	    $rubrik = $_REQUEST["rubrik"];
        $cat = new Category;
        $cat->transform(0);
        $categorylist = '';
        foreach ($rubrik as $categories) {
            $categorylist .= $cat->getPath($categories).'<br />';
        }
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $id = $_REQUEST["id"];
        } else {
            $id = "";
        }
?>
	<h3><strong><em><?php print $categorylist; ?></em>
    <?php print stripslashes($_REQUEST["thema"]); ?></strong></h3>
    <?php print stripslashes($content); ?>
    <p class="little"><?php print $PMF_LANG["msgLastUpdateArticle"].makeDate(date("YmdHis")); ?><br />
    <?php print $PMF_LANG["msgAuthor"].$_REQUEST["author"]; ?></p>
    
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=editpreview" method="post">
    <input type="hidden" name="id" value="<?php print $id; ?>" />
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
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_entry_back"]; ?>" /></p>
    </form>
<?php
    } elseif (!isset($_REQUEST["thema"]) || $_REQUEST["thema"] == "") {
		print "<p>".$PMF_LANG["ad_entryins_fail"]."</p>";
		print "<p><a href=\"javascript:history.back();\">".$PMF_LANG["ad_entry_back"]."</a></p>";
	}
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>
