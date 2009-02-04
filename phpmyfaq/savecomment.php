<?php
/*
 * Saves the posted comment
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since     2002-08-29
 * @version   SVN: $Id$
 * @copyright 2002-2009 phpMyFAQ Team
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

$captcha = new PMF_Captcha($sids);

$id = 0;
$msgWriteComment = $PMF_LANG['msgWriteComment'];

if ((isset($_POST['type']) && ('faq' == $_POST['type'])) && isset($_POST["id"])) {
    $id = (int)$_POST['id'];
} else if ((isset($_POST['type']) && ('news' == $_POST['type'])) && isset($_POST["newsid"])) {
    $id              = (int)$_POST["newsid"];
    $msgWriteComment = $PMF_LANG['newsWriteComment'];
}

if (    isset($_POST['user']) && $_POST['user'] != ''
     && isset($_POST['mail']) && checkEmail($_POST['mail'])
     && isset($_POST['comment']) && $_POST['comment'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['comment'])))
     && checkCaptchaCode()
     && !$faq->commentDisabled($id, $LANGCODE, isset($_POST['type']) ? $_POST['type'] : 'faq')) {

    if ((isset($_POST['type']) && ('faq' == $_POST['type'])) && isset($_POST["id"])) {
        $id = (int)$_POST["id"];
    } else if ((isset($_POST['type']) && ('news' == $_POST['type'])) && isset($_POST["newsid"])) {
        $id = (int)$_POST["newsid"];
        $msgWriteComment = $PMF_LANG['newsWriteComment'];
    }

    $faqsession->userTracking("save_comment", $id);

    $commentData = array(
        'record_id' => $id,
        'type'      => $db->escape_string($_POST['type']),
        'username'  => $db->escape_string(safeHTML($_POST["user"])),
        'usermail'  => $db->escape_string(safeHTML($_POST["mail"])),
        'comment'   => nl2br($db->escape_string(safeHTML($_POST["comment"]))),
        'date'      => $_SERVER['REQUEST_TIME'],
        'helped'    => '');
    if ($faq->addComment($commentData)) {
        $emailTo = $faqconfig->get('main.administrationMail');
        $urlToContent = '';
        if ('faq' == $_POST['type']) {
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
            $oLink = new PMF_Link(PMF_Link::getSystemUri().'?'.$_faqUrl);
            $oLink->itemTitle = $faq->faqRecord['title'];
            $urlToContent = $oLink->toString();
        } elseif ('news' == $_POST['type']) {
            
            $oNews = new PMF_News();
            $news  = $oNews->getNewsEntry($id);
            if ($news['authorEmail'] != '') {
                $emailTo = $news['authorEmail'];
            }
            $oLink = new PMF_Link(PMF_Link::getSystemUri().'?action=news&amp;newsid='.$news['id'].'&amp;newslang='.$news['lang']);
            $oLink->itemTitle = $news['header'];
            $urlToContent = $oLink->toString();
        }
        $commentMail =
            'User: ' . $commentData['username'] . ', mailto:'. $commentData['usermail'] . "\n".
            'New comment posted on: ' . $urlToContent .
            "\n\n" .
            wordwrap($_POST['comment'], 72);

        $mail = new PMF_Mail();
        $mail->unsetFrom();
        $mail->setFrom($commentData['usermail']);
        $mail->addTo($emailTo);
        // Let the category owner get a copy of the message
        if ($emailTo != $faqconfig->get('main.administrationMail')) {
            $mail->addCc($faqconfig->get('main.administrationMail'));
        }
        $mail->subject = '%sitename%';
        $mail->message = strip_tags($commentMail);
        $result = $mail->send();
        unset($mail);

        $tpl->processTemplate(
            'writeContent',
            array(
                'msgCommentHeader'  => $msgWriteComment,
                'Message'           => $PMF_LANG['msgCommentThanks']
            )
        );
    } else {
        $faqsession->userTracking('error_save_comment', $id);
        $tpl->processTemplate(
            'writeContent',
            array(
                'msgCommentHeader'  => $msgWriteComment,
                'Message'           => $PMF_LANG['err_SaveComment']
            )
        );
    }
} else {
    if (!IPCheck($_SERVER['REMOTE_ADDR'])) {
        $tpl->processTemplate(
            'writeContent',
            array(
                'msgCommentHeader'  => $msgWriteComment,
                'Message'           => $PMF_LANG['err_bannedIP']
            )
        );
    } else {
        $faqsession->userTracking('error_save_comment', $id);
        $tpl->processTemplate(
            'writeContent',
            array(
                'msgCommentHeader'  => $msgWriteComment,
                'Message'           => $PMF_LANG['err_SaveComment']
            )
        );
    }
}

$tpl->includeTemplate('writeContent', 'index');
