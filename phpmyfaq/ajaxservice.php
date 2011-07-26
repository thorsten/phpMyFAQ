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
        $faqid    = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $newsid   = PMF_Filter::filterInput(INPUT_POST, 'newsid', FILTER_VALIDATE_INT);
        $username = PMF_Filter::filterInput(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
        $mail     = PMF_Filter::filterInput(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
        $comment  = PMF_Filter::filterInput(INPUT_POST, 'comment_text', FILTER_SANITIZE_SPECIAL_CHARS);

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

        if (!is_null($username) && !empty($username) && !empty($mail) && !is_null($mail) && !is_null($comment) &&
            !empty($comment) && checkBannedWord($comment) && !$faq->commentDisabled($id, $languageCode, $type)) {

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

                $send = array();
                $mail = new PMF_Mail();
                $mail->setReplyTo($commentData['usermail'], $commentData['username']);
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

                $mail->subject = '%sitename%';
                $mail->message = strip_tags($commentMail);
                $result = $mail->send();
                unset($mail);

                $message = array('success' => $PMF_LANG['msgCommentThanks']);
            } else {
                $faqsession->userTracking('error_save_comment', $id);
                $message = array('error' => $PMF_LANG['err_SaveComment']);
            }
        } else {
            $message = array('error' => 'Please add your name, your e-mail address and a comment!');
        }
        break;

    case 'savefaq':

        $faq         = new PMF_Faq();
        $category    = new PMF_Category();
        $name        = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email       = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $faqid       = PMF_Filter::filterInput(INPUT_POST, 'faqid', FILTER_VALIDATE_INT);
        $faqlanguage = PMF_Filter::filterInput(INPUT_POST, 'faqlanguage', FILTER_SANITIZE_STRING);
        $question    = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        $answer      = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_STRIPPED);
        $translation = PMF_Filter::filterInput(INPUT_POST, 'translated_answer', FILTER_SANITIZE_STRING);
        $link        = PMF_Filter::filterInput(INPUT_POST, 'contentlink', FILTER_VALIDATE_URL);
        $keywords    = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRIPPED);
        $categories  = PMF_Filter::filterInputArray(INPUT_POST, array(
            'rubrik' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags'  => FILTER_REQUIRE_ARRAY)));

        // Check on translation
        if (is_null($answer) && !is_null($translation)) {
            $answer = $translation;
        }

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) &&
            !is_null($question) && !empty($question) && checkBannedWord(PMF_String::htmlspecialchars($question)) &&
            !is_null($answer) && !empty($answer) && checkBannedWord(PMF_String::htmlspecialchars($answer)) &&
            ((is_null($faqid) && !is_null($categories['rubrik'])) || (!is_null($faqid) && !is_null($faqlanguage) &&
            PMF_Language::isASupportedLanguage($faqlanguage)))) {

            $isNew = true;
            if (!is_null($faqid)) {
                $isNew = false;
                $faqsession->userTracking('save_new_translation_entry', 0);
            } else {
                $faqsession->userTracking('save_new_entry', 0);
            }

            $isTranslation = false;
            if (!is_null($faqlanguage)) {
                $isTranslation = true;
                $newLanguage   = $faqlanguage;
            }

            if (PMF_String::substr($contentlink,7) != "") {
                $answer = sprintf('%s<br />%s<a href="http://%s" target="_blank">%s</a>',
                    $answer,
                    $PMF_LANG['msgInfo'],
                    PMF_String::substr($contentlink,7),
                    $contentlink
                );
            }

            $autoActivate = PMF_Configuration::getInstance()->get('records.defaultActivation');

            $newData = array(
                'lang'          => ($isTranslation == true ? $newLanguage : $languageCode),
                'thema'         => $question,
                'active'        => ($autoActivate ? FAQ_SQL_ACTIVE_YES : FAQ_SQL_ACTIVE_NO),
                'sticky'        => 0,
                'content'       => nl2br($answer),
                'keywords'      => $keywords,
                'author'        => $name,
                'email'         => $email,
                'comment'       => FAQ_SQL_YES,
                'date'          => date('YmdHis'),
                'dateStart'     => '00000000000000',
                'dateEnd'       => '99991231235959',
                'linkState'     => '',
                'linkDateCheck' => 0);

            if ($isNew) {
                $categories = $categories['rubrik'];
            } else {
                $newData['id'] = $faqid;
                $categories    = $category->getCategoryIdsFromArticle($newData['id']);
            }

            $recordId = $faq->addRecord($newData, $isNew);

            $faq->addCategoryRelations($categories, $recordId, $newData['lang']);

            if ($autoActivate) {
                // Activate visits
                $visits = PMF_Visits::getInstance();
                $visits->add($recordId, $newData['lang']);

                // Add user permissions
                $faq->addPermission('user', $recordId, -1);
                $category->addPermission('user', $categories['rubrik'], array(-1));
                // Add group permission
                if ($faqconfig->get('main.permLevel') != 'basic') {
                    $faq->addPermission('group', $recordId, -1);
                    $category->addPermission('group', $categories['rubrik'], array(-1));
                }
            }

            // Let the PMF Administrator and the Category Owner to be informed by email of this new entry
            $send = array();
            $mail = new PMF_Mail();
            $mail->setReplyTo($email, $name);
            $mail->addTo($faqconfig->get('main.administrationMail'));
            $send[$faqconfig->get('main.administrationMail')] = 1;

            foreach ($categories as $_category) {

                $userId = $category->getCategoryUser($_category);

                // @todo Move this code to Category.php
                $oUser = new PMF_User();
                $oUser->getUserById($userId);
                $catOwnerEmail = $oUser->getUserData('email');

                // Avoid to send multiple emails to the same owner
                if (!isset($send[$catOwnerEmail])) {
                    $mail->addCc($catOwnerEmail);
                    $send[$catOwnerEmail] = 1;
                }
            }

            $mail->subject = '%sitename%';

            // @todo let the email contains the faq article both as plain text and as HTML
            $mail->message = html_entity_decode(
                $PMF_LANG['msgMailCheck']) .
                "\n\n" .
                $faqconfig->get('main.titleFAQ') .
                ": " .
                PMF_Link::getSystemRelativeUri('/ajaxservice.php') . 'admin/';
            $result = $mail->send();
            unset($mail);

            $message = array('success' => ($isNew ? $PMF_LANG['msgNewContentThanks'] : $PMF_LANG['msgNewTranslationThanks']));

        } else {
            $message = array('error' => $PMF_LANG['err_SaveEntries']);
        }

        break;

    case 'savequestion':

        $faq        = new PMF_Faq();
        $cat        = new PMF_Category();
        $categories = $cat->getAllCategories();
        $name       = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email      = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $ucategory  = PMF_Filter::filterInput(INPUT_POST, 'category', FILTER_VALIDATE_INT);
        $question   = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        $save       = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_VALIDATE_INT);

        // If e-mail address is set to optional
        if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($email)) {
            $email = PMF_Configuration::getInstance()->get('main.administrationMail');
        }

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) &&
            !is_null($question) && !empty($question) && checkBannedWord(PMF_String::htmlspecialchars($question))) {

            if (1 != $save) {

                $question = PMF_Stopwords::getInstance()->clean($question);

                $user            = new PMF_User_CurrentUser();
                $faqSearch       = new PMF_Search($db, $Language);
                $faqSearchResult = new PMF_Search_Resultset($user, $faq);
                $searchResult    = array();
                $mergedResult    = array();

                foreach ($question as $word) {
                    $searchResult[] = $faqSearch->search($word);
                }
                foreach($searchResult as $resultSet) {
                    foreach($resultSet as $result) {
                        $mergedResult[] = $result;
                    }
                }
                $faqSearchResult->reviewResultset($mergedResult);

                if (0 < $faqSearchResult->getNumberOfResults()) {

                    $response = sprintf('<p>%s</p>',
                        $plr->GetMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults()));

                    $response .= '<ul>';

                    foreach($faqSearchResult->getResultset() as $result) {
                        $url = sprintf('/index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s&amp;highlight=%s',
                            $result->category_id,
                            $result->id,
                            $result->lang,
                            urlencode($result->searchterm));
                        $oLink       = new PMF_Link(PMF_Configuration::getInstance()->get('main.referenceURL') . $url);
                        $oLink->text = PMF_Utils::chopString($result->question, 15);
                        $response   .= sprintf('<li>%s<br /><div class="searchpreview">%s...</div></li>',
                            $oLink->toHtmlAnchor(),
                            PMF_Utils::chopString(strip_tags($result->answer), 10)
                        );
                    }
                    $response .= '</ul>';

                    $message = array('result' => $response);
                } else {
                    $message = array('error' => 'not implemented yet: ' . $save);
                }
                
            } else {

                if (PMF_Configuration::getInstance()->get('records.enableVisibilityQuestions')) {
                    $visibility = 'N';
                } else {
                    $visibility = 'Y';
                }
                $questionData = array(
                    'username'    => $name,
                    'email'       => $email,
                    'category_id' => $ucategory,
                    'question'    => $question,
                    'is_visible'  => $visibility);

                $faq->addQuestion($questionData);

                $questionMail = "User: ".$questionData['username'].", mailto:".$questionData['email']."\n"
                                .$PMF_LANG["msgCategory"].": ".$categories[$questionData['category_id']]["name"]."\n\n"
                                .wordwrap($content, 72);

                $userId = $cat->getCategoryUser($questionData['category_id']);
                $oUser  = new PMF_User();
                $oUser->getUserById($userId);

                $userEmail      = $oUser->getUserData('email');
                $mainAdminEmail = PMF_Configuration::getInstance()->get('main.administrationMail');

                $mail = new PMF_Mail();
                $mail->setReplyTo($questionData['email'], $questionData['username']);
                $mail->addTo($mainAdminEmail);
                // Let the category owner get a copy of the message
                if ($userEmail && $mainAdminEmail != $userEmail) {
                    $mail->addCc($userEmail);
                }
                $mail->subject = '%sitename%';
                $mail->message = $questionMail;
                $mail->send();
                unset($mail);

                $message = array('success' => $PMF_LANG['msgAskThx4Mail']);
            }

        } else {
            $message = array('error' => $PMF_LANG['err_SaveQuestion']);
        }

        break;

    case 'saveregistration':

        $realname  = PMF_Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING);
        $loginname = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email     = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!is_null($loginname) && !empty($loginname) && !is_null($email) && !empty($email) &&
            !is_null($realname) && !empty($realname)) {

            $message = array();
            $user    = new PMF_User();
            $user->setLoginMinLength(4);

            // check login name
            if (!$user->isValidLogin($loginname)) {
                $message = array('error' => $PMF_LANG['ad_user_error_loginInvalid']);
            }
            if ($user->getUserByLogin($loginname)) {
                $message = array('error' => $PMF_LANG['ad_adus_exerr']);
            }

            // ok, let's go
            if (count($message) == 0) {
                // Create user account (login and password)
                // Note: password be automatically generated and sent by email as soon if admin switch user to "active"
                if (!$user->createUser($user_name, '')) {
                    $message = array('error' => $user->error());
                } else {
                    $user->userdata->set(
                        array('display_name', 'email'),
                        array($realname, $email)
                    );
                    // set user status
                    $user->setStatus('blocked');

                    $text = sprintf("New user has been registrated:\n\nUsername: %s\nLoginname: %s\n\n" .
                                    "To activate this user do please use the administration interface.",
                                    $realname,
                                    $loginname);

                    $mail = new PMF_Mail();
                    $mail->setReplyTo($email, $realname);
                    $mail->addTo($faqconfig->get('main.administrationMail'));
                    $mail->subject = PMF_Utils::resolveMarkers($PMF_LANG['emailRegSubject']);
                    $mail->message = $text;
                    $result = $mail->send();
                    unset($mail);

                    $message = array('success' => $PMF_LANG['successMessage'] . $PMF_LANG['msgRegThankYou']);
                }
            }
            
        } else {
            $message = array('error' => $PMF_LANG['err_sendMail']);
        }
        break;

    case 'savevoting':

        $faq      = new PMF_Faq();
        $type     = PMF_Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING, 'faq');
        $recordId = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $vote     = PMF_Filter::filterInput(INPUT_POST, 'vote', FILTER_VALIDATE_INT);
        $userIp   = PMF_Filter::filterVar($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

        if (isset($vote) && $faq->votingCheck($recordId, $userIp) && $vote > 0 && $vote < 6) {
            $faqsession->userTracking('save_voting', $recordId);

            $votingData = array(
                'record_id' => $recordId,
                'vote'      => $vote,
                'user_ip'   => $userIp);

            if (!$faq->getNumberOfVotings($recordId)) {
                $faq->addVoting($votingData);
            }  else {
                $faq->updateVoting($votingData);
            }
            $message = array('success' => $PMF_LANG['msgVoteThanks']);
        } elseif (!$faq->votingCheck($recordId, $userIp)) {
            $faqsession->userTracking('error_save_voting', $recordId);
            $message = array('error' => $PMF_LANG['err_VoteTooMuch']);

        } else {
            $faqsession->userTracking('error_save_voting', $recordId);
            $message = array('error' => $PMF_LANG['err_noVote']);
        }

        break;

    // Send user generated mails
    case 'sendcontact':

        $name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $question = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);

        // If e-mail address is set to optional
        if (!PMF_Configuration::getInstance()->get('main.optionalMailAddress') && is_null($email)) {
            $email = PMF_Configuration::getInstance()->get('main.administrationMail');
        }

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) && !is_null($question) &&
            !empty($question) && checkBannedWord(PMF_String::htmlspecialchars($question))) {

            $mail = new PMF_Mail();
            $mail->setReplyTo($email, $name);
            $mail->addTo($faqconfig->get('main.administrationMail'));
            $mail->subject = 'Feedback: %sitename%';;
            $mail->message = $question;
            $result = $mail->send();
            unset($mail);

            $message = array('success' => $PMF_LANG['msgMailContact']);

        } else {
            $message = array('error' => $PMF_LANG['err_sendMail']);
        }
        break;

    // Send mails to friends
    case 'sendtofriends':

        $name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $link     = PMF_Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
        $attached = PMF_Filter::filterInput(INPUT_POST, 'message', FILTER_SANITIZE_STRIPPED);
        $mailto   = PMF_Filter::filterInputArray(INPUT_POST,
            array('mailto' =>
                array('filter' => FILTER_VALIDATE_EMAIL,
                      'flags'  => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE
                )
            )
        );

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) &&
            is_array($mailto) && !empty($mailto[0]) && checkBannedWord(PMF_String::htmlspecialchars($attached))) {

            foreach($mailto['mailto'] as $recipient) {
                $recipient = trim(strip_tags($recipient));
                if (!empty($recipient)) {
                    $mail = new PMF_Mail();
                    $mail->setReplyTo($email, $name);
                    $mail->addTo($recipient);
                    $mail->subject = $PMF_LANG["msgS2FMailSubject"].$name;
                    $mail->message = sprintf("%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $faqconfig->get('main.send2friendText'),
                        $PMF_LANG['msgS2FText2'],
                        $link,
                        $attached);

                    // Send the email
                    $result = $mail->send();
                    unset($mail);
                    usleep(250);
                }
            }

            $message = array('success' => $PMF_LANG['msgS2FThx']);
        } else {
            $message = array('error' => $PMF_LANG['err_sendMail']);
        }
        break;
    
}

print json_encode($message);