<?php
/**
* $Id: savecomment.php,v 1.17 2006-08-30 05:30:25 thorstenr Exp $
*
* Saves the posted comment
*
* @author       Thorsten Rinne <thorsten@phpmyfaq.de>
* @since        2002-08-29
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
    header('Location: http://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$captcha = new PMF_Captcha($db, $sids, $pmf->language);

$id = 0;
$msgWriteComment = $PMF_LANG['msgWriteComment'];

if (    isset($_POST['user']) && $_POST['user'] != ''
     && isset($_POST['mail']) && checkEmail($_POST['mail'])
     && isset($_POST['comment']) && $_POST['comment'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['comment'])))
     && checkCaptchaCode() ) {

    if ((isset($_POST['type']) && ('faq' == $_POST['type'])) && isset($_POST["id"])) {
        $id = (int)$_POST["id"];
    } else if ((isset($_POST['type']) && ('news' == $_POST['type'])) && isset($_POST["newsid"])) {
        $id = (int)$_POST["newsid"];
        $msgWriteComment = $PMF_LANG['newsWriteComment'];
    }

    Tracking("save_comment", $id);

    $commentData = array(
        'record_id' => $id,
        'type'      => $db->escape_string($_POST['type']),
        'username'  => $db->escape_string(safeHTML($_POST["user"])),
        'usermail'  => $db->escape_string(safeHTML($_POST["mail"])),
        'comment'   => nl2br($db->escape_string(safeHTML($_POST["comment"]))),
        'date'      => time(),
        'helped'    => '');
    if ($faq->addComment($commentData)) {
        $emailTo = $PMF_CONF['adminmail'];
        $urlToContent = '';
        if ('faq' == $_POST['type']) {
            $faq->getRecord($id);
            if ($faq->faqRecord['email'] != '') {
                $emailTo = $faq->faqRecord['email'];
            }
            $_faqUrl = sprintf('%saction=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $sids,
                            0,
                            $faq->faqRecord['id'],
                            $faq->faqRecord['lang']
                            );
            $oLink = new PMF_Link(PMF_Link::getSystemUri().'?'.$_faqUrl);
            $oLink->itemTitle = $faq->faqRecord['title'];
            $urlToContent = $oLink->toString();
        } elseif ('news' == $_POST['type']) {
            class_exists('PMF_News') || require('inc/News.php');

            $oNews = new PMF_News($db, $LANGCODE);
            $news = $oNews->getNewsEntry($id);
            if ($news['authorEmail'] != '') {
                $emailTo = $news['authorEmail'];
            }
            $oLink = new PMF_Link(PMF_Link::getSystemUri().'?action=news&amp;newsid='.$news['id'].'&amp;newslang='.$news['lang']);
            $oLink->itemTitle = $news['header'];
            $urlToContent = $oLink->toString();
        }
        $commentMail =  "User: ".$commentData['username'].", mailto:".$commentData['usermail']."\n".
                        "New comment posted on: ".$urlToContent.
                        "\n\n".
                        wordwrap($_POST["comment"], 72);

        $additional_header = array();
        $additional_header[] = 'MIME-Version: 1.0';
        $additional_header[] = 'Content-Type: text/plain; charset='. $PMF_LANG['metaCharset'];
        if (strtolower($PMF_LANG['metaCharset']) == 'utf-8') {
            $additional_header[] = 'Content-Transfer-Encoding: 8bit';
        }
        $additional_header[] = 'From: '.'<'.$IDN->encode($commentData['usermail']).'>';
        // Let the admin always get a copy
        if ($emailTo != $PMF_CONF['adminmail']) {
            $additional_header[] = 'Cc: '.'<'.$IDN->encode($PMF_CONF['adminmail']).'>';
        }
        $body = strip_tags($commentMail);
        $body = str_replace(array("\r\n", "\r", "\n"), "\n", $body);
        $body = str_replace(array("\r\n", "\r", "\n"), "\n", $body);
        if (strstr(PHP_OS, 'WIN') !== NULL) {
            // if windows, cr must "\r\n". if other must "\n".
            $body = str_replace("\n", "\r\n", $body);
        }
        // Send the email
        mail($IDN->encode($emailTo), $PMF_CONF['title'], $body, implode("\r\n", $additional_header), '-f'.$IDN->encode($commentData['usermail']));

        $tpl->processTemplate ("writeContent", array(
                                                "msgCommentHeader"  => $msgWriteComment,
                                                "Message"           => $PMF_LANG["msgCommentThanks"]
                                                )
        );
    } else {
        Tracking("error_save_comment", $id);
        $tpl->processTemplate ("writeContent", array(
                                                "msgCommentHeader"  => $msgWriteComment,
                                                "Message"           => $PMF_LANG["err_SaveComment"]
                                                )
        );
    }
} else {
    if (!IPCheck($_SERVER["REMOTE_ADDR"])) {
        $tpl->processTemplate ("writeContent", array(
                                                "msgCommentHeader"  => $msgWriteComment,
                                                "Message"           => $PMF_LANG["err_bannedIP"]
                                                )
        );
    } else {
        Tracking("error_save_comment", $id);
        $tpl->processTemplate ("writeContent", array(
                                                "msgCommentHeader"  => $msgWriteComment,
                                                "Message"           => $PMF_LANG["err_SaveComment"]
                                                )
        );
    }
}

$tpl->includeTemplate("writeContent", "index");
