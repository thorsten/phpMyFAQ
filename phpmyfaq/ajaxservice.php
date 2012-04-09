<?php
/**
 * The Ajax Service Layer
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Ajax 
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2010-09-15
 */

define('IS_VALID_PHPMYFAQ', null);

//
// Prepend and start the PHP session
//
require 'inc/Bootstrap.php';
PMF_Init::cleanRequest();
session_name(PMF_Session::PMF_COOKIE_NAME_AUTH);
session_start();

$action   = PMF_Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$ajaxlang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
$code     = PMF_Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);

$Language     = new PMF_Language();
$languageCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

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
$captcha = new PMF_Captcha($faqConfig);
//$captcha->setSessionId($sids);

// Send headers
$http = PMF_Helper_Http::getInstance();
$http->setContentType('application/json');
$http->addHeader();

// Set session
$faqsession = new PMF_Session($faqConfig);
$network    = new PMF_Network($faqConfig);
$stopwords  = PMF_Stopwords::getInstance($faqConfig);

if (!$network->checkIp($_SERVER['REMOTE_ADDR'])) {
    $message = array('error' => $PMF_LANG['err_bannedIP']);
}

if ('savevoting' !== $action && 'saveuserdata' !== $action && 'changepassword' !== $action &&
    !$captcha->checkCaptchaCode($code)) {
    $message = array('error' => $PMF_LANG['msgCaptcha']);
}

if (isset($message['error'])) {
    print json_encode($message);
    exit();
}

// Save user generated content
switch ($action) {

    // Comments
    case 'savecomment':

        // @todo add check on "addcomment" permission

        $faq      = new PMF_Faq($faqConfig);
        $oComment = new PMF_Comment($faqConfig);
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
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($mail)) {
            $mail = $faqConfig->get('main.administrationMail');
        }

        if (!is_null($username) && !empty($username) && !empty($mail) && !is_null($mail) && !is_null($comment) &&
            !empty($comment) && $stopwords->checkBannedWord($comment) && !$faq->commentDisabled($id, $languageCode, $type)) {

            $faqsession->userTracking("save_comment", $id);
            $commentData = array(
                'record_id' => $id,
                'type'      => $type,
                'username'  => $username,
                'usermail'  => $mail,
                'comment'   => nl2br($comment),
                'date'      => $_SERVER['REQUEST_TIME'],
                'helped'    => '');

            if ($oComment->addComment($commentData)) {
                $emailTo = $faqConfig->get('main.administrationMail');
                $urlToContent = '';
                if ('faq' == $type) {
                    $faq->getRecord($id);
                    if ($faq->faqRecord['email'] != '') {
                        $emailTo = $faq->faqRecord['email'];
                    }
                    $faqUrl = sprintf(
                        '%s?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                        $faqConfig->get('main.referenceURL'),
                        0,
                        $faq->faqRecord['id'],
                        $faq->faqRecord['lang']
                    );
                    $
                    $oLink            = new PMF_Link($faqUrl, $faqConfig);
                    $oLink->itemTitle = $faq->faqRecord['title'];
                    $urlToContent     = $oLink->toString();
                } else {

                    $oNews = new PMF_News($faqConfig);
                    $news  = $oNews->getNewsEntry($id);
                    if ($news['authorEmail'] != '') {
                        $emailTo = $news['authorEmail'];
                    }
                    $link = sprintf('%s?action=news&amp;newsid=%d&amp;newslang=%s',
                        $faqConfig->get('main.referenceURL'),
                        $news['id'],
                        $news['lang']
                    );
                    $oLink            = new PMF_Link($link, $faqConfig);
                    $oLink->itemTitle = $news['header'];
                    $urlToContent     = $oLink->toString();
                }
                
                $commentMail =
                    'User: ' . $commentData['username'] . ', mailto:'. $commentData['usermail'] . "\n".
                    'New comment posted on: ' . $urlToContent .
                    "\n\n" .
                    wordwrap($comment, 72);

                $send = array();
                $mail = new PMF_Mail($faqConfig);
                $mail->setReplyTo($commentData['usermail'], $commentData['username']);
                $mail->addTo($emailTo);
                $send[$emailTo] = 1;

                // Let the admin get a copy of the message
                if (!isset($send[$faqConfig->get('main.administrationMail')])) {
                    $mail->addCc($faqConfig->get('main.administrationMail'));
                    $send[$faqConfig->get('main.administrationMail')] = 1;
                }

                // Let the category owner get a copy of the message
                $category = new PMF_Category($faqConfig);
                $categories = $category->getCategoryIdsFromArticle($faq->faqRecord['id']);
                foreach ($categories as $_category) {
                    $userId = $category->getCategoryUser($_category);
                    $catUser = new PMF_User($faqConfig);
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

        // @todo add check on "addfaq" permission

        $faq         = new PMF_Faq($faqConfig);
        $category    = new PMF_Category($faqConfig);
        $name        = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email       = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $faqid       = PMF_Filter::filterInput(INPUT_POST, 'faqid', FILTER_VALIDATE_INT);
        $faqlanguage = PMF_Filter::filterInput(INPUT_POST, 'faqlanguage', FILTER_SANITIZE_STRING);
        $question    = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        $answer      = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_STRIPPED);
        $translation = PMF_Filter::filterInput(INPUT_POST, 'translated_answer', FILTER_SANITIZE_STRING);
        $contentlink = PMF_Filter::filterInput(INPUT_POST, 'contentlink', FILTER_VALIDATE_URL);
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
            !is_null($question) && !empty($question) && $stopwords->checkBannedWord(PMF_String::htmlspecialchars($question)) &&
            !is_null($answer) && !empty($answer) && $stopwords->checkBannedWord(PMF_String::htmlspecialchars($answer)) &&
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

            if (PMF_String::substr($contentlink, 7) != "") {
                $answer = sprintf(
                    '%s<br /><div id="newFAQContentLink">%s<a href="http://%s" target="_blank">%s</a></div>',
                    $answer,
                    $PMF_LANG['msgInfo'],
                    PMF_String::substr($contentlink, 7),
                    $contentlink
                );
            }

            $autoActivate = $faqConfig->get('records.defaultActivation');

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

            $openQuestionId = PMF_Filter::filterInput(INPUT_POST, 'openQuestionID', FILTER_VALIDATE_INT);
            if ($openQuestionId) {
                if ($faqConfig->get('records.enableDeleteQuestion')) {
                    $faq->deleteQuestion($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $faq->updateQuestionAnswer($openQuestionId, $recordId, $categories[0]);
                }
            }

            // Activate visits
            $visits = PMF_Visits::getInstance($faqConfig);
            $visits->add($recordId, $newData['lang']);

            if ($autoActivate) {
                // Add user permissions
                $faq->addPermission('user', $recordId, -1);
                $category->addPermission('user', $categories['rubrik'], array(-1));
                // Add group permission
                if ($faqConfig->get('security.permLevel') != 'basic') {
                    $faq->addPermission('group', $recordId, -1);
                    $category->addPermission('group', $categories['rubrik'], array(-1));
                }
            }

            // Let the PMF Administrator and the Category Owner to be informed by email of this new entry
            $send = array();
            $mail = new PMF_Mail($faqConfig);
            $mail->setReplyTo($email, $name);
            $mail->addTo($faqConfig->get('main.administrationMail'));
            $send[$faqConfig->get('main.administrationMail')] = 1;

            foreach ($categories as $_category) {

                $userId = $category->getCategoryUser($_category);

                // @todo Move this code to Category.php
                $oUser = new PMF_User($faqConfig);
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
                $PMF_LANG['msgMailCheck']) . "\n\n" .
                $faqConfig->get('main.titleFAQ') . ": " .
                $faqConfig->get('main.referenceURL') . '/admin/';
            $result = $mail->send();
            unset($mail);

            $message = array(
                'success' => ($isNew ? $PMF_LANG['msgNewContentThanks'] : $PMF_LANG['msgNewTranslationThanks'])
            );

        } else {
            $message = array('error' => $PMF_LANG['err_SaveEntries']);
        }

        break;

    case 'savequestion':

        // @todo add check on "addquestion" permission

        $faq        = new PMF_Faq($faqConfig);
        $cat        = new PMF_Category($faqConfig);
        $categories = $cat->getAllCategories();
        $name       = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email      = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $ucategory  = PMF_Filter::filterInput(INPUT_POST, 'category', FILTER_VALIDATE_INT);
        $question   = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        $save       = PMF_Filter::filterInput(INPUT_POST, 'save', FILTER_VALIDATE_INT, 0);

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->get('main.administrationMail');
        }

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) &&
            !is_null($question) && !empty($question) && $stopwords->checkBannedWord(PMF_String::htmlspecialchars($question))) {

            if ($faqConfig->get('records.enableVisibilityQuestions')) {
                $visibility = 'N';
            } else {
                $visibility = 'Y';
            }

            if (1 != $save) {

                $cleanQuestion = PMF_Stopwords::getInstance($faqConfig)->clean($question);

                $user            = new PMF_User_CurrentUser($faqConfig);
                $faqSearch       = new PMF_Search($faqConfig);
                $faqSearchResult = new PMF_Search_Resultset($user, $faq, $faqConfig);
                $searchResult    = array();
                $mergedResult    = array();

                foreach ($cleanQuestion as $word) {
                    $searchResult[] = $faqSearch->search($word);
                }
                foreach ($searchResult as $resultSet) {
                    foreach($resultSet as $result) {
                        $mergedResult[] = $result;
                    }
                }
                $faqSearchResult->reviewResultset($mergedResult);

                if (0 < $faqSearchResult->getNumberOfResults()) {

                    $response = sprintf('<p>%s</p>',
                        $plr->GetMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults()));

                    $response .= '<ul>';

                    foreach ($faqSearchResult->getResultset() as $result) {
                        $url = sprintf(
                            '%s/index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $faqConfig->get('main.referenceURL'),
                            $result->category_id,
                            $result->id,
                            $result->lang
                        );
                        $oLink       = new PMF_Link($url, $faqConfig);
                        $oLink->text = PMF_Utils::chopString($result->question, 15);
                        $oLink->itemTitle = $result->question;
                        $response   .= sprintf('<li>%s<br /><div class="searchpreview">%s...</div></li>',
                            $oLink->toHtmlAnchor(),
                            PMF_Utils::chopString(strip_tags($result->answer), 10)
                        );
                    }
                    $response .= '</ul>';

                    $message = array('result' => $response);
                    
                } else {

                    $questionData = array(
                        'username'    => $name,
                        'email'       => $email,
                        'category_id' => $ucategory,
                        'question'    => $question,
                        'is_visible'  => $visibility);

                    $faq->addQuestion($questionData);

                    $questionMail = "User: " . $questionData['username'] .
                                ", mailto:".$questionData['email'] . "\n" . $PMF_LANG["msgCategory"] .
                                ": " . $categories[$questionData['category_id']]["name"] . "\n\n" .
                                wordwrap($question, 72) . "\n\n" .
                                $faqConfig->get('main.referenceURL') . '/admin/';

                    $userId = $cat->getCategoryUser($questionData['category_id']);
                    $oUser  = new PMF_User($faqConfig);
                    $oUser->getUserById($userId);

                    $userEmail      = $oUser->getUserData('email');
                    $mainAdminEmail = $faqConfig->get('main.administrationMail');

                    $mail = new PMF_Mail($faqConfig);
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

                $questionData = array(
                    'username'    => $name,
                    'email'       => $email,
                    'category_id' => $ucategory,
                    'question'    => $question,
                    'is_visible'  => $visibility);

                $faq->addQuestion($questionData);

                $questionMail = "User: " . $questionData['username'] .
                                ", mailto:".$questionData['email'] . "\n" . $PMF_LANG["msgCategory"] .
                                ": " . $categories[$questionData['category_id']]["name"] . "\n\n" .
                                wordwrap($question, 72) . "\n\n" .
                                $faqConfig->get('main.referenceURL') . '/admin/';

                $userId = $cat->getCategoryUser($questionData['category_id']);
                $oUser  = new PMF_User($faqConfig);
                $oUser->getUserById($userId);

                $userEmail      = $oUser->getUserData('email');
                $mainAdminEmail = $faqConfig->get('main.administrationMail');

                $mail = new PMF_Mail($faqConfig);
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
            $user    = new PMF_User($faqConfig);
            $user->setLoginMinLength(4);

            // Create user account (login and password)
            // Note: password be automatically generated and sent by email as soon if admin switch user to "active"
            if (!$user->createUser($loginname, '')) {
                $message = array('error' => $user->error());
            } else {
                $user->userdata->set(
                    array('display_name', 'email'),
                    array($realname, $email)
                );
                // set user status
                $user->setStatus('blocked');

                $text = sprintf(
                    "New user has been registrated:\n\nName: %s\nLogin name: %s\n\n" .
                    "To activate this user do please use the administration interface at %s.",
                    $realname,
                    $loginname,
                    $faqConfig->get('main.referenceURL')
                );

                $mail = new PMF_Mail($faqConfig);
                $mail->setReplyTo($email, $realname);
                $mail->addTo($faqConfig->get('main.administrationMail'));
                $mail->subject = PMF_Utils::resolveMarkers($PMF_LANG['emailRegSubject'], $faqConfig);
                $mail->message = $text;
                $result = $mail->send();
                unset($mail);

                $message = array(
                    'success' => trim($PMF_LANG['successMessage']) .
                                 ' ' .
                                 trim($PMF_LANG['msgRegThankYou'])
                );
            }

        } else {
            $message = array('error' => $PMF_LANG['err_sendMail']);
        }
        break;

    case 'savevoting':

        $faq      = new PMF_Faq($faqConfig);
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
            $faqRating   = new PMF_Rating($faqConfig);
            $message = array(
                'success' => $PMF_LANG['msgVoteThanks'],
                'rating'  => $faqRating->getVotingResult($recordId)
            );
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
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->get('main.administrationMail');
        }

        if (!is_null($name) && !empty($name) && !is_null($email) && !empty($email) && !is_null($question) &&
            !empty($question) && $stopwords->checkBannedWord(PMF_String::htmlspecialchars($question))) {

            $question = sprintf(
                "%s %s\n%s %s\n\n %s",
                $PMF_LANG["msgNewContentName"],
                $name,
                $PMF_LANG["msgNewContentMail"],
                $email,
                $question
            );

            $mail = new PMF_Mail($faqConfig);
            $mail->setReplyTo($email, $name);
            $mail->addTo($faqConfig->get('main.administrationMail'));
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
            is_array($mailto) && !empty($mailto['mailto'][0]) &&
                $stopwords->checkBannedWord(PMF_String::htmlspecialchars($attached))) {

            foreach($mailto['mailto'] as $recipient) {
                $recipient = trim(strip_tags($recipient));
                if (!empty($recipient)) {
                    $mail = new PMF_Mail($faqConfig);
                    $mail->setReplyTo($email, $name);
                    $mail->addTo($recipient);
                    $mail->subject = $PMF_LANG["msgS2FMailSubject"].$name;
                    $mail->message = sprintf("%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $faqConfig->get('main.send2friendText'),
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

    // Save user data from UCP
    case 'saveuserdata':

        $userId   = PMF_Filter::filterInput(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
        $name     = PMF_Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = PMF_Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $confirm  = PMF_Filter::filterInput(INPUT_POST, 'password_confirm', FILTER_SANITIZE_STRING);

        $user = PMF_User_CurrentUser::getFromSession($faqConfig);

        if ($userId !== $user->getUserId()) {
            $message = array('error' => 'User ID mismatch!');
            break;
        }

        if ($password !== $confirm) {
            $message = array('error' => $PMF_LANG['ad_user_error_passwordsDontMatch']);
            break;
        }

        $userData = array(
            'display_name' => $name,
            'email'        => $email);
        $success = $user->setUserData($userData);

        if (0 !== strlen($password) && 0 !== strlen($confirm)) {
            foreach ($user->getAuthContainer() as $name => $auth) {
                if ($auth->setReadOnly()) {
                    continue;
                }
                if (!$auth->changePassword($user->getLogin(), $password)) {
                    $message = array('error' => $auth->error());
                    $success = false;
                } else {
                    $success = true;
                }
            }
        }

        if ($success) {
            $message = array('success' => $PMF_LANG['ad_entry_savedsuc']);
        } else {
            $message = array('error' => $PMF_LANG['ad_entry_savedfail']);
        }
        break;

    case 'changepassword':

        $username = PMF_Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email    = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!is_null($username) && !is_null($email)) {

            $user       = new PMF_User_CurrentUser($faqConfig);
            $loginExist = $user->getUserByLogin($username);

            if ($loginExist && ($email == $user->getUserData('email'))) {
                $consonants = array(
                    'b','c','d','f','g','h','j','k','l','m','n','p','r','s','t','v','w','x','y','z'
                );
                $vowels = array(
                    'a','e','i','o','u'
                );
                $newPassword = '';
                srand((double)microtime()*1000000);
                for ($i = 1; $i <= 4; $i++) {
                    $newPassword .= $consonants[rand(0,19)];
                    $newPassword .= $vowels[rand(0,4)];
                }
                $user->changePassword($newPassword);
                $text = $PMF_LANG['lostpwd_text_1']."\nUsername: ".$username."\nNew Password: ".$newPassword."\n\n".$PMF_LANG["lostpwd_text_2"];

                $mail = new PMF_Mail($faqConfig);
                $mail->addTo($email);
                $mail->subject = '[%sitename%] Username / password request';
                $mail->message = $text;
                $result = $mail->send();
                unset($mail);
                // Trust that the email has been sent
                $message = array('success' => $PMF_LANG['lostpwd_mail_okay']);
            } else {
                $message = array('error' => $PMF_LANG['lostpwd_err_1']);
            }
        } else {
            $message = array('error' => $PMF_LANG['lostpwd_err_2']);
        }
        break;
}

print json_encode($message);
