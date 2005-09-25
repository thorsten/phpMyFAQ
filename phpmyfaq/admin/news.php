<?php
/**
* $Id: news.php,v 1.10 2005-09-25 09:47:02 thorstenr Exp $
*
* The main administration file for the news
*
* @author			Thorsten Rinne <thorsten@phpmyfaq.de>
* @since			2003-02-23
* @copyright		(c) 2001-2005 phpMyFAQ Team
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

print '<h2>'.$PMF_LANG['ad_news_add'].'</h2>';
if (isset($_REQUEST["do"]) && $_REQUEST["do"] == "write" && $permission["addnews"]) {
?>
	<form id="editRecord" name="editRecord" action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
	<input type="hidden" name="aktion" value="news" />
	<input type="hidden" name="do" value="save" />
    <dl>
        <dt><strong><?php print $PMF_LANG["ad_news_header"]; ?></strong></dt>
	    <dd><input class="admin" type="text" style="width: 525px;" name="header" /></dd>
        <dt><strong><?php print $PMF_LANG["ad_news_text"]; ?></strong></dt>
        <dd><noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea class="admin" id="content" name="content"></textarea></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_url"]; ?></strong></dt>
        <dd><input class="admin" type="text" style="width: 525px;" name="link" /></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_title"]; ?></strong></dt>
        <dd><input class="admin" type="text" style="width: 525px;" name="linktitel" /></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_target"]; ?></strong></dt>
        <dd><input type="radio" name="target" value="blank" /><?php print $PMF_LANG["ad_news_link_window"]; ?><br />
        <input type="radio" name="target" value="self" /><?php print $PMF_LANG["ad_news_link_faq"]; ?></dd>
        <dt>&nbsp;</dt>
        <dd><input class="submit" type="submit" value="<?php print $PMF_LANG["ad_news_add"]; ?>" /></dd>
    </dl>
	</form>
<?php
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "write" && $permission["addnews"]) {
	print $PMF_LANG["err_NotAuth"];
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "edit" && $permission["editnews"]) {
	if (!isset($_REQUEST["id"])) {
?>
    <table class="list">
    <thead>
        <tr>
            <th class="list"><?php print $PMF_LANG["ad_news_headline"]; ?></th>
            <th class="list"><?php print $PMF_LANG["ad_news_date"]; ?></th>
            <th class="list">&nbsp;</th>
        </tr>
    </thead>
    <tbody>
<?php
		$result = $db->query("select id, datum, header from ".SQLPREFIX."faqnews order by datum desc");
		if ($db->num_rows($result) > 0) {
			while ($row = $db->fetch_object($result)) {
            	$datum = makeDate($row->datum);
?>
        <tr>
            <td class="list"><?php print $row->header; ?></td>
            <td class="list"><?php print $datum; ?></td>
            <td class="list"><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=news&amp;do=edit&amp;id=<?php print $row->id; ?>" title="<?php print $PMF_LANG["ad_news_update"]; ?>"><img src="images/edit.gif" width="18" height="18" alt="<?php print $PMF_LANG["ad_news_update"]; ?>" border="0" /></a>&nbsp;&nbsp;<a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=news&amp;do=delete&amp;id=<?php print $row->id; ?>" title="<?php print $PMF_LANG["ad_news_delete"]; ?>"><img src="images/delete.gif" width="17" height="18" alt="<?php print $PMF_LANG["ad_news_delete"]; ?>" border="0" /></a></td>
        </tr>
<?php
           	}
		} else {
           	print "<tr><td colspan=\"3\" class=\"list\">".$PMF_LANG["ad_news_nodata"]."</td></tr>"; 
		}
?>
    </tbody>
    </table>
    <p><a href="<?php print $_SERVER["PHP_SELF"].$linkext; ?>&amp;aktion=news&amp;do=write"><?php print $PMF_LANG["ad_menu_news_add"]; ?></a></p>
<?php
	} elseif (isset($_REQUEST["id"])) {
		$result = $db->query("select id, header, artikel, link, linktitel, target from ".SQLPREFIX."faqnews where id = ".$_REQUEST["id"]); 
		while ($row = $db->fetch_object($result)) {
?>
	<h2><?php print $PMF_LANG["ad_news_edit"]; ?></h2>
	<form id="editRecord" name="editRecord" action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post"> 
	<input type="hidden" name="aktion" value="news" />
	<input type="hidden" name="do" value="update" />
	<input type="hidden" name="id" value="<?php print $row->id ?>" />
    <dl>
        <dt><strong><?php print $PMF_LANG["ad_news_header"]; ?></strong></dt>
	    <dd><input class="admin" type="text" style="width: 525px;" name="header" value="<?php print $row->header ?>" /></dd>
        <dt><strong><?php print $PMF_LANG["ad_news_text"]; ?></strong></dt>
        <dd><noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript><textarea class="admin" id="content" name="content"><?php if (isset($row->artikel)) { print htmlspecialchars(stripslashes($row->artikel), ENT_QUOTES); } ?></textarea></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_url"]; ?></strong></dt>
        <dd><input class="admin" type="text" style="width: 525px;" name="link" value="<?php print $row->link; ?>" /></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_title"]; ?></strong></dt>
        <dd><input class="admin" type="text" style="width: 525px;" name="linktitel" value="<?php print $row->linktitel; ?>" /></dd>
	    <dt><strong><?php print $PMF_LANG["ad_news_link_target"]; ?></strong></dt>
        <dd><input type="radio" name="target" value="blank" <?php if ($row->target == "blank") { ?> checked="checked"<?php } ?> /><?php print $PMF_LANG["ad_news_link_window"] ?><br /><input type="radio" name="target" value="self" <?php if ($row->target == "self") { ?> checked="checked"<?php } ?> /><?php print $PMF_LANG["ad_news_link_faq"] ?></dd>
        <dt>&nbsp;</dt>
        <dd><input class="submit" type="submit" value="<?php print $PMF_LANG["ad_news_add"]; ?>" /></dd>
    </dl>
	</form>
<?php
		}
	}
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "edit" && $permission["editnews"]) {
	print $PMF_LANG["err_NotAuth"];
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "save" && $permission["addnews"]) {
	$datum = date("YmdHis");
	$artikel = $db->escape_string($_REQUEST["content"]);
    (!isset($_REQUEST["target"])) ? $target = "" : $target = $_POST["target"];
    $result = $db->query("INSERT INTO ".SQLPREFIX."faqnews (id, header, artikel, link, linktitel, datum, target) VALUES (".$db->nextID(SQLPREFIX."faqnews",
"id").", '".$db->escape_string($_REQUEST["header"])."', '".$artikel."', '".$_REQUEST["link"]."', '".$_REQUEST["linktitel"]."', '".$datum."', '".$target."')");
	print "<p>".$PMF_LANG["ad_news_updatesuc"]."</p>";
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "save" && $permission["addnews"]) {
	print $PMF_LANG["err_NotAuth"];
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "update" && $permission["editnews"]) {
	$datum = date("YmdHis");
	$artikel = $db->escape_string($_REQUEST["content"]);
	(!isset($_REQUEST["target"])) ? $target = "" : $target = $_POST["target"];
	$result = $db->query("UPDATE ".SQLPREFIX."faqnews SET header = '".$db->escape_string($_REQUEST["header"])."', artikel = '".$artikel."', link = '".$_REQUEST["link"]."', linktitel = '".$_REQUEST["linktitel"]."', datum = '".$datum."', target = '".$target."' WHERE id = ".$_REQUEST["id"]);
	print "<p>".$PMF_LANG["ad_news_updatesuc"]."</p>";
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "update" && $permission["editnews"]) {
	print $PMF_LANG["err_NotAuth"];
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "delete" && $permission["delnews"]) {
	if (!isset($_REQUEST["really"])) {
?>
	<p><?php print $PMF_LANG["ad_news_del"]; ?></p>
    <div align="center">
    <form action="<?php print $_SERVER["PHP_SELF"].$linkext; ?>" method="post">
    <input type="hidden" name="aktion" value="news" />
    <input type="hidden" name="do" value="delete" />
    <input type="hidden" name="id" value="<?php print $_REQUEST["id"]; ?>" />
    <input type="hidden" name="really" value="yes" />
    <input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG["ad_news_yesdelete"]; ?>" style="color: Red;" />
    <input class="submit" type="reset" onclick="javascript:history.back();" value="<?php print $PMF_LANG["ad_news_nodelete"]; ?>" />
    </form>
    </div>
<?php
	} elseif ($_REQUEST["really"] == "yes") {
		$result = $db->query("DELETE FROM ".SQLPREFIX."faqnews WHERE id = ".$_REQUEST["id"]);
		print "<p>".$PMF_LANG["ad_news_delsuc"]."</p>";
	}
} elseif (isset($_REQUEST["do"]) && $_REQUEST["do"] == "delete" && $permission["delnews"]) {
	print $PMF_LANG["err_NotAuth"];
}
?>