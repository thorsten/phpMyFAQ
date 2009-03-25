<?php
/**
 * TODO: Short description.
 *
 * @package   phpMyFAQ 
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    David Saez Padros <david@ols.es>
 * @author    Jürgen Kuza <kig@bluewin.ch>
 * @since     2002-09-17
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

$code = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);

if (
        isset($_POST['username']) && $_POST['username'] != ''
     && isset($_POST['usermail']) && checkEmail($_POST['usermail'])
     && isset($_POST['content']) && $_POST['content'] != ''
     && IPCheck($_SERVER['REMOTE_ADDR'])
     && checkBannedWord(htmlspecialchars(strip_tags($_POST['content'])))
     && $captcha->checkCaptchaCode($code)
     ) {
    if (isset($_POST['try_search'])) {
        $suchbegriff = strip_tags($_POST['content']);
        $printResult = searchEngine($suchbegriff, $numr);
        echo $numr;
    } else {
        $numr = 0;
    }

    if ($numr == 0) {
        $cat        = new PMF_Category();
        $categories = $cat->getAllCategories();

        if ($faqconfig->get('records.enableVisibilityQuestions')) {
            $visibility = 'N';
        } else {
            $visibility = 'Y';
        }

        $content = strip_tags($_POST['content']);
        $questionData = array(
            'ask_username'  => strip_tags($_POST['username']),
            'ask_usermail'  => $IDN->encode($_POST['usermail']),
            'ask_category'  => intval($_POST['rubrik']),
            'ask_content'   => $content,
            'ask_date'      => date('YmdHis'),
            'is_visible'    => $visibility
            );

        list($user, $host) = explode("@", $questionData['ask_usermail']);
        if (checkEmail($questionData['ask_usermail'])) {

            $faq->addQuestion($questionData);

            $questionMail = "User: ".$questionData['ask_username'].", mailto:".$questionData['ask_usermail']."\n"
                            .$PMF_LANG["msgCategory"].": ".$categories[$questionData['ask_category']]["name"]."\n\n"
                            .wordwrap($content, 72);

            $userId = $category->getCategoryUser($questionData['ask_category']);
            $oUser = new PMF_User();
            $oUser->getUserById($userId);

            $mail = new PMF_Mail();
            $mail->unsetFrom();
            $mail->setFrom($questionData['ask_usermail'], $questionData['ask_username']);
            $mail->addTo($faqconfig->get('main.administrationMail'));
            // Let the category owner get a copy of the message
            if ($faqconfig->get('main.administrationMail') != $oUser->getUserData('email')) {
                $mail->addCc($oUser->getUserData('email'));
            }
            $mail->subject = '%sitename%';
            $mail->message = $questionMail;
            $result = $mail->send();
            unset($mail);

            $tpl->processTemplate(
                'writeContent',
                array(
                    'msgQuestion'   => $PMF_LANG['msgQuestion'],
                    'Message'       => $PMF_LANG['msgAskThx4Mail']
                )
            );
        } else {
            $tpl->processTemplate(
                'writeContent',
                array(
                    'msgQuestion'   => $PMF_LANG['msgQuestion'],
                    'Message'       => $PMF_LANG['err_noMailAdress']
                )
            );
        }
    } else {
        $tpl->templates['writeContent'] = $tpl->readTemplate('template/asksearch.tpl');
        $tpl->processTemplate (
            'writeContent',
            array(
                'msgQuestion'           => $PMF_LANG['msgQuestion'],
                'printResult'           => $printResult,
                'msgAskYourQuestion'    => $PMF_LANG['msgAskYourQuestion'],
                'msgContent'            => $questionData['ask_content'],
                'postUsername'          => urlencode($questionData['ask_username']),
                'postUsermail'          => urlencode($questionData['ask_usermail']),
                'postRubrik'            => urlencode($questionData['ask_category']),
                'postContent'           => urlencode($questionData['ask_content']),
                'writeSendAdress'       => $_SERVER['PHP_SELF'].'?'.$sids.'action=savequestion',
            )
        );
    }
} else {
    if (false === IPCheck($_SERVER['REMOTE_ADDR'])) {
        $tpl->processTemplate(
            'writeContent',
            array(
                'msgQuestion'   => $PMF_LANG['msgQuestion'],
                'Message'       => $PMF_LANG['err_bannedIP']
            )
        );
    } else {
        $tpl->processTemplate(
            'writeContent',
            array(
                'msgQuestion'   => $PMF_LANG['msgQuestion'],
                'Message'       => $PMF_LANG['err_SaveQuestion']
            )
        );
    }
}

$tpl->includeTemplate('writeContent', 'index');
