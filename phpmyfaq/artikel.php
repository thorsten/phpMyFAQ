<?php
/**
* $Id: artikel.php,v 1.8 2004-12-13 19:58:14 thorstenr Exp $
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Meikel Katzengreis <meikel@katzengreis.com>
* @since        2002-08-27
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

if (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) {
	$currentCategory = $_REQUEST['cat'];
} else {
    $currentCategory = '';
}
if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

Tracking("artikelview", $id);

$comment = '';
$result = $db->query("SELECT id, content, datum, author, email, comment FROM ".SQLPREFIX."faqdata WHERE id = '".$id."' AND lang = '".$lang."' AND active = 'yes'");
while ($row = $db->fetch_object($result)) {
	$id = $row->id;
	$comment = $row->comment;
	logViews($id, $lang);
	$content = str_replace('../', '', stripslashes($row->content));
	if (is_dir('attachments/')  && is_dir('attachments/'.$id) && isset($PMF_CONF['disatt'])) {
		$files = 0;
		$outstr = "";
		$dir = opendir('attachments/'.$id);
		
		while ($dat = readdir($dir)) {
			if ($dat != '.' && $dat != '..') {
				$files++;
				$outstr .= '<a href="attachments/'.$id.'/'.$dat.'" target="_blank">'.$dat.'</a>, ';
			}
		}
		if ($files > 0) {
			$content .= '<p>'.$PMF_LANG['msgAttachedFiles'].' '.substr($outstr, 0, -2).'</p>';
		}
	}
	$writeDateMsg = makeDate($row->datum);
	$writeAuthor = $row->author;
    $categoryName = $tree->getPath($currentCategory);
}

$writePrintMsg          = '<a href="#" onclick="javascript:window.print();">'.$PMF_LANG['msgPrinterFriendly'].'</a>';
$writePDF               = '<a target="_blank" href="pdf.php?cat='.$currentCategory.'&amp;id='.$id.'&amp;lang='.$lang.'">'.$PMF_LANG['msgPDF'].'</a>';
$writeSend2FriendMsg    = '<a href="'.$_SERVER['PHP_SELF'].'?'.$sids.'action=send2friend&amp;cat='.$currentCategory.'&amp;id='.$id.'&amp;artlang='.$lang.'">'.$PMF_LANG['msgSend2Friend'].'</a>';
$writeXMLMsg            = "<a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=xml&amp;id=".$id."&amp;artlang=".$lang."\">".$PMF_LANG["msgMakeXMLExport"]."</a>";
$changeLanguagePATH     = $_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$currentCategory."&amp;id=".$id;
$writeCommentMsg        = $PMF_LANG["msgYouCan"]."<a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=writecomment&amp;id=".$id."&amp;artlang=".$lang."\">".$PMF_LANG["msgWriteComment"]."</a>";
$writeCategory          = stripslashes($categoryName)."<br />\n";
$saveVotingPATH         = $_SERVER["PHP_SELF"]."?".$sids."action=savevoting";

if (isset($_REQUEST["highlight"]) && $_REQUEST["highlight"] != "/" && $_REQUEST["highlight"] != "<" && $_REQUEST["highlight"] != ">") {
    $highlight = $_REQUEST["highlight"];
    $highlight = str_replace("/", "\/", $highlight);
    $content = preg_replace('/(((href|src)="[^"]*)?'.$highlight.'(?(1).*"))/mies', "highlight_no_links(\"\\1\")", $content);
	}

$arrLanguage = check4Language($id);
$switchLanguage = "";
$check4Lang = "";
$num = count($arrLanguage);
if ($num > 1) {
	foreach ($arrLanguage as $language) {
		$check4Lang .= "<option value=\"".$language."\">".$languageCodes[strtoupper($language)]."</option>\n";
	}
	$switchLanguage .= "<p>\n";
    $switchLanguage .= "<fieldset>\n";
    $switchLanguage .= "<legend>".$PMF_LANG["msgLangaugeSubmit"]."</legend>\n";
	$switchLanguage .= "<form action=\"".$changeLanguagePATH."\" method=\"post\" style=\"display: inline;\">\n";
	$switchLanguage .= "<select name=\"artlang\" size=\"1\">\n";
	$switchLanguage .= $check4Lang;
	$switchLanguage .= "</select>\n";
	$switchLanguage .= "&nbsp;\n";
	$switchLanguage .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"".$PMF_LANG["msgLangaugeSubmit"]."\" />\n";
    $switchLanguage .= "</fieldset>\n";
	$switchLanguage .= "</form>\n";
	$switchLanguage .= "</p>\n";
}

$myComment = $writeCommentMsg;
if ($comment == "n") {
	$myComment = $PMF_LANG["msgWriteNoComment"];
}

$tpl->processTemplate ("writeContent", array(
				"writeRubrik" => $writeCategory,
				"writeThema" => stripslashes(getThema($id, $lang)),
				"writeContent" => $content,
				"writeDateMsg" => $PMF_LANG["msgLastUpdateArticle"].$writeDateMsg,
				"writeAuthor" => $PMF_LANG["msgAuthor"].$writeAuthor,
				"writePrintMsg" => $writePrintMsg,
				"writePDF" => $writePDF,
				"writeSend2FriendMsg" => $writeSend2FriendMsg,
				"writeXMLMsg" => $writeXMLMsg,
				"saveVotingPATH" => $saveVotingPATH,
				"saveVotingID" => $id,
				"saveVotingIP" => $_SERVER["REMOTE_ADDR"],
				"msgAverageVote" => $PMF_LANG["msgAverageVote"],
				"printVotings" => generateVoting($id),
				"switchLanguage" => $switchLanguage,
				"msgVoteUseability" => $PMF_LANG["msgVoteUseability"],
				"msgVoteBad" => $PMF_LANG["msgVoteBad"],
				"msgVoteGood" => $PMF_LANG["msgVoteGood"],
				"msgVoteSubmit" => $PMF_LANG["msgVoteSubmit"],
				"writeCommentMsg" => $myComment,
				"writeListMsg" => $PMF_LANG["msgShowCategory"].$writeCategory,
				"writeComments" => generateComments($id)
				));

$tpl->includeTemplate("writeContent", "index");
?>
