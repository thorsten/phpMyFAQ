<?php
/**
* $Id: record.edit.php,v 1.7 2004-12-11 21:54:12 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @license      Mozilla Public License 1.1
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

if ($permission["editbt"] && emptyTable(SQLPREFIX."faqcategory")) {
	$tree = new Category();
    $tree->buildTree();
    $rubrik = "";
    $thema = "";
    
    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "takequestion") {
    
		list($rubrik, $thema) = $db->fetch_row($db->query("SELECT ask_rubrik, ask_content FROM ".SQLPREFIX."faqfragen WHERE id = '".$_REQUEST["id"]."'"));
		$lang = trim(strtolower(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)));
        
    }
    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "editpreview") {
        
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $id = $_REQUEST["id"];
            $acti = "saveentry&amp;id=".$id;
        } else {
            $acti = "insertentry";
			$id = "";
        }
        
        $lang = $_REQUEST["lang"];
        $active = $_REQUEST["active"];
        $rubrik = $_REQUEST["rubrik"];
        $keywords = $_REQUEST["keywords"];
        $thema = stripslashes($_REQUEST["thema"]);
        $content = stripslashes(htmlspecialchars($_REQUEST["content"]));
        $author = $_REQUEST["author"];
        $email = $_REQUEST["email"];
        $comment = $_REQUEST["comment"];
        $changed = $_REQUEST["changed"];
        
    } elseif (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "editentry") {
        
		if ((!isset($rubrik) && !isset($thema)) || (isset($_REQUEST["id"]) && $_REQUEST["id"] != "")) {
		    adminlog("Beitragedit, ".$_REQUEST["id"]);
            // Get the category
            $resultCategory = $db->query('');
            while ($row = $db->fetch_object($resultCategory)) {
                $categories[] = array('category_id' => $row->category_id, 'category_lang' => $row->category_lang);
            }
            // Get the record
			$resultRecord = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqdata.keywords, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.email, '.SQLPREFIX.'faqdata.comment, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata WHERE id = '.$_REQUEST["id"].' AND lang = "'.$_GET["lang"]);
			list($id, $lang, $active, $keywords, $thema, $content, $author, $email, $comment, $date) = $db->fetch_row($resultRecord);
            $content = htmlspecialchars($content);
			$acti = "saveentry&amp;id=".$_REQUEST["id"];
			$id = $_REQUEST["id"];
		} else {
			$acti = "insertentry";
			$id = "";
		    $lang = $LANGCODE;
		}
        
	} else {
        
		adminlog("Beitragcreate");
		$acti = "insertentry";
        $rubrik = "";
		$id = "";
		$lang = $LANGCODE;
        
	}
?>
    <h2><?php print $PMF_LANG["ad_entry_edit_1"]; ?> <span style="color: Red;"><?php print $id; ?></span> <?php print $PMF_LANG["ad_entry_edit_2"]; ?></h2>
    
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=<?php print $acti; ?>" method="post">
    <dl>

    <dt><strong><?php print $PMF_LANG["ad_entry_category"]; ?></strong></dt>
    <dd><select name="rubrik[]" size="3" multiple="multiple">
<?php print $tree->printCategoryOptions($categories['category_id']); ?>
    </select></dd>
    
    <dt><strong><?php print $PMF_LANG["ad_entry_theme"]; ?></strong></dt>
    <dd><textarea class="admin" name="thema" style="width: 525px; height: 50px;" cols="2" rows="50"><?php if (isset($thema)) { print stripslashes($thema); } ?></textarea></dd>
	
    <dt><strong><?php print $PMF_LANG["ad_entry_content"]; ?></strong></dt>
    <dd><noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea class="admin" id="content" name="content" cols="50" rows="10"><?php if (isset($content)) { print stripslashes($content); } ?></textarea></dd>

<?php
    if ($permission["addatt"]) {
?>

    <dt><strong><?php print $PMF_LANG["ad_att_att"]; ?></strong></dt>
    <dd>
<?php
	    if (isset($id) && $id != "") {
?>
        <strong><?php print "../attachments/".$id."/" ?></strong><br />
<?php
    		if (@is_dir(PMF_ROOT_DIR."/attachments/".$id."/")) {
    			$do = dir(PMF_ROOT_DIR."/attachments/".$id."/");
    			while ($dat = $do->read()) {
    				if ($dat != "." && $dat != "..") {
    					print "<a href=\""."../attachments/".$id."/".$dat."\">".$dat."</a> ";
                        if ($permission["delatt"]) {
                            print "[ <a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=delatt&amp;id=".$id."&amp;which=".rawurlencode($dat)."&amp;lang=".$lang."\">".$PMF_LANG["ad_att_del"]."</a> ]";
                        }
                        print "<br />\n";
    				}
    			}
    		} else {
    			print "<em>".$PMF_LANG["ad_att_none"]."</em><br />";
    		}
    		print "<a href=\"javascript:Picture('attachment.php?uin=".$uin."&amp;id=".$id."&amp;rubrik=".$rubrik."', 'Attachment', 400,80)\">".$PMF_LANG["ad_att_add"]."</a>";
    	} else {
    		print $PMF_LANG["ad_att_nope"];
    	}
?></dd>

<?php
    }
?>

    <dt><strong><?php print $PMF_LANG["ad_entry_locale"]; ?>:</strong></dt>
    <dd><select name="language">
    <?php print languageOptions($lang); ?>
	</select></dd>
    
	<dt><strong><?php print $PMF_LANG["ad_entry_keywords"]; ?></strong></dt>
    <dd><input class="admin" name="keywords" style="width: 525px;" value="<?php if (isset($keywords)) { print htmlspecialchars(stripslashes($keywords), ENT_QUOTES); } ?>" /></dd>

	<dt><strong><?php print $PMF_LANG["ad_entry_author"]; ?></strong></dt>
    <dd><input class="admin" name="author" style="width: 525px;" value="<?php if (isset($author)) { print $author; } else { print $auth_realname; } ?>" /></dd>

    <dt><strong><?php print $PMF_LANG["ad_entry_email"]; ?></strong></dt>
    <dd><input class="admin" name="email" style="width: 525px;" value="<?php if (isset($email)) { print $email; } else { print $auth_email; } ?>" /></dd>
	
<?php
	if (isset($active) && $active == "yes") {
		$suf = " checked=\"checked\"";
		unset($sul);
	} else {
		unset($suf);
		$sul = " checked=\"checked\"";
	}
?>
    <dt><strong><?php print $PMF_LANG["ad_entry_active"]; ?></strong></dt>
    <dd><input type="radio" name="active" value="yes"<?php if (isset($suf)) { print $suf; } ?> /> <?php print $PMF_LANG["ad_gen_yes"]; ?> <input type="radio" name="active" value="no"<?php if (isset($sul)) { print $sul; } ?> /> <?php print $PMF_LANG["ad_gen_no"]; ?></dd>
	
    <dt><strong>&nbsp;</strong></dt>
    <dd><input type="checkbox" name="comment" value="y"<?php if (isset($comment) && $comment == "y") { print " checked"; } ?> /> <?php print $PMF_LANG["ad_entry_allowComments"]; ?></dd>
	
    <dt><strong><?php print $PMF_LANG["ad_entry_date"]; ?></strong></dt>
    <dd><?php if (isset($date)) { print makeDate($date); } else { print makeDate(date("YmdHis")); } ?></dd>

    <dt><strong><?php print $PMF_LANG["ad_entry_changed"]; ?></strong></dt>
	<dd><textarea class="admin" name="changed" style="width: 525px; height: 50px;" cols="2" rows="50"><?php if (isset($changed)) { print $changed; } ?></textarea></dd>
        
    <dt><strong>&nbsp;</strong></dt>
    <dd><input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_save"]; ?>" name="submit[1]" />
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_preview"]; ?>" name="submit[2]" />
    <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_gen_reset"]; ?>" />

<?php
	if ($acti != "insertentry") {
?>
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_delete"]; ?>" name="submit[0]" />
<?php
	}
?></dd>
	</dl>
<?php
	if ($id) {
?>
    <h3><?php print $PMF_LANG["ad_entry_changelog"]; ?></h3>
<?php
		$result = $db->query("SELECT usr, datum, what FROM ".SQLPREFIX."faqchanges WHERE beitrag = '".$id."' ORDER BY id DESC");
		while (list($usr,$dat,$wht) = $db->fetch_row($result)) {
			list($usr) = $db->fetch_row($db->query("SELECT NAME FROM ".SQLPREFIX."faquser WHERE ID='".$usr."'"));
?>
    <div style="font-size: 10px;"><strong><?php print date("Y-m-d H:i:s",$dat).": ".$usr; ?></strong><br /><?php print $wht; ?></div>	
<?php
		}
	}
?>
    </form>
<?php
	$result = $db->query("SELECT id, id_comment, usr, email, comment, datum FROM ".SQLPREFIX."faqcomments WHERE id ='".$id."' ORDER BY datum DESC");
	if ($db->num_rows($result) > 0) {
?>
    <p><strong><?php print $PMF_LANG["ad_entry_comment"] ?></strong></p>
<?php
		while(list($id,$cm_id,$usr,$eml,$cmt,$dt) = $db->fetch_row($result)) {
?>	
    <p><?php print $PMF_LANG["ad_entry_commentby"] ?> <a href="mailto:<?php print $eml; ?>"><?php print $usr; ?></a>:<br /><?php print $cmt; ?><br /><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=delcomment&amp;artid=<?php print $id; ?>&amp;cmtid=<?php print $cm_id; ?>&amp;lang=<?php print $lang; ?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></p>
<?php
		}
	}
} elseif ($permission["editbt"] != 1 && emptyTable(SQLPREFIX."faqcategory")) {
    print $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && !emptyTable(SQLPREFIX."faqcategory")) {
    print $PMF_LANG["no_cats"];
}
?>
