<?php
/******************************************************************************
 * File:				record.show.php
 * Description:			show a record
 * Authors:				Thorsten Rinne <thorsten@phpmyfaq.de>
 * Date:				2003-02-23
 * Last change:			2004-11-06
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

print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";
if ($permission["editbt"] || $permission["delbt"]) {
	$tree = new Category();
    $tree->transform(0);
	
    $query = "SELECT id, lang, rubrik, thema, author FROM ".SQLPREFIX."faqdata WHERE active = 'no' ORDER BY rubrik, id ";
    $laktion = 'view';
    $internalSearch = '';
    
    if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "accept") {
        $query = "SELECT id, lang, rubrik, thema, author FROM ".SQLPREFIX."faqdata WHERE active = 'no' ORDER BY rubrik, id ";
        $laktion = "accept";
        $internalSearch = "";
    }
    
    if (isset($_REQUEST["suchbegriff"]) && $_REQUEST["suchbegriff"] != "") {
        $begriff = $_REQUEST["suchbegriff"];
        $query = ("SELECT id, lang, rubrik, thema, author FROM ".SQLPREFIX."faqdata WHERE MATCH (thema,content,keywords) AGAINST ('".$begriff."')  ORDER BY rubrik, id ");
        $laktion = "view";
        $internalSearch = "&amp;search=".$begriff;
    }
    
    $perpage = 20;
	if (!isset($_REQUEST["pages"])) {
		$anz = $db->num_rows($db->query($query));
		$pages = ceil($anz / $perpage);
		if ($pages < 1) {
			$pages = 1;
        }
    } else {
		$pages = $_REQUEST["pages"];
    }
	
    if (!isset($_REQUEST["page"])) {
		$page = 1;
    } else {
		$page = $_REQUEST["page"];
    }
	
	$start = ($page - 1) * $perpage;
	$PageSpan = PageSpan("<a href=\"".$_SERVER["PHP_SELF"].$linkext."&amp;aktion=".$laktion."&amp;pages=".$pages."&amp;page=<NUM>".$internalSearch."\">", 1, $pages, $page);
	
	$result = $db->query($query." LIMIT ".$start.", ".$perpage);
	
	if (isset($_REQUEST["aktion"]) && $_REQUEST["aktion"] == "view") {
		$resultComments = $db->query("SELECT count(id) as anz, id FROM ".SQLPREFIX."faqcomments GROUP BY id ORDER BY id;");
		if ($db->num_rows($resultComments) > 1) {
			while ($row = $db->fetch_object($resultComments)) {
				$numComments[$row->id] = $row->anz;
            }
        }
    }
	$old = 0;
	$previousID = 0;
    
	if ($db->num_rows($result) > 0) {
?>
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=accept" method="post">
    <fieldset>
    <legend><?php print $PMF_LANG["msgSearch"]; ?></legend>
    <strong><?php print $PMF_LANG["msgSearchWord"]; ?>:</strong> <input class="admin" type="text" name="suchbegriff" size="50">&nbsp;&nbsp;<input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["msgSearch"]; ?>">
    </fieldset>
    </form>
<?php
        while (list($id, $lang, $rub, $topic, $author) = $db->fetch_row($result)) {
            if ($rub != $old) {
			    if ($old == 0) {
?>
    <table class="list">
<?php
                } else {
?>
	</table>
	<br />	
    <table class="list">
<?php
                }
?>
    <thead>
        <tr>
            <th colspan="4" class="list"><?php print $tree->getPath($rub); ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
		    <td colspan="4" class="list"><?php print $PageSpan; ?></td>
        </tr>
    </tfoot>
    <tbody>
<?php
            }
?>
        <tr>
            <td class="list"><?php print $id; ?></td>		
            <td class="list"><?php print $lang; ?></td>
            <td class="list"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=saveentry&amp;id=<?php print $id; ?>&amp;language=<?php print $lang; ?>&amp;submit[0]=<?php print $PMF_LANG["ad_entry_delete"]; ?>" title="<?php print $PMF_LANG["ad_user_delete"]; ?> '<?php print str_replace("\"", "´", stripslashes($topic)); ?>'"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_entry_delete"]; ?>" /></a></td>
            <td class="list"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=editentry&amp;id=<?php print $id; ?>&amp;lang=<?php print $lang; ?>" title="<?php print $PMF_LANG["ad_user_edit"]; ?> '<?php print str_replace("\"", "´", stripslashes($topic)); ?>'"><?php print stripslashes($topic); ?></a><?php
            if (isset($numComments[$id])) {
                print " (".$numComments[$id]." ".$PMF_LANG["ad_start_comments"].")";
            }
?></td>
        </tr>
<?php
            $previousID = $id;
            $old = $rub;
        }
?>
    </tbody>
	</table>
	<p align="right"><strong>[ <a href="#top"><?php print $PMF_LANG["ad_gen_top"]; ?></a> ]</strong></p>
<?php
    } else {
        print "n/a";
    }
} else {
	print $PMF_LANG["err_NotAuth"];
}
?>
