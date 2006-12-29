<?php
/**
* $Id: artikel.php,v 1.66 2006-12-29 22:53:28 matteo Exp $
*
* Shows the page with the FAQ record and - when available - the user
* comments
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @author       Lars Tiedemann <larstiedemann@yahoo.de>
* @since        2002-08-27
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

if (isset($_GET['gen'])) {
    $captcha->showCaptchaImg();
    exit();
}

$currentCategory = $cat;

if (isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
    $id = (int)$_REQUEST['id'];
}
if (isset($_REQUEST['solution_id']) && is_numeric($_REQUEST['solution_id'])) {
    $solution_id = $_REQUEST['solution_id'];
} else {
    $solution_id = 0;
}

Tracking('article_view', $id);

// Get all data from the FAQ record
if (0 == $solution_id) {
    $faq->getRecord($id);
} else {
    $faq->getRecordBySolutionId($solution_id);
}
$faq->logViews($faq->faqRecord['id']);

$content = $faq->faqRecord['content'];
$thema   = $faq->getRecordTitle($id);
// Add Glossary entries
$oG = new PMF_Glossary($db, $LANGCODE);
$content = $oG->insertItemsIntoContent($content);
$thema   = $oG->insertItemsIntoContent($thema);

// Set the path of the current category
$categoryName = $tree->getPath($currentCategory, ' &raquo; ', true);

$changeLanguagePath = PMF_Link::getSystemRelativeUri().sprintf('?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s', $sids, $currentCategory, $id, $LANGCODE);
$oLink = new PMF_Link($changeLanguagePath);
$oLink->itemTitle = $faq->getRecordTitle($id, false);
$changeLanguagePath = $oLink->toString();

$highlight = '';
if (isset($_GET['highlight']) && $_GET['highlight'] != "/" && $_GET['highlight'] != "<" && $_GET['highlight'] != ">" && strlen($_GET['highlight']) > 1) {
    $highlight = strip_tags($_GET['highlight']);
    $highlight = str_replace("'", "´", $highlight);
    $highlight = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'), '', $highlight);
    $highlight = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);
    foreach ($searchItems as $item) {
        $thema = preg_replace_callback('/'
                    .'('.$item.'="[^"]*")|'
                    .'((href|src|title|alt|class|style|id|name)="[^"]*'.$item.'[^"]*")|'
                    .'('.$item.')'
                    .'/mis',
                    "highlight_no_links",
                    $thema
                    );
        $content = preg_replace_callback('/'
                    .'('.$item.'="[^"]*")|'
                    .'((href|src|title|alt|class|style|id|name)="[^"]*'.$item.'[^"]*")|'
                    .'('.$item.')'
                    .'/mis',
                    "highlight_no_links",
                    $content
                    );
    }
}

// Hack: Apply the new SEO schema to those HTML anchors to
//       other faq records (Internal Links) added with WYSIWYG Editor:
//         href="index.php?action=artikel&cat=NNN&id=MMM&artlang=XYZ"
// Search for href attribute links
require_once('inc/Linkverifier.php');
$oLnk = new PMF_Linkverifier($db);
// Extract URLs from content
$oLnk->resetPool();
$oLnk->parse_string($content);
$fixedContent = $content;
// Search for href attributes only
if (isset($oLnk->urlpool['href'])) {
    foreach ($oLnk->urlpool['href'] as $_url) {
        if (!(strpos($_url, 'index.php?action=artikel') === false)) {
            // Get the Faq link title
            preg_match('/id=([\d]+)/ism', $_url, $matches);
            $_id   = $matches[1];
            preg_match('/artlang=([a-z\-]+)$/ism', $_url, $matches);
            $_lang = $matches[1];
            $_title = $faq->getRecordTitle($_id, false);
            $_link = substr($_url, 9);
            // Move the link to XHTML
            if (strpos($_url, '&amp;') === false) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink = new PMF_Link(PMF_Link::getSystemRelativeUri().$_link);
            $oLink->itemTitle = $_title;
            $oLink->tooltip = PMF_htmlentities($_title, ENT_NOQUOTES, $PMF_LANG['metaCharset']);
            $newFaqPath = $oLink->toString();
            $fixedContent = str_replace($_url, $newFaqPath, $fixedContent);
        }
    }
}
$content = $fixedContent;

$arrLanguage = check4Language($id);
$switchLanguage = '';
$check4Lang = '';
$num = count($arrLanguage);
if ($num > 1) {
    foreach ($arrLanguage as $language) {
        $check4Lang .= "<option value=\"".$language."\"";
        $check4Lang .= ($lang == $language ? ' selected="selected"' : '');
        $check4Lang .= ">".$languageCodes[strtoupper($language)]."</option>\n";
    }
    $switchLanguage .= "<p>\n";
    $switchLanguage .= "<fieldset>\n";
    $switchLanguage .= "<legend>".$PMF_LANG["msgLangaugeSubmit"]."</legend>\n";
    $switchLanguage .= "<form action=\"".$changeLanguagePath."\" method=\"post\" style=\"display: inline;\">\n";
    $switchLanguage .= "<select name=\"artlang\" size=\"1\">\n";
    $switchLanguage .= $check4Lang;
    $switchLanguage .= "</select>\n";
    $switchLanguage .= "&nbsp;\n";
    $switchLanguage .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"".$PMF_LANG["msgLangaugeSubmit"]."\" />\n";
    $switchLanguage .= "</fieldset>\n";
    $switchLanguage .= "</form>\n";
    $switchLanguage .= "</p>\n";
}

if (is_dir('attachments/') && is_dir('attachments/'.$id) && $faqconfig->get('disatt')) {
    $files = 0;
    $outstr = "";
    $dir = opendir('attachments/'.$id);
    while ($dat = readdir($dir)) {
        if ($dat != '.' && $dat != '..') {
            $files++;
            $outstr .= '<a href="attachments/'.$id.'/'.rawurlencode($dat).'" target="_blank">'.$dat.'</a>, ';
        }
    }
    if ($files > 0) {
        $content .= '<p>'.$PMF_LANG['msgAttachedFiles'].' '.substr($outstr, 0, -2).'</p>';
    }
}

$writeMultiCategories = '';
$cat = new PMF_Category($lang);
$multiCats = $cat->getCategoriesFromArticle($id);
if (count($multiCats) > 1) {
    $writeMultiCategories .= '        <div id="article_categories">';
    $writeMultiCategories .= '        <fieldset>';
    $writeMultiCategories .= '                <legend>'.$PMF_LANG['msgArticleCategories'].'</legend>';
    $writeMultiCategories .= '            <ul>';
    foreach ($multiCats as $multiCat) {
        $writeMultiCategories .= sprintf('<li><a href="%s?%saction=show&amp;cat=%d">%s</a></li>', $_SERVER['PHP_SELF'], $sids, $multiCat['id'], $multiCat['name']);
        $writeMultiCategories .= "\n";
    }
    $writeMultiCategories .= '            </ul>';
    $writeMultiCategories .= '        </fieldset>';
    $writeMultiCategories .= '    </div>';
}

// Show link to edit the faq?
$editThisEntry = '';
if (isset($permission['editbt'])) {
    $editThisEntry = sprintf(
                        '<a href="%sadmin/index.php?action=editentry&amp;id=%d&amp;lang=%s">%s</a>',
                        PMF_Link::getSystemRelativeUri('index.php'),
                        $id,
                        $lang,
                        $PMF_LANG['ad_entry_edit_1']
                        );
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Does the user have the right to add a comment?
if (($faq->faqRecord['active'] != 'yes') || ('n' == $faq->faqRecord['comment']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf('%s<a onclick="show(\'comment\');" href="#comment">%s</a>',
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['msgWriteComment']);
}

// Get the tags for this entry
require_once('inc/Tags.php');
$tagging = new PMF_Tags($db, $LANGCODE);

$diggItUrl = sprintf('%s?cat=%s&id=%d&lang=%s', PMF_Link::getSystemUri(), $currentCategory, $id, $lang);

//$visitsData = $faq->getVisitsData($id, $lang);
//$faqPopularity = $visitsData['visits'];
$allVisitsData = $faq->getAllVisitsData();
$faqPopularity = '';
$maxVisits = 0;
$minVisits = 0;
$currVisits = 0;
$faqVisitsCount = count($allVisitsData);
$percentage = 0;
if ($faqVisitsCount > 0) {
    $percentage = 100/$faqVisitsCount;
}
foreach ($allVisitsData as $_r) {
    if ($minVisits > $_r['visits']) {
        $minVisits = $_r['visits'];
    }
    if ($maxVisits < $_r['visits']) {
        $maxVisits = $_r['visits'];
    }
    if (($id == $_r['id']) && ($lang == $_r['lang'])) {
        $currVisits = $_r['visits'];
    }
}
if ($maxVisits - $minVisits > 0) {
    $percentage = 100*($currVisits - $minVisits)/($maxVisits - $minVisits);
}
$faqPopularity = $currVisits.'/'.(int)$percentage.'%';

// Get the related records for this entry
require_once('inc/Relation.php');
$relevant = new PMF_Relation($db, $LANGCODE);

// Set the template variables
$tpl->processTemplate ("writeContent", array(
    'writeRubrik'                 => $categoryName.'<br />',
    'solution_id'                 => $faq->faqRecord['solution_id'],
    'writeThema'                  => $thema,
    'writeArticleCategoryHeader'  => $PMF_LANG['msgArticleCategories'],
    'writeArticleCategories'      => $writeMultiCategories,
    'writeContent'                => preg_replace_callback("/<code([^>]*)>(.*?)<\/code>/is", 'hilight', $content),
    'writeTagHeader'              => $PMF_LANG['msg_tags'] . ': ',
    'writeArticleTags'            => $tagging->getAllLinkTagsById($id),
    'writeRelatedArticlesHeader'  => $PMF_LANG['msg_related_articles'] . ': ',
    'writeRelatedArticles'        => $relevant->getAllRelatedById($id, $faq->faqRecord['title'], $faq->faqRecord['keywords']),
    'writePopularity'             => $faqPopularity,
    'writeDateMsg'                => $PMF_LANG['msgLastUpdateArticle'].$faq->faqRecord['date'],
    'writeRevision'               => $PMF_LANG['ad_entry_revision'].': 1.'.$faq->faqRecord['revision_id'],
    'writeAuthor'                 => $PMF_LANG['msgAuthor'].$faq->faqRecord['author'],
    'editThisEntry'               => $editThisEntry,
    'writeDiggMsgTag'             => 'Digg it!',
    'writeDiggMsg'                => sprintf('<a target="_blank" href="http://digg.com/submit?phase=2&amp;url=%s">Digg It!</a>', urlencode($diggItUrl)),
    'writePrintMsg'               => sprintf('<a href="#" onclick="javascript:window.print();">%s</a>', $PMF_LANG['msgPrintArticle']),
    'writePDF'                    => sprintf('<a target="_blank" href="pdf.php?cat=%s&amp;id=%d&amp;lang=%s">'.$PMF_LANG['msgPDF'].'</a>', $currentCategory, $id, $lang),
    'writeSend2FriendMsg'         => sprintf('<a href="index.php?%saction=send2friend&amp;cat=%d&amp;id=%d&amp;artlang=%s">%s</a>', $sids, $currentCategory, $id, $lang, $PMF_LANG['msgSend2Friend']),
    'writePrintMsgTag'            => $PMF_LANG['msgPrintArticle'],
    'writePDFTag'                 => $PMF_LANG['msgPDF'],
    'writeSend2FriendMsgTag'      => $PMF_LANG['msgSend2Friend'],
    'saveVotingPATH'              => sprintf('index.php?%saction=savevoting', $sids),
    'saveVotingID'                => $id,
    'msgAverageVote'              => $PMF_LANG['msgAverageVote'],
    'printVotings'                => $faq->getVotingResult($id),
    'switchLanguage'              => $switchLanguage,
    'msgVoteUseability'           => $PMF_LANG['msgVoteUseability'],
    'msgVoteBad'                  => $PMF_LANG['msgVoteBad'],
    'msgVoteGood'                 => $PMF_LANG['msgVoteGood'],
    'msgVoteSubmit'               => $PMF_LANG['msgVoteSubmit'],
    'writeCommentMsg'             => $commentMessage,
    'msgWriteComment'             => $PMF_LANG['msgWriteComment'],
    'writeSendAdress'             => $_SERVER['PHP_SELF'].'?'.$sids.'action=savecomment',
    'id'                          => $id,
    'lang'                        => $lang,
    'msgCommentHeader'            => $PMF_LANG['msgCommentHeader'],
    'msgNewContentName'           => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'           => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'          => getEmailAddress(),
    'defaultContentName'          => getFullUserName(),
    'msgYourComment'              => $PMF_LANG['msgYourComment'],
    'msgNewContentSubmit'         => $PMF_LANG['msgNewContentSubmit'],
    'captchaFieldset'             => printCaptchaFieldset($PMF_LANG['msgCaptcha'], $captcha->printCaptcha('writecomment'), $captcha->caplength),
    'writeComments'               => $faq->getComments($id)));

$tpl->includeTemplate('writeContent', 'index');
