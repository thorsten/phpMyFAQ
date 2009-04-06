<?php
/**
 * Saves the question of a user
 *
 * @package    phpMyFAQ 
 * @subpackage Frontend
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author     Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author     Jürgen Kuza <kig@bluewin.ch>
 * @since      2002-09-17
 * @version    SVN: $Id$
 * @copyright  2002-2009 phpMyFAQ Team
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

$username = PMF_Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
$usermail = PMF_Filter::filterInput(INPUT_POST, 'usermail', FILTER_VALIDATE_EMAIL);
$usercat  = PMF_Filter::filterInput(INPUT_POST, 'rubrik', FILTER_VALIDATE_INT);
$content  = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_STRIPPED);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$code     = $code ? $code : PMF_Filter::filterInput(INPUT_GET, 'code', FILTER_SANITIZE_STRING);
$domail   = PMF_Filter::filterInput(INPUT_GET, 'domail', FILTER_VALIDATE_INT);
$thankyou = PMF_Filter::filterInput(INPUT_GET, 'thankyou', FILTER_VALIDATE_INT);

function sendAskedQuestion($username, $usermail, $usercat, $content)
{
    global $IDN, $category, $PMF_LANG, $faq, $faqconfig;
    
    $retval = false;
    $cat        = new PMF_Category();
    $categories = $cat->getAllCategories();

    if ($faqconfig->get('records.enableVisibilityQuestions')) {
        $visibility = 'N';
    } else {
        $visibility = 'Y';
    }

    $questionData = array(
        'ask_username'  => $username,
        'ask_usermail'  => $IDN->encode($usermail),
        'ask_category'  => $usercat,
        'ask_content'   => $content,
        'ask_date'      => date('YmdHis'),
        'is_visible'    => $visibility
        );

    list($user, $host) = explode("@", $questionData['ask_usermail']);
    
    if (PMF_Filter::filterVar($questionData['ask_usermail'], FILTER_VALIDATE_EMAIL) != false) {

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
        $retval = $mail->send();
    }
    
    return $retval;
}

if (!is_null($username) && !empty($usermail) && !empty($content) && IPCheck($_SERVER['REMOTE_ADDR']) && 
    checkBannedWord(htmlspecialchars($content)) && $captcha->checkCaptchaCode($code)) {
    	
    $pmf_sw       = PMF_Stopwords::getInstance();
    $search_stuff = $pmf_sw->clean($content);       

    $search        = new PMF_Search();
    $search_result = array();
    foreach ($search_stuff as $word) {
        $search_result[] = searchengine($word);
    }
    
    if ($search_result) {
    
        $tpl->processBlock('writeContent', 'adequateAnswers', array('answers' => $search_result));
        $tpl->processBlock('writeContent', 
                           'messageQuestionFound', 
                           array('BtnText' => $PMF_LANG['msgSendMailDespiteEverything'],
                                 'Message' => $PMF_LANG['msgSendMailIfNothingIsFound'],
                                 'Code'    => $code));
        
        $_SESSION['asked_questions'][$code] = array('username' => $username, 
                                                    'usermail' => $usermail,
                                                    'usercat'  => $usercat,
                                                    'content'  => $content);
    } else {
        
        if (sendAskedQuestion($username, $usermail, $usercat, $content)) {
            header('Location: index.php?action=savequestion&thankyou=1');
            exit;
        }
        
        $tpl->processBlock('writeContent', 'messageSaveQuestion', array('Message' => $PMF_LANG['err_noMailAdress']));
    }

} elseif (null != $domail && null != $code && isset($_SESSION['asked_questions'][$code])) {
    
    extract($_SESSION['asked_questions'][$code]);
    sendAskedQuestion($username, $usermail, $usercat, $content);
    
    unset($_SESSION['asked_questions'][$code]);
    header('Location: index.php?action=savequestion&thankyou=1');
    exit;
} elseif (null != $thankyou) {
    $tpl->processBlock('writeContent', 
        'messageSaveQuestion', array('Message' => $PMF_LANG['msgAskThx4Mail']));
} else {
    if (false === IPCheck($_SERVER['REMOTE_ADDR'])) {
        $message = $PMF_LANG['err_bannedIP'];
    } else {
        $message = $PMF_LANG['err_SaveQuestion'];
    }
    
    $tpl->processBlock('writeContent', 'messageSaveQuestion', array('Message' => $message));
}

$tpl->processTemplate('writeContent', array(
          'msgQuestion' => $PMF_LANG['msgQuestion']));
$tpl->includeTemplate('writeContent', 'index');
