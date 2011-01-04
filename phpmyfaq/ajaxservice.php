<?php
/**
 * The Ajax Service Layer
 *
 * PHP Version 5.2.3
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
 * @package   Ajax 
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-15
 */

define('IS_VALID_PHPMYFAQ', null);

//
// Prepend and start the PHP session
//
require_once 'inc/Init.php';
define('IS_VALID_PHPMYFAQ', null);
PMF_Init::cleanRequest();
session_name(PMF_COOKIE_NAME_AUTH . trim($faqconfig->get('main.phpMyFAQToken')));
session_start();

$action   = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$ajaxlang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);


$language     = new PMF_Language();
$languageCode = $language->setLanguage($faqconfig->get('main.languageDetection'), $faqconfig->get('main.language'));
require_once 'lang/language_en.php';

if (PMF_Language::isASupportedLanguage($ajaxlang)) {
    $languageCode = trim($ajaxlang);
    require_once 'lang/language_' . $languageCode . '.php';
} else {
    $languageCode = 'en';
    require_once 'lang/language_en.php';
}

//Load plurals support for selected language
$plr = new PMF_Language_Plurals($PMF_LANG);

//
// Initalizing static string wrapper
//
PMF_String::init($languageCode);

// Check captcha
$captcha = new PMF_Captcha($db, $Language);
$captcha->setSessionId($sids);

// Send headers
$http = PMF_Helper_Http::getInstance();
$http->setContentType('application/json');
$http->addHeader();

// Set session
$faqsession = new PMF_Session();

if (!IPCheck($_SERVER['REMOTE_ADDR'])) {
    $message = array('error' => $PMF_LANG['err_bannedIP']);
}
if (!$captcha->checkCaptchaCode($code)) {
    $message = array('error' => $PMF_LANG['err_SaveComment']);
}

// Save user generated content
switch ($action) {

    // Comments
    case 'savecomment':

        $faq      = new PMF_Faq();
        $type     = PMF_Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
        $faqid    = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $newsid   = PMF_Filter::filterInput(INPUT_POST, 'newsid', FILTER_VALIDATE_INT);
        $username = PMF_Filter::filterInput(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
        $mail     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $comment  = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);

        switch ($type) {
            case 'news':
                $id = $newsid;
                break;
            case 'faq';
                $id = $faqid;
                break;
        }

        // If e-mail address is set to optional
        if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($mail)) {
            $mail = PMF_Configuration::getInstance()->get('main.administrationMail');
        }

        if (!is_null($username) && !is_null($mail) && !is_null($comment) && checkBannedWord($comment) &&
            !$faq->commentDisabled($id, $languageCode, $type)) {

            $faqsession->userTracking("save_comment", $id);
            $commentData = array(
                'record_id' => $id,
                'type'      => $type,
                'username'  => $username,
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
                    'User: ' . $commentData['username'] . ', mailto:'. $commentData['usermail'] . "\n".
                    'New comment posted on: ' . $urlToContent .
                    "\n\n" .
                    wordwrap($comment, 72);

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

                $message = array('success' => $PMF_LANG['msgCommentThanks']);
            } else {
                $faqsession->userTracking('error_save_comment', $id);
                $message = array('error' => $PMF_LANG['err_SaveComment']);
            }
        }
        break;

    case 'savefaq':
        
        break;
    case 'savequestion':
        
        break;
    case 'saveregistration':
        
        break;
    case 'savevoting':
        
        break;
    // Send user generated mails
    case 'sendcontact':
        
        break;
    case 'sendtofriends':
        
        break;
}

print json_encode($message);