<?php

/**
 * The Ajax Service Layer.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2020 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2010-09-15
 */

define('IS_VALID_PHPMYFAQ', null);

use phpMyFAQ\Captcha;
use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Network;
use phpMyFAQ\News;
use phpMyFAQ\Question;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session;
use phpMyFAQ\Stopwords;
use phpMyFAQ\Strings;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use phpMyFAQ\Visits;

//
// Bootstrapping
//
require 'src/Bootstrap.php';

$action = Filter::filterInput(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$ajaxLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
$code = Filter::filterInput(INPUT_POST, 'captcha', FILTER_SANITIZE_STRING);
$currentToken = Filter::filterInput(INPUT_POST, 'csrf', FILTER_SANITIZE_STRING);

$Language = new Language($faqConfig);
$languageCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

if (Language::isASupportedLanguage($ajaxLang)) {
    $languageCode = trim($ajaxLang);
    require_once 'lang/language_' . $languageCode . '.php';
} else {
    $languageCode = 'en';
    require_once 'lang/language_en.php';
}

//
// Load plurals support for selected language
//
$plr = new Plurals($PMF_LANG);

//
// Initializing static string wrapper
//
Strings::init($languageCode);

//
// Send headers
//
$http = new HttpHelper();
$http->setContentType('application/json');

$faqSession = new Session($faqConfig);
$network = new Network($faqConfig);
$stopWords = new Stopwords($faqConfig);

if (!$network->checkIp($_SERVER['REMOTE_ADDR'])) {
    $message = ['error' => $PMF_LANG['err_bannedIP']];
}

//
// Check, if user is logged in
//
$user = CurrentUser::getFromCookie($faqConfig);
if (!$user instanceof CurrentUser) {
    $user = CurrentUser::getFromSession($faqConfig);
}
if ($user instanceof CurrentUser) {
    $isLoggedIn = true;
} else {
    $isLoggedIn = false;
}

//
// Check captcha
//
$captcha = new Captcha($faqConfig);
$captcha->setUserIsLoggedIn($isLoggedIn);

if ('savevoting' !== $action && 'saveuserdata' !== $action && 'changepassword' !== $action &&
    !$captcha->checkCaptchaCode($code)) {
    $message = ['error' => $PMF_LANG['msgCaptcha']];
}

//
// Check if logged in if FAQ is completely secured
//
if (false === $isLoggedIn && $faqConfig->get('security.enableLoginOnly') &&
    'changepassword' !== $action && 'saveregistration' !== $action) {
    $message = ['error' => $PMF_LANG['ad_msg_noauth']];
}

if (isset($message['error'])) {
    $http->sendJsonWithHeaders($message);
    exit();
}

// Save user generated content
switch ($action) {
    //
    // Comments
    //
    case 'savecomment':

        if (!$faqConfig->get('records.allowCommentsForGuests') &&
            !$user->perm->checkRight($user->getUserId(), 'addcomment')) {
            $message = ['error' => $PMF_LANG['err_NotAuth']];
            break;
        }

        $faq = new Faq($faqConfig);
        $oComment = new Comments($faqConfig);
        $category = new Category($faqConfig);
        $type = Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
        $faqId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $newsId = Filter::filterInput(INPUT_POST, 'newsId', FILTER_VALIDATE_INT);
        $username = Filter::filterInput(INPUT_POST, 'user', FILTER_SANITIZE_STRING);
        $mailer = Filter::filterInput(INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL);
        $comment = Filter::filterInput(INPUT_POST, 'comment_text', FILTER_SANITIZE_STRING);

        switch ($type) {
            case 'news':
                $id = $newsId;
                break;
            case 'faq';
                $id = $faqId;
                break;
        }

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($mailer)) {
            $mailer = $faqConfig->get('main.administrationMail');
        }

        // Check display name and e-mail address for not logged in users
        if (false === $isLoggedIn) {
            $user = new User($faqConfig);
            if (true === $user->checkDisplayName($username) && true === $user->checkMailAddress($mailer)) {
                $message = ['error' => '-'.$PMF_LANG['err_SaveComment']];
                break;
            }
        }

        if (!is_null($username) && !is_null($mailer) && !is_null($comment) && $stopWords->checkBannedWord(
                $comment
            ) && !$faq->commentDisabled(
                $id,
                $languageCode,
                $type
            )) {
            try {
                $faqSession->userTracking('save_comment', $id);
            } catch (Exception $e) {
                // @todo handle the exception
            }

            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId($id)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($mailer)
                ->setComment(nl2br($comment))
                ->setDate($_SERVER['REQUEST_TIME']);

            if ($oComment->addComment($commentEntity)) {
                $emailTo = $faqConfig->get('main.administrationMail');
                $title = '';
                $urlToContent = '';
                if ('faq' == $type) {
                    $faq->getRecord($id);
                    if ($faq->faqRecord['email'] != '') {
                        $emailTo = $faq->faqRecord['email'];
                    }

                    $title = $faq->getRecordTitle($id);

                    $faqUrl = sprintf(
                        '%s?action=faq&cat=%d&id=%d&artlang=%s',
                        $faqConfig->getDefaultUrl(),
                        $category->getCategoryIdFromFaq($faq->faqRecord['id']),
                        $faq->faqRecord['id'],
                        $faq->faqRecord['lang']
                    );
                    $oLink = new Link($faqUrl, $faqConfig);
                    $oLink->itemTitle = $faq->faqRecord['title'];
                    $urlToContent = $oLink->toString();
                } else {
                    $news = new News($faqConfig);
                    $newsData = $news->getNewsEntry($id);
                    if ($newsData['authorEmail'] != '') {
                        $emailTo = $newsData['authorEmail'];
                    }

                    $title = $newsData['header'];

                    $link = sprintf(
                        '%s?action=news&newsid=%d&newslang=%s',
                        $faqConfig->getDefaultUrl(),
                        $newsData['id'],
                        $newsData['lang']
                    );
                    $oLink = new Link($link, $faqConfig);
                    $oLink->itemTitle = $newsData['header'];
                    $urlToContent = $oLink->toString();
                }

                $commentMail =
                    'User: ' . $commentEntity->getUsername() . ', mailto:' . $commentEntity->getEmail() . "\n" .
                    'Title: ' . $title . "\n" .
                    'New comment posted here: ' . $urlToContent .
                    "\n\n" .
                    wordwrap($comment, 72);

                $send = [];
                $mailer = new Mail($faqConfig);
                $mailer->setReplyTo($commentEntity->getEmail(), $commentEntity->getUsername());
                $mailer->addTo($emailTo);

                $send[$emailTo] = 1;
                $send[$faqConfig->get('main.administrationMail')] = 1;

                if ($type === CommentType::FAQ) {
                    // Let the category owner of a FAQ get a copy of the message
                    $category = new Category($faqConfig);
                    $categories = $category->getCategoryIdsFromFaq($faq->faqRecord['id']);
                    foreach ($categories as $_category) {
                        $userId = $category->getOwner($_category);
                        $catUser = new User($faqConfig);
                        $catUser->getUserById($userId);
                        $catOwnerEmail = $catUser->getUserData('email');

                        if ($catOwnerEmail !== '') {
                            if (!isset($send[$catOwnerEmail]) && $catOwnerEmail !== $emailTo) {
                                $mailer->addCc($catOwnerEmail);
                                $send[$catOwnerEmail] = 1;
                            }
                        }
                    }
                }

                $mailer->subject = $faqConfig->get('main.titleFAQ') . ': New comment for "' . $title . '"';
                $mailer->message = strip_tags($commentMail);

                $result = $mailer->send();
                unset($mailer);

                $message = ['success' => $PMF_LANG['msgCommentThanks']];
            } else {
                try {
                    $faqSession->userTracking('error_save_comment', $id);
                } catch (Exception $e) {
                    // @todo handle the exception
                }
                $message = ['error' => $PMF_LANG['err_SaveComment']];
            }
        } else {
            $message = ['error' => 'Please add your name, your e-mail address and a comment!'];
        }
        break;

    case 'savefaq':

        if (!$faqConfig->get('records.allowNewFaqsForGuests') &&
            !$user->perm->checkRight($user->getUserId(), 'addfaq')) {
            $message = ['error' => $PMF_LANG['err_NotAuth']];
            break;
        }

        $faq = new Faq($faqConfig);
        $category = new Category($faqConfig);
        $questionObject = new Question($faqConfig);
        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $faqId = Filter::filterInput(INPUT_POST, 'faqid', FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterInput(INPUT_POST, 'faqlanguage', FILTER_SANITIZE_STRING);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
            $answer = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = html_entity_decode($answer);
        } else {
            $answer = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_STRIPPED);
            $answer = nl2br($answer);
        }
        $translatedAnswer = Filter::filterInput(INPUT_POST, 'translated_answer', FILTER_SANITIZE_STRING);
        $contentLink = Filter::filterInput(INPUT_POST, 'contentlink', FILTER_SANITIZE_STRING);
        $contentLink = Filter::filterVar($contentLink, FILTER_VALIDATE_URL);
        $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRIPPED);
        $categories = Filter::filterInputArray(
            INPUT_POST,
            [
                'rubrik' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ],
            ]
        );

        // Check on translation
        if (empty($answer) && !is_null($translatedAnswer)) {
            $answer = $translatedAnswer;
        }

        if (!is_null($author) && !is_null($email) && !is_null($question) && $stopWords->checkBannedWord(strip_tags($question)) &&
            !is_null($answer) && $stopWords->checkBannedWord(strip_tags($answer)) &&
            ((is_null($faqId) && !is_null($categories['rubrik'])) || (!is_null($faqId) && !is_null($faqLanguage) &&
                    Language::isASupportedLanguage($faqLanguage)))) {
            $isNew = true;
            if (!is_null($faqId)) {
                $isNew = false;
                try {
                    $faqSession->userTracking('save_new_translation_entry', 0);
                } catch (Exception $e) {
                    // @todo handle the exception
                }
            } else {
                try {
                    $faqSession->userTracking('save_new_entry', 0);
                } catch (Exception $e) {
                    // @todo handle the exception
                }
            }

            $isTranslation = false;
            if (!is_null($faqLanguage)) {
                $isTranslation = true;
                $newLanguage = $faqLanguage;
            }

            if (Strings::substr($contentLink, 7) != '') {
                $answer = sprintf(
                    '%s<br><div id="newFAQContentLink">%s<a href="http://%s" target="_blank">%s</a></div>',
                    $answer,
                    $PMF_LANG['msgInfo'],
                    Strings::substr($contentLink, 7),
                    $contentLink
                );
            }

            $autoActivate = $faqConfig->get('records.defaultActivation');

            $newData = [
                'lang' => ($isTranslation === true ? $newLanguage : $languageCode),
                'thema' => $question,
                'active' => ($autoActivate ? FAQ_SQL_ACTIVE_YES : FAQ_SQL_ACTIVE_NO),
                'sticky' => 0,
                'content' => $answer,
                'keywords' => $keywords,
                'author' => $author,
                'email' => $email,
                'comment' => 'y',
                'date' => date('YmdHis'),
                'dateStart' => '00000000000000',
                'dateEnd' => '99991231235959',
                'linkState' => '',
                'linkDateCheck' => 0,
                'notes' => ''
            ];

            if ($isNew) {
                $categories = $categories['rubrik'];
            } else {
                $newData['id'] = $faqId;
                $categories = $category->getCategoryIdsFromFaq($newData['id']);
            }

            $recordId = $faq->addRecord($newData, $isNew);

            $faq->addCategoryRelations($categories, $recordId, $newData['lang']);

            $openQuestionId = Filter::filterInput(INPUT_POST, 'openQuestionID', FILTER_VALIDATE_INT);
            if ($openQuestionId) {
                if ($faqConfig->get('records.enableDeleteQuestion')) {
                    $questionObject->deleteQuestion($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $questionObject->updateQuestionAnswer($openQuestionId, $recordId, $categories[0]);
                }
            }

            // Activate visits
            $visits = new Visits($faqConfig);
            $visits->logViews($recordId);

            // Set permissions
            $userPermissions = $category->getPermissions('user', $categories);
            // Add user permissions
            $faq->addPermission('user', $recordId, $userPermissions);
            $category->addPermission('user', $categories, $userPermissions);
            // Add group permission
            if ($faqConfig->get('security.permLevel') !== 'basic') {
                $groupPermissions = $category->getPermissions('group', $categories);
                $faq->addPermission('group', $recordId, $groupPermissions);
                $category->addPermission('group', $categories, $groupPermissions);
            }

            // Let the PMF Administrator and the Entity Owner to be informed by email of this new entry
            $send = [];
            $mailer = new Mail($faqConfig);
            $mailer->setReplyTo($email, $author);
            $mailer->addTo($faqConfig->get('main.administrationMail'));
            $send[$faqConfig->get('main.administrationMail')] = 1;

            foreach ($categories as $_category) {
                $userId = $category->getOwner($_category);
                $groupId = $category->getModeratorGroupId($_category);

                // @todo Move this code to Entityhp
                $oUser = new User($faqConfig);
                $oUser->getUserById($userId);
                $catOwnerEmail = $oUser->getUserData('email');

                // Avoid to send multiple emails to the same owner
                if (!empty($catOwnerEmail) && !isset($send[$catOwnerEmail])) {
                    $mailer->addCc($catOwnerEmail);
                    $send[$catOwnerEmail] = 1;
                }

                if ($groupId > 0) {
                    $moderators = $oUser->perm->getGroupMembers($groupId);
                    foreach ($moderators as $moderator) {
                        $oUser->getUserById($moderator);
                        $moderatorEmail = $oUser->getUserData('email');

                        // Avoid to send multiple emails to the same moderator
                        if (!empty($moderatorEmail) && !isset($send[$moderatorEmail])) {
                            $mailer->addCc($moderatorEmail);
                            $send[$moderatorEmail] = 1;
                        }
                    }
                }
            }

            $mailer->subject = $faqConfig->get('main.titleFAQ') . ': New FAQ was added.';

            // @todo let the email contains the faq article both as plain text and as HTML
            $mailer->message = html_entity_decode(
                    $PMF_LANG['msgMailCheck']
                ) . "\n\n" .
                $faqConfig->get('main.titleFAQ') . ': ' .
                $faqConfig->getDefaultUrl() . 'admin/?action=editentry&id=' . $recordId . '&lang=' . $faqLanguage;
            $result = $mailer->send();
            unset($mailer);

            $message = [
                'success' => ($isNew ? $PMF_LANG['msgNewContentThanks'] : $PMF_LANG['msgNewTranslationThanks']),
            ];
        } else {
            $message = [
                'error' => $PMF_LANG['err_SaveEntries']
            ];
        }

        break;

    //
    // Add question
    //
    case 'savequestion':

        if (!$faqConfig->get('records.allowQuestionsForGuests') &&
            !$user->perm->checkRight($user->getUserId(), 'addquestion')) {
            $message = ['error' => $PMF_LANG['err_NotAuth']];
            break;
        }

        $faq = new Faq($faqConfig);
        $cat = new Category($faqConfig);
        $categories = $cat->getAllCategories();
        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $ucategory = Filter::filterInput(INPUT_POST, 'category', FILTER_VALIDATE_INT);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);
        $save = Filter::filterInput(INPUT_POST, 'save', FILTER_VALIDATE_INT, 0);

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->get('main.administrationMail');
        }

        // If smart answering is disabled, save question immediately
        if (false === $faqConfig->get('main.enableSmartAnswering')) {
            $save = true;
        }

        if (!is_null($author) && !is_null($email) && !is_null($question) && $stopWords->checkBannedWord(
                Strings::htmlspecialchars($question)
            )) {
            if ($faqConfig->get('records.enableVisibilityQuestions')) {
                $visibility = 'N';
            } else {
                $visibility = 'Y';
            }

            $questionData = [
                'username' => $author,
                'email' => $email,
                'category_id' => $ucategory,
                'question' => $question,
                'is_visible' => $visibility
            ];

            if (false === (boolean)$save) {
                $cleanQuestion = $stopWords->clean($question);

                $user = new CurrentUser($faqConfig);
                $faqSearch = new Search($faqConfig);
                $faqSearch->setCategory(new Category($faqConfig));
                $faqSearch->setCategoryId($ucategory);
                $faqSearchResult = new SearchResultSet($user, $faq, $faqConfig);
                $searchResult = [];
                $mergedResult = [];

                foreach ($cleanQuestion as $word) {
                    if (!empty($word)) {
                        $searchResult[] = $faqSearch->search($word, false);
                    }
                }
                foreach ($searchResult as $resultSet) {
                    foreach ($resultSet as $result) {
                        $mergedResult[] = $result;
                    }
                }
                $faqSearchResult->reviewResultSet($mergedResult);

                if (0 < $faqSearchResult->getNumberOfResults()) {
                    $response = sprintf(
                        '<p>%s</p>',
                        $plr->getMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults())
                    );

                    $response .= '<ul>';

                    $faqHelper = new FaqHelper($faqConfig);
                    foreach ($faqSearchResult->getResultSet() as $result) {
                        $url = sprintf(
                            '%sindex.php?action=faq&cat=%d&id=%d&artlang=%s',
                            $faqConfig->getDefaultUrl(),
                            $result->category_id,
                            $result->id,
                            $result->lang
                        );
                        $oLink = new Link($url, $faqConfig);
                        $oLink->text = Utils::chopString($result->question, 15);
                        $oLink->itemTitle = $result->question;

                        try {
                            $response .= sprintf(
                                '<li>%s<br><div class="searchpreview">%s...</div></li>',
                                $oLink->toHtmlAnchor(),
                                $faqHelper->renderAnswerPreview($result->answer, 10)
                            );
                        } catch (Exception $e) {
                            // handle exception
                        }
                    }
                    $response .= '</ul>';

                    $message = ['result' => $response];
                } else {
                    $questionHelper = new QuestionHelper($faqConfig, $cat);
                    $questionHelper->sendSuccessMail($questionData, $categories);
                    $message = ['success' => $PMF_LANG['msgAskThx4Mail']];
                }
            } else {
                $questionHelper = new QuestionHelper($faqConfig, $cat);
                $questionHelper->sendSuccessMail($questionData, $categories);
                $message = ['success' => $PMF_LANG['msgAskThx4Mail']];
            }
        } else {
            $message = ['error' => $PMF_LANG['err_SaveQuestion']];
        }

        break;

    case 'saveregistration':

        $realname = Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_STRING);
        $loginName = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterInput(INPUT_POST, 'is_visible', FILTER_SANITIZE_STRING);

        if (!is_null($loginName) && !is_null($email) && !is_null($realname)) {
            $message = [];
            $user = new User($faqConfig);

            // Create user account (login and password)
            // Note: password be automatically generated and sent by email as soon if admin switch user to "active"
            if (!$user->createUser($loginName, null)) {
                $message = ['error' => $user->error()];
            } else {
                $user->userdata->set(
                    ['display_name', 'email', 'is_visible'],
                    [$realname, $email,  $isVisible === 'on' ? 1 : 0]
                );
                // set user status
                $user->setStatus('blocked');

                if (!$faqConfig->get('spam.manualActivation')) {
                    $isNowActive = $user->activateUser();
                } else {
                    $isNowActive = false;
                }

                if ($isNowActive) {
                    $adminMessage = 'This user has been automatically activated, you can still' .
                        ' modify the users permissions or decline membership by visiting the admin section';
                } else {
                    $adminMessage = 'To activate this user please use';
                }

                $text = sprintf(
                    "New user has been registrated:\n\nName: %s\nLogin name: %s\n\n" .
                    '%s the administration interface at %s.',
                    $realname,
                    $loginName,
                    $adminMessage,
                    $faqConfig->getDefaultUrl()
                );

                $mailer = new Mail($faqConfig);
                $mailer->setReplyTo($email, $realname);
                $mailer->addTo($faqConfig->get('main.administrationMail'));
                $mailer->subject = Utils::resolveMarkers($PMF_LANG['emailRegSubject'], $faqConfig);
                $mailer->message = $text;
                $result = $mailer->send();
                unset($mailer);

                $message = [
                    'success' => trim($PMF_LANG['successMessage']) .
                        ' ' .
                        trim($PMF_LANG['msgRegThankYou']),
                ];
            }
        } else {
            $message = ['error' => $PMF_LANG['err_sendMail']];
        }
        break;

    case 'savevoting':

        $faq = new Faq($faqConfig);
        $rating = new Rating($faqConfig);
        $type = Filter::filterInput(INPUT_POST, 'type', FILTER_SANITIZE_STRING, 'faq');
        $recordId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterInput(INPUT_POST, 'vote', FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

        if (isset($vote) && $rating->check($recordId, $userIp) && $vote > 0 && $vote < 6) {
            try {
                $faqSession->userTracking('save_voting', $recordId);
            } catch (Exception $e) {
                // @todo handle the exception
            }

            $votingData = [
                'record_id' => $recordId,
                'vote' => $vote,
                'user_ip' => $userIp,
            ];

            if (!$rating->getNumberOfVotings($recordId)) {
                $rating->addVoting($votingData);
            } else {
                $rating->update($votingData);
            }
            $message = [
                'success' => $PMF_LANG['msgVoteThanks'],
                'rating' => $rating->getVotingResult($recordId),
            ];
        } elseif (!$rating->check($recordId, $userIp)) {
            try {
                $faqSession->userTracking('error_save_voting', $recordId);
            } catch (Exception $e) {
                // @todo handle the exception
            }
            $message = ['error' => $PMF_LANG['err_VoteTooMuch']];
        } else {
            try {
                $faqSession->userTracking('error_save_voting', $recordId);
            } catch (Exception $e) {
                // @todo handle the exception
            }
            $message = ['error' => $PMF_LANG['err_noVote']];
        }

        break;

    // Send user generated mails
    case 'sendcontact':

        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->get('main.administrationMail');
        }

        if (!is_null($author) && !is_null($email) && !is_null($question) &&
            !empty($question) && $stopWords->checkBannedWord(Strings::htmlspecialchars($question))) {
            $question = sprintf(
                "%s %s\n%s %s\n\n %s",
                $PMF_LANG['msgNewContentName'],
                $author,
                $PMF_LANG['msgNewContentMail'],
                $email,
                $question
            );

            $mailer = new Mail($faqConfig);
            $mailer->setReplyTo($email, $author);
            $mailer->addTo($faqConfig->get('main.administrationMail'));
            $mailer->subject = Utils::resolveMarkers('Feedback: %sitename%', $faqConfig);
            $mailer->message = $question;
            $result = $mailer->send();

            unset($mailer);

            if ($result) {
                $message = ['success' => $PMF_LANG['msgMailContact']];
            } else {
                $message = ['error' => $PMF_LANG['err_sendMail']];
            }
        } else {
            $message = ['error' => $PMF_LANG['err_sendMail']];
        }
        break;

    // Send mails to friends
    case 'sendtofriends':

        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $link = Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
        $attached = Filter::filterInput(INPUT_POST, 'message', FILTER_SANITIZE_STRIPPED);
        $mailto = Filter::filterInputArray(
            INPUT_POST,
            [
                'mailto' => [
                    'filter' => FILTER_VALIDATE_EMAIL,
                    'flags' => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE,
                ],
            ]
        );

        if (!is_null($author) && !is_null($email) && is_array($mailto) && !empty($mailto['mailto'][0]) &&
            $stopWords->checkBannedWord(Strings::htmlspecialchars($attached))) {
            foreach ($mailto['mailto'] as $recipient) {
                $recipient = trim(strip_tags($recipient));
                if (!empty($recipient)) {
                    $mailer = new Mail($faqConfig);
                    $mailer->setReplyTo($email, $author);
                    $mailer->addTo($recipient);
                    $mailer->subject = $PMF_LANG['msgS2FMailSubject'] . $author;
                    $mailer->message = sprintf(
                        "%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $faqConfig->get('main.send2friendText'),
                        $PMF_LANG['msgS2FText2'],
                        $link,
                        $attached
                    );

                    // Send the email
                    $result = $mailer->send();
                    unset($mailer);
                    usleep(250);
                }
            }

            $message = ['success' => $PMF_LANG['msgS2FThx']];
        } else {
            $message = ['error' => $PMF_LANG['err_sendMail']];
        }
        break;

    // Save user data from UCP
    case 'saveuserdata':

        if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $currentToken) {
            $message = ['error' => $PMF_LANG['ad_msg_noauth']];
            break;
        }

        $userId = Filter::filterInput(INPUT_POST, 'userid', FILTER_VALIDATE_INT);
        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
        $confirm = Filter::filterInput(INPUT_POST, 'password_confirm', FILTER_SANITIZE_STRING);

        $user = CurrentUser::getFromSession($faqConfig);

        if ($userId !== $user->getUserId()) {
            $message = ['error' => 'User ID mismatch!'];
            break;
        }

        if ($password !== $confirm) {
            $message = ['error' => $PMF_LANG['ad_user_error_passwordsDontMatch']];
            break;
        }

        $userData = [
            'display_name' => $author,
            'email' => $email,
        ];
        $success = $user->setUserData($userData);

        if (0 !== strlen($password) && 0 !== strlen($confirm)) {
            foreach ($user->getAuthContainer() as $author => $auth) {
                if ($auth->setReadOnly()) {
                    continue;
                }
                if (!$auth->changePassword($user->getLogin(), $password)) {
                    $message = ['error' => $auth->error()];
                    $success = false;
                } else {
                    $success = true;
                }
            }
        }

        if ($success) {
            $message = ['success' => $PMF_LANG['ad_entry_savedsuc']];
        } else {
            $message = ['error' => $PMF_LANG['ad_entry_savedfail']];
        }
        break;

    case 'changepassword':

        $username = Filter::filterInput(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

        if (!is_null($username) && !is_null($email)) {
            $user = new CurrentUser($faqConfig);
            $loginExist = $user->getUserByLogin($username);

            if ($loginExist && ($email == $user->getUserData('email'))) {
                $newPassword = $user->createPassword();
                $user->changePassword($newPassword);
                $text = $PMF_LANG['lostpwd_text_1'] . "\nUsername: " . $username . "\nNew Password: " . $newPassword . "\n\n" . $PMF_LANG['lostpwd_text_2'];

                $mailer = new Mail($faqConfig);
                $mailer->addTo($email);
                $mailer->subject = Utils::resolveMarkers('[%sitename%] Username / password request', $faqConfig);
                $mailer->message = $text;
                $result = $mailer->send();
                unset($mailer);
                // Trust that the email has been sent
                $message = ['success' => $PMF_LANG['lostpwd_mail_okay']];
            } else {
                $message = ['error' => $PMF_LANG['lostpwd_err_1']];
            }
        } else {
            $message = ['error' => $PMF_LANG['lostpwd_err_2']];
        }
        break;

    case 'request-removal':

        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $loginName = Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_STRING);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRIPPED);

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->get('main.administrationMail');
        }

        if (!is_null($author) && !is_null($email) && !is_null($question) &&
            !empty($question) && $stopWords->checkBannedWord(Strings::htmlspecialchars($question))) {
            $question = sprintf(
                "%s %s\n%s %s\n%s %s\n\n %s",
                $PMF_LANG['ad_user_loginname'],
                $loginName,
                $PMF_LANG['msgNewContentName'],
                $author,
                $PMF_LANG['msgNewContentMail'],
                $email,
                $question
            );

            $mailer = new Mail($faqConfig);
            $mailer->setReplyTo($email, $author);
            $mailer->addTo($faqConfig->get('main.administrationMail'));
            $mailer->subject = $faqConfig->get('main.titleFAQ') . ': Remove User Request';
            $mailer->message = $question;
            $result = $mailer->send();
            unset($mailer);

            $message = ['success' => $PMF_LANG['msgMailContact']];
        } else {
            $message = ['error' => $PMF_LANG['err_sendMail']];
        }
        break;
}

$http->sendJsonWithHeaders($message);
exit();
