<?php
/**
* $Id: record.edit.php,v 1.27 2006-01-03 11:17:33 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2003-02-23
* @license      Mozilla Public License 1.1
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

if ($permission["editbt"] && !emptyTable(SQLPREFIX."faqcategories")) {
	$tree = new Category();
    $tree->buildTree();
    $rubrik = "";
    $thema = "";
    $categories = array('category_id', 'category_lang');
    
    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "takequestion") {
    
        $_result = $db->query("SELECT ask_rubrik, ask_content FROM ".SQLPREFIX."faqfragen WHERE id = ".$_REQUEST["id"]);
		$row = $db->fetch_object($_result);
        $rubrik = $row->ask_rubrik;
        $thema = $row->ask_content;
		$lang = trim(strtolower(substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2)));
        $categories = array(array('category_id' => $rubrik, 'category_lang' => $lang));
    }
    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "editpreview") {
        
        if (isset($_REQUEST["id"]) && $_REQUEST["id"] != "") {
            $id = $_REQUEST["id"];
            $acti = "saveentry&amp;id=".$id;
        } else {
			$id = 0;
            $acti = "insertentry";
        }
        $lang = $_REQUEST["lang"];
        $rubrik = isset($_POST['rubrik']) ? $_POST['rubrik'] : null;
        if (is_array($rubrik)) {
            foreach ($rubrik as $cats) {
                $categories[] = array('category_id' => $cats, 'category_lang' => $lang);
            }
        }
        $active = $_REQUEST["active"];
        $keywords = $_REQUEST["keywords"];
        $thema = $_REQUEST["thema"];
        $content = htmlspecialchars($_REQUEST["content"]);
        $author = $_REQUEST["author"];
        $email = $_REQUEST["email"];
        $comment = $_REQUEST["comment"];
        $changed = $_REQUEST["changed"];
        
    } elseif (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "editentry") {
        
		if ((!isset($rubrik) && !isset($thema)) || (isset($_REQUEST["id"]) && $_REQUEST["id"] != "")) {
		    adminlog("Beitragedit, ".$_REQUEST["id"]);
			$id = intval($_GET["id"]);
            $lang = $_GET["lang"];
            
            // Get the category
            $resultCategory = $db->query('SELECT category_id, category_lang FROM '.SQLPREFIX.'faqcategoryrelations WHERE record_id = '.$id.' AND record_lang = \''.$lang.'\'');
            while ($row = $db->fetch_object($resultCategory)) {
                $categories[] = array('category_id' => $row->category_id, 'category_lang' => $row->category_lang);
            }
            
            // Get the record
			$resultRecord = $db->query('SELECT '.SQLPREFIX.'faqdata.id, '.SQLPREFIX.'faqdata.lang, '.SQLPREFIX.'faqdata.active, '.SQLPREFIX.'faqdata.keywords, '.SQLPREFIX.'faqdata.thema, '.SQLPREFIX.'faqdata.content, '.SQLPREFIX.'faqdata.author, '.SQLPREFIX.'faqdata.email, '.SQLPREFIX.'faqdata.comment, '.SQLPREFIX.'faqdata.datum FROM '.SQLPREFIX.'faqdata WHERE id = '.$id.' AND lang = \''.$lang.'\'');
            
			$row = $db->fetch_object($resultRecord);
            $id = $row->id;
            $lang = $row->lang;
            $active = $row->active;
            $keywords = $row->keywords;
            $thema = $row->thema;
            $content = htmlspecialchars($row->content);
            $author = $row->author;
            $email = $row->email;
            $comment = $row->comment;
            $date = $row->datum;
			$acti = 'saveentry&amp;id='.$_REQUEST['id'];
		} else {
			$acti = 'insertentry';
			$id = 0;
		    $lang = $LANGCODE;
		}
        
	} else {
        
		adminlog('Beitragcreate');
		$acti = 'insertentry';
		if (!is_array($categories)) {
            $categories = array();
		}
		$id = 0;
		$lang = $LANGCODE;
        
	}
	
	print '<h2>'.$PMF_LANG["ad_entry_edit_1"];
	if (0 != $id) {
	    print ' <span style="color: Red;">'.$id.'</span> ';
	}
	print $PMF_LANG["ad_entry_edit_2"].'</h2>';
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=<?php print $acti; ?>" method="post">

    <label class="left" for="rubrik"><?php print $PMF_LANG["ad_entry_category"]; ?></label>
    <select class="admin" name="rubrik[]" id="rubrik" size="5" multiple="multiple">
<?php print $tree->printCategoryOptions($categories); ?>
    </select><br />
    
    <label class="left" for="thema"><?php print $PMF_LANG["ad_entry_theme"]; ?></label>
    <textarea class="admin" name="thema" id="thema" style="width: 565px; height: 50px;" cols="2" rows="50"><?php if (isset($thema)) { print $thema; } ?></textarea><br />
	
    <label class="left" for="content"><?php print $PMF_LANG["ad_entry_content"]; ?></label>
    <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea class="admin" id="content" name="content" cols="50" rows="10"><?php if (isset($content)) { print $content; } ?></textarea><br />

<?php
    if ($permission["addatt"]) {
?>
    <label class="left"><?php print $PMF_LANG["ad_att_att"]; ?></label>
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
    		print "<a href=\"#\" onclick=\"Picture('attachment.php?uin=".$uin."&amp;id=".$id."&amp;rubrik=".$rubrik."', 'Attachment', 400,80)\">".$PMF_LANG["ad_att_add"]."</a>";
    	} else {
    		print $PMF_LANG["ad_att_nope"];
    	}
?><br />

<?php
    }
?>

    <label class="left" for="language"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
    <?php print selectLanguages($lang); ?><br />
    
	<label class="left" for="keywords"><?php print $PMF_LANG["ad_entry_keywords"]; ?></label>
    <input class="admin" name="keywords" id="keywords" style="width: 565px;" value="<?php if (isset($keywords)) { print htmlspecialchars($keywords); } ?>" /><br />

	<label class="left" for="author"><?php print $PMF_LANG["ad_entry_author"]; ?></label>
    <input class="admin" name="author" id="author" style="width: 565px;" value="<?php if (isset($author)) { print htmlspecialchars($author); } else { print $user->getUserData('display_name'); } ?>" /><br />

    <label class="left" for="email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
    <input class="admin" name="email" id="email" style="width: 565px;" value="<?php if (isset($email)) { print htmlspecialchars($email); } else { print $user->getUserData('email'); } ?>" /><br />
	
<?php
	if (isset($active) && $active == "yes") {
		$suf = " checked=\"checked\"";
		unset($sul);
	} else {
		unset($suf);
		$sul = " checked=\"checked\"";
	}
?>
    <label class="left" for="active"><?php print $PMF_LANG["ad_entry_active"]; ?></label>
    <input type="radio" name="active" class="active" value="yes"<?php if (isset($suf)) { print $suf; } ?> /> <?php print $PMF_LANG["ad_gen_yes"]; ?> <input type="radio" name="active" class="active" value="no"<?php if (isset($sul)) { print $sul; } ?> /> <?php print $PMF_LANG["ad_gen_no"]; ?><br />
	
    <label class="left" for="comment"><?php print $PMF_LANG["ad_entry_allowComments"]; ?></label>
    <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($comment) && $comment == "y") { print " checked"; } ?> /><br />
	
    <label class="left"><?php print $PMF_LANG["ad_entry_date"]; ?></label>
    <?php if (isset($date)) { print makeDate($date); } else { print makeDate(date("YmdHis")); } ?><br />

    <label class="left" for="changed"><?php print $PMF_LANG["ad_entry_changed"]; ?></label>
	<textarea class="admin" name="changed" id="changed" style="width: 565px; height: 50px;" cols="2" rows="50"><?php if (isset($changed)) { print $changed; } ?></textarea><br />
        
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_save"]; ?>" name="submit[1]" />
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_preview"]; ?>" name="submit[2]" />
    <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_gen_reset"]; ?>" />

<?php
	if ($acti != "insertentry") {
?>
    <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_delete"]; ?>" name="submit[0]" />
<?php
	}
?><br />
<?php
	if (is_numeric($id)) {
?>
    <h3><?php print $PMF_LANG["ad_entry_changelog"]; ?></h3>
<?php
		$result = $db->query("SELECT usr, datum, what FROM ".SQLPREFIX."faqchanges WHERE beitrag = ".$id." ORDER BY id DESC");
		while ($row = $db->fetch_object($result)) {
?>
    <div style="font-size: 10px;"><strong><?php print date("Y-m-d H:i:s", $row->datum).": ".$row->usr; ?></strong><br /><?php print $row->what; ?></div>
<?php
		}
?>
    </form>
<?php
	$result = $db->query("SELECT id, id_comment, usr, email, comment, datum FROM ".SQLPREFIX."faqcomments WHERE id = ".$id." ORDER BY datum DESC");
		if ($db->num_rows($result) > 0) {
?>
    <p><strong><?php print $PMF_LANG["ad_entry_comment"] ?></strong></p>
<?php
            while ($row = $db->fetch_object($result)) {
?>	
    <p><?php print $PMF_LANG["ad_entry_commentby"] ?> <a href="mailto:<?php print $row->email; ?>"><?php print $row->usr; ?></a>:<br /><?php print $row->comment; ?><br /><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=delcomment&amp;artid=<?php print $row->id; ?>&amp;cmtid=<?php print $row->id_comment; ?>&amp;lang=<?php print $lang; ?>"><img src="images/delete.gif" alt="<?php print $PMF_LANG["ad_entry_delete"] ?>" title="<?php print $PMF_LANG["ad_entry_delete"] ?>" border="0" width="17" height="18" align="right" /></a></p>
<?php
			}
		}
	}
} elseif ($permission["editbt"] != 1 && !emptyTable(SQLPREFIX."faqcategories")) {
    print $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && emptyTable(SQLPREFIX."faqcategories")) {
    print $PMF_LANG["no_cats"];
}
?>
