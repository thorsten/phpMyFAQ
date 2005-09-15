<?php
/**
* $Id: artikel.php,v 1.22 2005-09-15 17:53:21 thorstenr Exp $
*
* Shows the page with the FAQ record and - when available - the user
* comments
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Meikel Katzengreis <meikel@katzengreis.com>
* @since        2002-08-27
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

if (isset($_REQUEST['cat']) && is_numeric($_REQUEST['cat'])) {
	$currentCategory = (int)$_REQUEST['cat'];
} else {
    $currentCategory = 0;
}
if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
	$id = (int)$_REQUEST['id'];
}

Tracking("article_view", $id);

$comment = '';
$result = $db->query(sprintf("SELECT id, content, datum, author, email, comment FROM %sfaqdata WHERE id = %d AND lang = '%s' AND active = 'yes'", SQLPREFIX, $id, $lang));

while ($row = $db->fetch_object($result)) {
	$id = $row->id;
	$comment = $row->comment;
	logViews($id, $lang);
	$content = stripslashes($row->content);
	$writeDateMsg = makeDate($row->datum);
	$writeAuthor = $row->author;
    $categoryName = $tree->getPath($currentCategory);
}

$writePrintMsg          = sprintf('<a href="#" onclick="javascript:window.print();">%s</a>', 
                            $PMF_LANG["msgPrintArticle"]);
$writePDF               = sprintf('<a target="_blank" href="pdf.php?cat=%s&amp;id=%d&amp;lang=%s">'.$PMF_LANG['msgPDF'].'</a>', 
                            $currentCategory, $id, $lang);
$writeSend2FriendMsg    = sprintf('<a href="%s?'.$sids.'action=send2friend&amp;cat=%d&amp;id=&d&amp;artlang=%s">%s</a>', 
                            $_SERVER['PHP_SELF'], $currentCategory, $id, $lang, $PMF_LANG['msgSend2Friend']);
$writeXMLMsg            = sprintf('<a href="%s?%daction=xml&amp;id=%d&amp;artlang=%d">%s</a>',
                            $_SERVER["PHP_SELF"], $sids, $id, $lang, $PMF_LANG["msgMakeXMLExport"]);
$changeLanguagePATH     = $_SERVER["PHP_SELF"]."?".$sids."action=artikel&amp;cat=".$currentCategory."&amp;id=".$id;
$writeCommentMsg        = $PMF_LANG["msgYouCan"]."<a href=\"".$_SERVER["PHP_SELF"]."?".$sids."action=writecomment&amp;id=".$id."&amp;artlang=".$lang."\">".$PMF_LANG["msgWriteComment"]."</a>";
$writeCategory          = stripslashes($categoryName)."<br />\n";
$saveVotingPATH         = sprintf('%s?%daction=savevoting', 
                            $_SERVER["PHP_SELF"], $sids);

if (isset($_GET["highlight"]) && $_GET["highlight"] != "/" && $_GET["highlight"] != "<" && $_GET["highlight"] != ">") {
    $highlight = strip_tags($_GET["highlight"]);
    $highlight = str_replace("'", "´", $highlight);
    $highlight = preg_quote($highlight, '/');
    $content = preg_replace_replace('/(((href|src)="[^"]*)?'.$highlight.'(?(1).*"))/mis', "highlight_no_links", $content);
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

$tpl->processTemplate ("writeContent", array(
				"writeRubrik" => $writeCategory,
				"writeThema" => stripslashes(getThema($id, $lang)),
				"writeContent" => preg_replace_callback("/<pre([^>]*)>(.*?)<\/pre>/is", 'hilight', $content),
				"writeDateMsg" => $PMF_LANG["msgLastUpdateArticle"].$writeDateMsg,
				"writeAuthor" => $PMF_LANG["msgAuthor"].$writeAuthor,
				"writePrintMsg" => $writePrintMsg,
				"writePDF" => $writePDF,
				"writeSend2FriendMsg" => $writeSend2FriendMsg,
				"writeXMLMsg" => $writeXMLMsg,
				"writePrintMsgTag" => $PMF_LANG["msgPrintArticle"],
				"writePDFTag" => $PMF_LANG["msgPDF"],
				"writeSend2FriendMsgTag" => $PMF_LANG["msgSend2Friend"],
				"writeXMLMsgTag" => $PMF_LANG["msgMakeXMLExport"],
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
				"writeCommentMsg" => ($comment == 'n') ? $PMF_LANG['msgWriteNoComment'] : $writeCommentMsg,
				"writeComments" => generateComments($id)
				));

$tpl->includeTemplate("writeContent", "index");
?>
