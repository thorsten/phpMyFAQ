<?php
/**
 * Saves the posted comment
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
 * @copyright phpMyFAQ
 * @package   Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2002-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2002-08-29
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

$type    = PMF_Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
$code    = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$faqid   = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
$newsid  = PMF_Filter::filterInput(INPUT_POST, 'newsid', FILTER_VALIDATE_INT);
$user    = PMF_Filter::filterInput(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
$mail    = PMF_Filter::filterInput(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
$comment = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRIPPED);
$message = '';

switch ($type) {
    case 'news':
        $id              = $newsid;
        $msgWriteComment = $PMF_LANG['newsWriteComment'];
        break;
    case 'faq';
    default:
        $id = $faqid;
        $msgWriteComment = $PMF_LANG['msgWriteComment'];
        break;
}

// If e-mail address is set to optional
if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($mail)) {
    $mail = PMF_Configuration::getInstance()->get('main.administrationMail');
}

if (!is_null($user) && !is_null($mail) && !is_null($comment) && checkBannedWord(PMF_String::htmlspecialchars($comment)) &&
    IPCheck($_SERVER['REMOTE_ADDR']) && $captcha->checkCaptchaCode($code) && !$faq->commentDisabled($id, $LANGCODE, $type)) {

    $faqsession->userTracking("save_comment", $id);

    $commentData = array(
        'record_id' => $id,
        'type'      => $type,
        'username'  => $user,
        'usermail'  => $mail,
        'comment'   => nl2br($comment),
        'date'      => $_SERVER['REQUEST_TIME'],
        'helped'    => '');
    if ($faq->addComment($commentData)) {
        $emailTo = $faqconfig->get('main.administrationMail');
        $urlToContent = '';
        if ('faq' == $type) {
            $faq->getRecord($id);
            if ($faq->faqRecord['email'] != '') {
                $emailTo = $faq->faqRecord['email'];
            }
            $_faqUrl = sprintf(
                '%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                $sids,
                0,
                $faq->faqRecord['id'],
                $faq->faqRecord['lang']
            );
            $oLink            = new PMF_Link(PMF_Link::getSystemUri().'?'.$_faqUrl);
            $oLink->itemTitle = $faq->faqRecord['title'];
            $urlToContent     = $oLink->toString();
        } else {
            
            $oNews = new PMF_News();
            $news  = $oNews->getNewsEntry($id);
            if ($news['authorEmail'] != '') {
                $emailTo = $news['authorEmail'];
            }
            $oLink            = new PMF_Link(PMF_Link::getSystemUri().'?action=news&amp;newsid='.$news['id'].'&amp;newslang='.$news['lang']);
            $oLink->itemTitle = $news['header'];
            $urlToContent     = $oLink->toString();
        }
        $commentMail =
            'User: ' . html_entity_decode($commentData['username'], ENT_QUOTES) . ', mailto:'. $commentData['usermail'] . "\n".
            'New comment posted on: ' . html_entity_decode($urlToContent, ENT_QUOTES) .
            "\n\n" .
            wordwrap(html_entity_decode($comment, ENT_QUOTES), 72);

        $send = array();
        $mail = new PMF_Mail();
        $mail->unsetFrom();
        $mail->setFrom($commentData['usermail'], html_entity_decode($commentData['username'], ENT_QUOTES));
        $mail->addTo($emailTo);
        $send[$emailTo] = 1;

        // Let the admin get a copy of the message
        if (!isset($send[$faqconfig->get('main.administrationMail')])) {
            $mail->addCc($faqconfig->get('main.administrationMail'));
            $send[$faqconfig->get('main.administrationMail')] = 1;
        }

        // Let the category owner get a copy of the message
        $category = new PMF_Category();
        $categories = $category->getCategoryIdsFromArticle($faq->faqRecord['id']);
        foreach ($categories as $_category) {
            $userId = $category->getCategoryUser($_category);
            $catUser = new PMF_User();
            $catUser->getUserById($userId);
            $catOwnerEmail = $catUser->getUserData('email');

            if ($catOwnerEmail != '') {
                if (!isset($send[$catOwnerEmail])) {
                    $mail->addCc($catOwnerEmail);
                    $send[$catOwnerEmail] = 1;
                }
            }
        }

        $mail->subject = '%sitename% Comment';
        $mail->message = $commentMail;
        $result = $mail->send();
        unset($mail);

        $message = $PMF_LANG['msgCommentThanks'];
    } else {
        $faqsession->userTracking('error_save_comment', $id);
        $message = $PMF_LANG['err_SaveComment'];
    }
} else {
    if (!IPCheck($_SERVER['REMOTE_ADDR'])) {
        $message = $PMF_LANG['err_bannedIP'];
    } else {
        $faqsession->userTracking('error_save_comment', $id);
        $message = $PMF_LANG['err_SaveComment'];
    }
}

$tpl->processTemplate('writeContent',
                      array('msgCommentHeader'  => $msgWriteComment,
                            'Message'           => $message));

$tpl->includeTemplate('writeContent', 'index');
