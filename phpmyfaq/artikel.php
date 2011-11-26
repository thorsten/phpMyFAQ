<?php
/**
 * Shows the page with the FAQ record and - when available - the user comments
 * 
 * PHP Version 5.2
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
 *
 * @category  phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <larstiedemann@yahoo.de>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-27
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha   = new PMF_Captcha($db, $Language);
$oGlossary = new PMF_Glossary();
$oLnk      = new PMF_Linkverifier();
$tagging   = new PMF_Tags($db, $Language);
$relevant  = new PMF_Relation($db, $Language);
$faqrating = new PMF_Rating();
$comment   = new PMF_Comment();

$captcha->setSessionId($sids);
if (!is_null($showCaptcha)) {
    $captcha->showCaptchaImg();
    exit;
}

$currentCategory = $cat;
$record_id       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$solution_id     = PMF_Filter::filterInput(INPUT_GET, 'solution_id', FILTER_VALIDATE_INT);
$highlight       = PMF_Filter::filterInput(INPUT_GET, 'highlight', FILTER_SANITIZE_STRIPPED);

// Set the art language
$faq->setLanguage($lang);

// Get all data from the FAQ record
if (0 == $solution_id) {
    $faq->getRecord($record_id);
} else {
    $faq->getRecordBySolutionId($solution_id);
}

$faqsession->userTracking('article_view', $faq->faqRecord['id']);

$faqvisits = PMF_Visits::getInstance();
$faqvisits->logViews($faq->faqRecord['id']);

$content = $faq->faqRecord['content'];
$thema   = $faq->getRecordTitle($faq->faqRecord['id']);
// Add Glossary entries
$content = $oGlossary->insertItemsIntoContent($content);
$thema   = $oGlossary->insertItemsIntoContent($thema);

// Set the path of the current category
$categoryName = $category->getPath($currentCategory, ' &raquo; ', true);

$changeLanguagePath = PMF_Link::getSystemRelativeUri().sprintf('?%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s', 
    $sids, 
    $currentCategory, 
    $faq->faqRecord['id'], 
    $LANGCODE);
$oLink              = new PMF_Link($changeLanguagePath);
$oLink->itemTitle   = $faq->getRecordTitle($faq->faqRecord['id'], false);
$changeLanguagePath = $oLink->toString();

$highlight = PMF_Filter::filterInput(INPUT_GET, 'highlight', FILTER_SANITIZE_STRIPPED);
if (!is_null($highlight) && $highlight != "/" && $highlight != "<" && $highlight != ">" && PMF_String::strlen($highlight) > 3) {
    $highlight   = str_replace("'", "´", $highlight);
    $highlight   = str_replace(array('^', '.', '?', '*', '+', '{', '}', '(', ')', '[', ']'), '', $highlight);
    $highlight   = preg_quote($highlight, '/');
    $searchItems = explode(' ', $highlight);

    foreach ($searchItems as $item) {
        $thema   = PMF_Utils::setHighlightedString($thema, $item);
        $content = PMF_Utils::setHighlightedString($content, $item);
    }
}

// Hack: Apply the new SEO schema to those HTML anchors to
//       other faq records (Internal Links) added with WYSIWYG Editor:
//         href="index.php?action=artikel&cat=NNN&id=MMM&artlang=XYZ"
// Search for href attribute links
$oLnk->resetPool();
$oLnk->parse_string($content);
$fixedContent = str_replace('href="#', 
    sprintf('href="index.php?action=artikel&amp;lang=%s&amp;cat=%d&amp;id=%d&amp;artlang=%s#',
        $LANGCODE,
        $currentCategory,
        $faq->faqRecord['id'],
        $LANGCODE),
    $content);
$oLnk->resetPool();
$oLnk->parse_string($fixedContent); 

// Search for href attributes only
$linkArray = $oLnk->getUrlpool();
if (isset($linkArray['href'])) {
    foreach (array_unique($linkArray['href']) as $_url) {
        $xpos = strpos($_url, 'index.php?action=artikel');
        if (!($xpos === false)) {
            // Get the Faq link title
            $matches = array();
            preg_match('/id=([\d]+)/ism', $_url, $matches);
            $_id    = $matches[1];
            $_title = $faq->getRecordTitle($_id, false);
            $_link  = substr($_url, $xpos + 9);
            if (strpos($_url, '&amp;') === false) {
                $_link = str_replace('&', '&amp;', $_link);
            }
            $oLink            = new PMF_Link(PMF_Link::getSystemRelativeUri().$_link);
            $oLink->itemTitle = $oLink->tooltip = $_title;
            $newFaqPath       = $oLink->toString();
            $fixedContent     = str_replace($_url, $newFaqPath, $fixedContent);
        }
    }
}

$content = $fixedContent; 

// Check for the languages for a faq
$arrLanguage    = PMF_Utils::languageAvailable($faq->faqRecord['id']);
$switchLanguage = '';
$check4Lang     = '';
$num            = count($arrLanguage);
if ($num > 1) {
    foreach ($arrLanguage as $language) {
        $check4Lang .= "<option value=\"".$language."\"";
        $check4Lang .= ($lang == $language ? ' selected="selected"' : '');
        $check4Lang .= ">".$languageCodes[strtoupper($language)]."</option>\n";
    }
    $switchLanguage .= "<form action=\"".$changeLanguagePath."\" method=\"post\" style=\"display: inline;\">\n";
    $switchLanguage .= "<select name=\"artlang\" size=\"1\">\n";
    $switchLanguage .= $check4Lang;
    $switchLanguage .= "</select>\n";
    $switchLanguage .= "&nbsp;\n";
    $switchLanguage .= "<input class=\"submit\" type=\"submit\" name=\"submit\" value=\"".$PMF_LANG["msgLangaugeSubmit"]."\" />\n";
    $switchLanguage .= "</form>\n";
}

// List all faq attachments
if ($faqconfig->get('records.disableAttachments') && 'yes' == $faq->faqRecord['active']) {
    
    $attList = PMF_Attachment_Factory::fetchByRecordId($faq->faqRecord['id']);
    $outstr  = "";
    
    while (list(,$att) = each($attList)) {
        $outstr .= sprintf('<a href="%s">%s</a>, ',
            $att->buildUrl(),
            $att->getFilename());
    }
    if (count($attList) > 0) {
        $content .= '<p>'.$PMF_LANG['msgAttachedFiles'].' '.PMF_String::substr($outstr, 0, -2).'</p>';
    }
}

// List all categories for this faq
$writeMultiCategories = '';
$multiCategories = $category->getCategoriesFromArticle($faq->faqRecord['id']);
if (count($multiCategories) > 1) {
    $writeMultiCategories .= '        <div id="article_categories">';
    $writeMultiCategories .= '        <fieldset>';
    $writeMultiCategories .= '                <legend>'.$PMF_LANG['msgArticleCategories'].'</legend>';
    $writeMultiCategories .= '            <ul>';
    foreach ($multiCategories as $multiCat) {
        $writeMultiCategories .= sprintf("<li>%s</li>\n", $category->getPath($multiCat['id'], ' &raquo; ', true));
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
        $faq->faqRecord['id'],
        $lang,
        $PMF_LANG['ad_entry_edit_1'].' '.$PMF_LANG['ad_entry_edit_2']);
}

// Is the faq expired?
$expired = (date('YmdHis') > $faq->faqRecord['dateEnd']);

// Does the user have the right to add a comment?
if (($faq->faqRecord['active'] != 'yes') || ('n' == $faq->faqRecord['comment']) || $expired) {
    $commentMessage = $PMF_LANG['msgWriteNoComment'];
} else {
    $commentMessage = sprintf(
        "%s<a href=\"javascript:void(0);\" onclick=\"javascript:$('#commentForm').show();\">%s</a>",
        $PMF_LANG['msgYouCan'],
        $PMF_LANG['msgWriteComment']);
}

$translationUrl = sprintf(
    str_replace( '%', '%%', PMF_Link::getSystemRelativeUri('index.php')) . 'index.php?%saction=translate&amp;cat=%s&amp;id=%d&amp;srclang=%s',
        $sids,
        $currentCategory,
        $faq->faqRecord['id'],
        $lang);

if (!empty($switchLanguage)) {
    $tpl->processBlock(
        'writeContent',
        'switchLanguage',
        array(
            'msgChangeLanguage' => $PMF_LANG['msgLangaugeSubmit']
        )
    );
}

// Set the template variables
$tpl->processTemplate('writeContent', array(
    'writeRubrik'                   => $categoryName,
    'solution_id'                   => $faq->faqRecord['solution_id'],
    'writeThema'                    => $thema,
    'writeArticleCategoryHeader'    => $PMF_LANG['msgArticleCategories'],
    'writeArticleCategories'        => $writeMultiCategories,
    'writeContent'                  => $content,
    'writeTagHeader'                => $PMF_LANG['msg_tags'] . ': ',
    'writeArticleTags'              => $tagging->getAllLinkTagsById($faq->faqRecord['id']),
    'writeRelatedArticlesHeader'    => $PMF_LANG['msg_related_articles'] . ': ',
    'writeRelatedArticles'          => $relevant->getAllRelatedById($faq->faqRecord['id'], $faq->faqRecord['title'], $faq->faqRecord['keywords']),
    'writeDateMsg'                  => $PMF_LANG['msgLastUpdateArticle'] . PMF_Date::format($faq->faqRecord['date']),
    'writeRevision'                 => $PMF_LANG['ad_entry_revision'] . ': 1.' . $faq->faqRecord['revision_id'],
    'writeAuthor'                   => $PMF_LANG['msgAuthor'] . ': ' . $faq->faqRecord['author'],
    'editThisEntry'                 => $editThisEntry,
    'msgTranslate'                  => $PMF_LANG['msgTranslate'],
    'translationUrl'                => $translationUrl,
    'languageSelection'             => PMF_Language::selectLanguages($LANGCODE, false, $arrLanguage, 'translation'),
    'msgTranslateSubmit'            => $PMF_LANG['msgTranslateSubmit'],
    'saveVotingPATH'                => sprintf(str_replace('%', '%%', PMF_Link::getSystemRelativeUri('index.php')).'index.php?%saction=savevoting', $sids),
    'saveVotingID'                  => $faq->faqRecord['id'],
    'saveVotingIP'                  => $_SERVER['REMOTE_ADDR'],
    'msgAverageVote'                => $PMF_LANG['msgAverageVote'],
    'printVotings'                  => $faqrating->getVotingResult($record_id),
    'switchLanguage'                => $switchLanguage,
    'msgVoteUseability'             => $PMF_LANG['msgVoteUseability'],
    'msgVoteBad'                    => $PMF_LANG['msgVoteBad'],
    'msgVoteGood'                   => $PMF_LANG['msgVoteGood'],
    'msgVoteSubmit'                 => $PMF_LANG['msgVoteSubmit'],
    'writeCommentMsg'               => $commentMessage,
    'msgWriteComment'               => $PMF_LANG['msgWriteComment'],
    'writeSendAdress'               => '?'.$sids.'action=savecomment',
    'id'                            => $faq->faqRecord['id'],
    'lang'                          => $lang,
    'msgCommentHeader'              => $PMF_LANG['msgCommentHeader'],
    'msgNewContentName'             => $PMF_LANG['msgNewContentName'],
    'msgNewContentMail'             => $PMF_LANG['msgNewContentMail'],
    'defaultContentMail'            => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('email') : '',
    'defaultContentName'            => ($user instanceof PMF_User_CurrentUser) ? $user->getUserData('display_name') : '',
    'msgYourComment'                => $PMF_LANG['msgYourComment'],
    'msgNewContentSubmit'           => $PMF_LANG['msgNewContentSubmit'],
    'captchaFieldset'               => PMF_Helper_Captcha::getInstance()->renderCaptcha(
        $captcha,
        'writecomment',
        $PMF_LANG['msgCaptcha']
    ),
    'writeComments'                 => $comment->getComments($faq->faqRecord['id'], PMF_Comment::COMMENT_TYPE_FAQ),
    'msg_about_faq'                 => $PMF_LANG['msg_about_faq']
    )
);


$tpl->includeTemplate('writeContent', 'index');
