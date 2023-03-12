<?php

/**
 * The Ajax Service Layer.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-15
 */

const IS_VALID_PHPMYFAQ = null;

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Helper\RegistrationHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Mail;
use phpMyFAQ\Network;
use phpMyFAQ\News;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Rating;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Twofactor;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

//
// Bootstrapping
//
require 'src/Bootstrap.php';

$postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

$apiLanguage = Filter::filterVar($postData['lang'], FILTER_UNSAFE_RAW);
$currentToken = Filter::filterVar($postData['csrf'] ?? '', FILTER_UNSAFE_RAW);
$action = Filter::filterInput(INPUT_GET, 'action', FILTER_UNSAFE_RAW);

if ($faqConfig->get('security.enableGoogleReCaptchaV2')) {
    $code = Filter::filterVar($postData['g-recaptcha-response'] ?? '', FILTER_UNSAFE_RAW);
} else {
    $code = Filter::filterVar($postData['captcha'] ?? '', FILTER_UNSAFE_RAW);
}

$Language = new Language($faqConfig);
$languageCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
require_once 'lang/language_en.php';
$faqConfig->setLanguage($Language);

if (Language::isASupportedLanguage($apiLanguage)) {
    $languageCode = trim((string) $apiLanguage);
    require_once 'lang/language_' . $languageCode . '.php';
} else {
    $languageCode = 'en';
    require_once 'lang/language_en.php';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($languageCode);
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

//
// Load plurals support for selected language
//
$plr = new Plurals();

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
$stopWords = new StopWords($faqConfig);

if (!$network->checkIp($_SERVER['REMOTE_ADDR'])) {
    $message = ['error' => Translation::get('err_bannedIP')];
}

//
// Check, if user is logged in
//
[ $user, $isLoggedIn ] = CurrentUser::getCurrentUser($faqConfig);

//
// Check captcha
//
$captcha = Captcha::getInstance($faqConfig);
$captcha->setUserIsLoggedIn($isLoggedIn);

if (
    'savevoting' !== $action && 'submit-user-data' !== $action && 'change-password' !== $action &&
    'submit-request-removal' !== $action && !$captcha->checkCaptchaCode($code)
) {
    $message = ['error' => Translation::get('msgCaptcha')];
}

//
// Check if the user is logged in when FAQ is completely secured
//
if (
    !$isLoggedIn && $faqConfig->get('security.enableLoginOnly') && 'submit-request-removal' !== $action &&
    'change-password' !== $action && 'saveregistration' !== $action
) {
    $message = ['error' => Translation::get('ad_msg_noauth')];
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
    case 'add-comment':
        if (
            !$faqConfig->get('records.allowCommentsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), 'addcomment')
        ) {
            $message = ['error' => Translation::get('err_NotAuth')];
            break;
        }

        $faq = new Faq($faqConfig);
        $oComment = new Comments($faqConfig);
        $category = new Category($faqConfig);

        $type = Filter::filterVar($postData['type'], FILTER_SANITIZE_SPECIAL_CHARS);
        $faqId = Filter::filterVar($postData['id'] ?? null, FILTER_VALIDATE_INT, 0);
        $newsId = Filter::filterVar($postData['newsId'] ?? null, FILTER_VALIDATE_INT);
        $username = Filter::filterVar($postData['user'], FILTER_SANITIZE_SPECIAL_CHARS);
        $mailer = Filter::filterVar($postData['mail'], FILTER_VALIDATE_EMAIL);
        $comment = Filter::filterVar($postData['comment_text'], FILTER_SANITIZE_SPECIAL_CHARS);

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
            $mailer = $faqConfig->getAdminEmail();
        }

        // Check display name and e-mail address for not logged in users
        if (false === $isLoggedIn) {
            $user = new User($faqConfig);
            if (true === $user->checkDisplayName($username) && true === $user->checkMailAddress($mailer)) {
                $message = ['error' => Translation::get('err_SaveComment')];
                $faqConfig->getLogger()->error('Name and mail already used by registered user.');
                break;
            }
        }

        if (
            !is_null($username) && !is_null($mailer) && !is_null($comment) && $stopWords->checkBannedWord(
                $comment
            ) && !$faq->commentDisabled(
                $id,
                $languageCode,
                $type
            )
        ) {
            try {
                $faqSession->userTracking('save_comment', $id);
            } catch (Exception $exception) {
                $faqConfig->getLogger()->error('Tracking of save new comment', ['exception' => $exception->getMessage()]);
            }

            $commentEntity = new Comment();
            $commentEntity
                ->setRecordId($id)
                ->setType($type)
                ->setUsername($username)
                ->setEmail($mailer)
                ->setComment(nl2br((string) $comment))
                ->setDate($_SERVER['REQUEST_TIME']);

            if ($oComment->addComment($commentEntity)) {
                $emailTo = $faqConfig->getAdminEmail();
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
                    wordwrap((string) $comment, 72);

                $send = [];
                $mailer = new Mail($faqConfig);
                $mailer->setReplyTo($commentEntity->getEmail(), $commentEntity->getUsername());
                $mailer->addTo($emailTo);

                $send[$emailTo] = 1;
                $send[$faqConfig->getAdminEmail()] = 1;

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

                $mailer->subject = $faqConfig->getTitle() . ': New comment for "' . $title . '"';
                $mailer->message = strip_tags($commentMail);

                $result = $mailer->send();
                unset($mailer);

                $message = ['success' => Translation::get('msgCommentThanks')];
            } else {
                try {
                    $faqSession->userTracking('error_save_comment', $id);
                } catch (Exception) {
                    // @todo handle the exception
                }
                $message = ['error' => Translation::get('err_SaveComment')];
            }
        } else {
            $message = ['error' => 'Please add your name, your e-mail address and a comment!'];
        }
        break;

    case 'add-faq':
        if (
            !$faqConfig->get('records.allowNewFaqsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), 'addfaq')
        ) {
            $message = ['error' => Translation::get('err_NotAuth')];
            break;
        }

        $faq = new Faq($faqConfig);
        $category = new Category($faqConfig);
        $questionObject = new Question($faqConfig);

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_UNSAFE_RAW));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $question = Filter::filterVar($postData['question'], FILTER_UNSAFE_RAW);
        $question = trim(strip_tags((string) $question));
        if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
            $answer = Filter::filterVar($postData['answer'], FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = trim(html_entity_decode((string) $answer));
        } else {
            $answer = Filter::filterVar($postData['answer'], FILTER_UNSAFE_RAW);
            $answer = strip_tags((string) $answer);
            $answer = trim(nl2br($answer));
        }
        $contentLink = Filter::filterVar($postData['contentlink'], FILTER_VALIDATE_URL);
        $keywords = Filter::filterVar($postData['keywords'], FILTER_UNSAFE_RAW);
        if (isset($postData['rubrik[]'])) {
            if (is_string($postData['rubrik[]'])) {
                $postData['rubrik[]'] = [ $postData['rubrik[]'] ];
            }
            $categories = Filter::filterArray(
                $postData['rubrik[]']
            );
        }

        // Check on translation
        if (isset($postData['faqid']) && isset($postData['lang']) && isset($postData['translated_answer'])) {
            $faqId = Filter::filterVar($postData['faqid'], FILTER_VALIDATE_INT);
            $faqLanguage = Filter::filterVar($postData['lang'], FILTER_UNSAFE_RAW);
            $answer = trim((string) Filter::filterVar($postData['translated_answer'], FILTER_UNSAFE_RAW));
        }

        if (
            !is_null($author) && !is_null($email) && !empty($question) &&
            $stopWords->checkBannedWord(strip_tags($question)) &&
            !empty($answer) && $stopWords->checkBannedWord(strip_tags($answer))
        ) {
            $isNew = true;
            $newLanguage = '';

            if (!isset($faqId)) {
                $isNew = false;
                try {
                    $faqSession->userTracking('save_new_translation_entry', 0);
                } catch (Exception) {
                    // @todo handle the exception
                }
            } else {
                try {
                    $faqSession->userTracking('save_new_entry', 0);
                } catch (Exception) {
                    // @todo handle the exception
                }
            }

            $isTranslation = false;
            if (isset($faqLanguage) && !is_null($faqLanguage)) {
                $isTranslation = true;
                $newLanguage = $faqLanguage;
            }

            if (!is_null($contentLink) && Strings::substr($contentLink, 7) !== '') {
                $answer = sprintf(
                    '%s<br><div id="newFAQContentLink">%s<a href="https://%s" target="_blank">%s</a></div>',
                    $answer,
                    Translation::get('msgInfo'),
                    Strings::substr($contentLink, 7),
                    $contentLink
                );
            }

            $autoActivate = $faqConfig->get('records.defaultActivation');

            $faqEntity = new FaqEntity();
            $faqEntity
                ->setLanguage(($isTranslation === true ? $newLanguage : $languageCode))
                ->setQuestion($question)
                ->setActive(($autoActivate ? FAQ_SQL_ACTIVE_YES : FAQ_SQL_ACTIVE_NO))
                ->setSticky(false)
                ->setAnswer($answer)
                ->setKeywords($keywords)
                ->setAuthor($author)
                ->setEmail($email)
                ->setComment(true)
                ->setLinkState('')
                ->setNotes('');

            if (!$isNew && isset($faqId)) {
                $faqEntity->setId($faqId);
                $categories = $category->getCategoryIdsFromFaq($faqId);
            }

            $recordId = $faq->create($faqEntity);

            $openQuestionId = Filter::filterInput(INPUT_POST, 'openQuestionID', FILTER_VALIDATE_INT);
            if ($openQuestionId) {
                if ($faqConfig->get('records.enableDeleteQuestion')) {
                    $questionObject->deleteQuestion($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $questionObject->updateQuestionAnswer($openQuestionId, $recordId, $categories[0]);
                }
            }

            $faqMetaData = new FaqMetaData($faqConfig);
            $faqMetaData
                ->setFaqId($recordId)
                ->setFaqLanguage($faqEntity->getLanguage())
                ->setCategories($categories)
                ->save();

            // Let the admin and the category owners to be informed by email of this new entry
            $categoryHelper = new CategoryHelper();
            $categoryHelper
                ->setCategory($category)
                ->setConfiguration($faqConfig);

            $moderators = $categoryHelper->getModerators($categories);

            try {
                $notification = new Notification($faqConfig);
                $notification->sendNewFaqAdded($moderators, $recordId, $faqLanguage);
            } catch (Exception) {
                // @todo handle exception in v3.2
            }

            $message = [
                'success' => ($isNew ? Translation::get('msgNewContentThanks') : Translation::get('msgNewTranslationThanks')),
            ];
        } else {
            $message = [
                'error' => Translation::get('err_SaveEntries')
            ];
        }

        break;

    //
    // Ask question
    //
    case 'ask-question':
        if (
            !$faqConfig->get('records.allowQuestionsForGuests') &&
            !$faqConfig->get('main.enableAskQuestions') &&
            !$user->perm->hasPermission($user->getUserId(), 'addquestion')
        ) {
            $http->setStatus(401);
            $message = ['error' => Translation::get('err_NotAuth')];
            break;
        }
        $faq = new Faq($faqConfig);
        $cat = new Category($faqConfig);
        $categories = $cat->getAllCategories();

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_UNSAFE_RAW));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $ucategory = Filter::filterVar($postData['category'], FILTER_VALIDATE_INT);
        $question = Filter::filterVar($postData['question'], FILTER_UNSAFE_RAW);
        $question = trim(strip_tags((string) $question));
        $save = Filter::filterVar($postData['save'] ?? 0, FILTER_VALIDATE_INT);

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->getAdminEmail();
        }

        // If smart answering is disabled, save question immediately
        if (false === $faqConfig->get('main.enableSmartAnswering')) {
            $save = true;
        }

        if (!empty($author) && !empty($email) && !empty($question) && $stopWords->checkBannedWord($question)) {
            if ($faqConfig->get('records.enableVisibilityQuestions')) {
                $visibility = 'Y';
            } else {
                $visibility = 'N';
            }

            $questionData = [
                'username' => $author,
                'email' => $email,
                'category_id' => $ucategory,
                'question' => Strings::htmlentities($question),
                'is_visible' => $visibility
            ];

            if (false === (bool)$save) {
                $cleanQuestion = $stopWords->clean($question);

                $user = new CurrentUser($faqConfig);
                $faqSearch = new Search($faqConfig);
                $faqSearch->setCategory(new Category($faqConfig));
                $faqSearch->setCategoryId((int) $ucategory);
                $faqPermission = new FaqPermission($faqConfig);
                $faqSearchResult = new SearchResultSet($user, $faqPermission, $faqConfig);
                $searchResult = [];
                $mergedResult = [];

                foreach ($cleanQuestion as $word) {
                    if (!empty($word)) {
                        try {
                            $searchResult[] = $faqSearch->search($word, false);
                        } catch (Exception $e) {
                            // @todo handle exception
                        }
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
                        '<h5>%s</h5>',
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
                                '<li>%s<br><small class="pmf-search-preview">%s...</small></li>',
                                $oLink->toHtmlAnchor(),
                                $faqHelper->renderAnswerPreview($result->answer, 10)
                            );
                        } catch (Exception) {
                            // handle exception
                        }
                    }
                    $response .= '</ul>';

                    $message = ['result' => $response];
                } else {
                    $questionHelper = new QuestionHelper($faqConfig, $cat);
                    try {
                        $questionHelper->sendSuccessMail($questionData, $categories);
                    } catch (Exception | TransportExceptionInterface $exception) {
                        $http->setStatus(400);
                        $message = ['error' => $exception->getMessage()];
                    }
                    $http->setStatus(200);
                    $message = ['success' => Translation::get('msgAskThx4Mail')];
                }
            } else {
                $questionHelper = new QuestionHelper($faqConfig, $cat);
                try {
                    $questionHelper->sendSuccessMail($questionData, $categories);
                } catch (Exception | TransportExceptionInterface $exception) {
                    $http->setStatus(400);
                    $message = ['error' => $exception->getMessage()];
                }
                $http->setStatus(200);
                $message = ['success' => Translation::get('msgAskThx4Mail')];
            }
        } else {
            $http->setStatus(400);
            $message = ['error' => Translation::get('err_SaveQuestion')];
        }

        break;

    case 'saveregistration':
        $registration = new RegistrationHelper($faqConfig);

        $fullName = Filter::filterInput(INPUT_POST, 'realname', FILTER_UNSAFE_RAW);
        $userName = Filter::filterInput(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterInput(INPUT_POST, 'is_visible', FILTER_UNSAFE_RAW) ?? false;

        if (!$registration->isDomainWhitelisted($email)) {
            $message = ['error' => 'The domain is not whitelisted.'];
            break;
        }

        if (!is_null($userName) && !is_null($email) && !is_null($fullName)) {
            try {
                $message = $registration->createUser($userName, $fullName, $email, $isVisible);
            } catch (Exception | TransportExceptionInterface $exception) {
                $message = ['error' => $exception->getMessage()];
            }
        } else {
            $message = ['error' => Translation::get('err_sendMail')];
        }
        break;

    case 'savevoting':
        $faq = new Faq($faqConfig);
        $rating = new Rating($faqConfig);
        $type = Filter::filterInput(INPUT_POST, 'type', FILTER_UNSAFE_RAW, 'faq');
        $recordId = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterInput(INPUT_POST, 'vote', FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP);

        if (isset($vote) && $rating->check($recordId, $userIp) && $vote > 0 && $vote < 6) {
            try {
                $faqSession->userTracking('save_voting', $recordId);
            } catch (Exception) {
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
                'success' => Translation::get('msgVoteThanks'),
                'rating' => $rating->getVotingResult($recordId),
            ];
        } elseif (!$rating->check($recordId, $userIp)) {
            try {
                $faqSession->userTracking('error_save_voting', $recordId);
            } catch (Exception $exception) {
                $message = ['error' => $exception->getMessage()];
            }
            $message = ['error' => Translation::get('err_VoteTooMuch')];
        } else {
            try {
                $faqSession->userTracking('error_save_voting', $recordId);
            } catch (Exception $exception) {
                $message = ['error' => $exception->getMessage()];
            }
            $message = ['error' => Translation::get('err_noVote')];
        }

        break;

    //
    // Send mails from contact form
    //
    case 'submit-contact':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_UNSAFE_RAW));
        $email = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
        $question = trim((string) Filter::filterVar($postData['question'], FILTER_UNSAFE_RAW));

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->getAdminEmail();
        }

        if (!empty($author) && !empty($email) && !empty($question) && $stopWords->checkBannedWord($question)) {
            $question = sprintf(
                "%s: %s\n%s: %s\n\n %s",
                Translation::get('msgNewContentName'),
                $author,
                Translation::get('msgNewContentMail'),
                $email,
                $question
            );

            $mailer = new Mail($faqConfig);
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($faqConfig->getAdminEmail());
                $mailer->subject = Utils::resolveMarkers('Feedback: %sitename%', $faqConfig);
                $mailer->message = $question;
                $mailer->send();
                unset($mailer);
                $message = ['success' => Translation::get('msgMailContact')];
            } catch (Exception | TransportExceptionInterface $e) {
                $message = ['error' => $e->getMessage()];
            }
        } else {
            $message = ['error' => Translation::get('err_sendMail')];
        }
        break;

    // Send mails to friends
    case 'sendtofriends':
        $author = Filter::filterInput(INPUT_POST, 'name', FILTER_UNSAFE_RAW);
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $link = Filter::filterInput(INPUT_POST, 'link', FILTER_VALIDATE_URL);
        $attached = Filter::filterInput(INPUT_POST, 'message', FILTER_UNSAFE_RAW);
        $mailto = Filter::filterInputArray(
            INPUT_POST,
            [
                'mailto' => [
                    'filter' => FILTER_VALIDATE_EMAIL,
                    'flags' => FILTER_REQUIRE_ARRAY | FILTER_NULL_ON_FAILURE,
                ],
            ]
        );

        if (
            !is_null($author) && !is_null($email) && is_array($mailto) && !empty($mailto['mailto'][0]) &&
            $stopWords->checkBannedWord(Strings::htmlspecialchars($attached))
        ) {
            foreach ($mailto['mailto'] as $recipient) {
                $recipient = trim(strip_tags((string) $recipient));
                if (!empty($recipient)) {
                    $mailer = new Mail($faqConfig);
                    try {
                        $mailer->setReplyTo($email, $author);
                        $mailer->addTo($recipient);
                    } catch (Exception $exception) {
                        $message = ['error' => $exception->getMessage()];
                    }
                    $mailer->subject = Translation::get('msgS2FMailSubject') . $author;
                    $mailer->message = sprintf(
                        "%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $faqConfig->get('main.send2friendText'),
                        Translation::get('msgS2FText2'),
                        $link,
                        $attached
                    );

                    // Send the email
                    $result = $mailer->send();
                    unset($mailer);
                    usleep(250);
                }
            }

            $message = ['success' => Translation::get('msgS2FThx')];
        } else {
            $message = ['error' => Translation::get('err_sendMail')];
        }
        break;

    //
    // Save user data from UCP
    //
    case 'submit-user-data':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($postData[Token::PMF_SESSION_NAME], FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('ucp', $csrfToken)) {
            $message = ['error' => Translation::get('ad_msg_noauth')];
            break;
        }

        $userId = Filter::filterVar($postData['userid'], FILTER_VALIDATE_INT);
        $userName = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
        $isVisible = Filter::filterVar($postData['is_visible'], FILTER_UNSAFE_RAW);
        $password = trim((string) Filter::filterVar($postData['faqpassword'], FILTER_UNSAFE_RAW));
        $confirm = trim((string) Filter::filterVar($postData['faqpassword_confirm'], FILTER_UNSAFE_RAW));
        $twofactorEnabled = Filter::filterInput(INPUT_POST, 'twofactor_enabled', FILTER_UNSAFE_RAW);
        $delete_secret = Filter::filterInput(INPUT_POST, 'newsecret', FILTER_UNSAFE_RAW);

        $user = CurrentUser::getFromSession($faqConfig);
        
        if($delete_secret=='on') {
            $secret = "";
        }
        else {
            $secret = $user->getUserData('secret');
        }

        if ($userId !== $user->getUserId()) {
            $http->setStatus(400);
            $message = ['error' => 'User ID mismatch!'];
            break;
        }

        if ($password !== $confirm) {
            $http->setStatus(409);
            $message = ['error' => Translation::get('ad_user_error_passwordsDontMatch')];
            break;
        }

        if (strlen($password) <= 7 || strlen($confirm) <= 7) {
            $http->setStatus(409);
            $message = ['error' => Translation::get('ad_passwd_fail')];
            break;
        } else {
            $userData = [
                'display_name' => $userName,
                'email' => $email,
                'is_visible' => $isVisible === 'on' ? 1 : 0,
                'twofactor_enabled' => $twofactorEnabled === 'on' ? 1 : 0,
                'secret' => $secret
            ];
            $success = $user->setUserData($userData);

            foreach ($user->getAuthContainer() as $auth) {
                if ($auth->setReadOnly()) {
                    continue;
                }
                if (!$auth->update($user->getLogin(), $password)) {
                    $http->setStatus(400);
                    $message = ['error' => $auth->error()];
                    $success = false;
                } else {
                    $success = true;
                }
            }
        }

        if ($success) {
            $http->setStatus(200);
            $message = ['success' => Translation::get('ad_entry_savedsuc')];
        } else {
            $http->setStatus(400);
            $message = ['error' => Translation::get('ad_entry_savedfail')];
        }
        break;

    //
    // Change password
    //
    case 'change-password':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $username = trim((string) Filter::filterVar($postData['username'], FILTER_UNSAFE_RAW));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));

        if (!empty($username) && !empty($email)) {
            $user = new CurrentUser($faqConfig);
            $loginExist = $user->getUserByLogin($username);

            if ($loginExist && ($email == $user->getUserData('email'))) {
                try {
                    $newPassword = $user->createPassword();
                } catch (Exception $exception) {
                    $http->setStatus(400);
                    $message = ['error' => $exception->getMessage()];
                }
                try {
                    $user->changePassword($newPassword);
                } catch (Exception $exception) {
                    $http->setStatus(400);
                    $message = ['error' => $exception->getMessage()];
                }
                $text = Translation::get('lostpwd_text_1') . "\nUsername: " . $username . "\nNew Password: " .
                    $newPassword . "\n\n" . Translation::get('lostpwd_text_2');

                $mailer = new Mail($faqConfig);
                try {
                    $mailer->addTo($email);
                } catch (Exception $exception) {
                    $http->setStatus(400);
                    $message = ['error' => $exception->getMessage()];
                }
                $mailer->subject = Utils::resolveMarkers('[%sitename%] Username / password request', $faqConfig);
                $mailer->message = $text;
                try {
                    $result = $mailer->send();
                } catch (Exception | TransportExceptionInterface $exception) {
                    $http->setStatus(400);
                    $message = ['error' => $exception->getMessage()];
                }
                unset($mailer);
                // Trust that the email has been sent
                $message = ['success' => Translation::get('lostpwd_mail_okay')];
            } else {
                $http->setStatus(409);
                $message = ['error' => Translation::get('lostpwd_err_1')];
            }
        } else {
            $http->setStatus(409);
            $message = ['error' => Translation::get('lostpwd_err_2')];
        }
        break;

    //
    // Request removal of user
    //
    case 'submit-request-removal':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($postData[Token::PMF_SESSION_NAME], FILTER_UNSAFE_RAW);
        if (!Token::getInstance()->verifyToken('request-removal', $csrfToken)) {
            $message = ['error' => 'TOKEN' . Translation::get('ad_msg_noauth')];
            break;
        }

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_UNSAFE_RAW));
        $loginName = trim((string) Filter::filterVar($postData['loginname'], FILTER_UNSAFE_RAW));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $question = trim((string) Filter::filterVar($postData['question'], FILTER_UNSAFE_RAW));

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->getAdminEmail();
        }

        if (!empty($author) && !empty($email) && !empty($question) && $stopWords->checkBannedWord($question)) {
            $question = sprintf(
                "%s %s\n%s %s\n%s %s\n\n %s",
                Translation::get('ad_user_loginname'),
                $loginName,
                Translation::get('msgNewContentName'),
                $author,
                Translation::get('msgNewContentMail'),
                $email,
                $question
            );

            $mailer = new Mail($faqConfig);
            try {
                $mailer->setReplyTo($email, $author);
                $mailer->addTo($faqConfig->getAdminEmail());
                $mailer->subject = $faqConfig->getTitle() . ': Remove User Request';
                $mailer->message = $question;
                $result = $mailer->send();
                unset($mailer);

                $http->setStatus(200);
                $message = ['success' => Translation::get('msgMailContact')];
            } catch (Exception | TransportExceptionInterface $exception) {
                $http->setStatus(400);
                $message = ['error' => $exception->getMessage()];
            }
        } else {
            $http->setStatus(400);
            $message = ['error' => Translation::get('err_sendMail')];
        }
        break;
}

try {
    $http->sendJsonWithHeaders($message);
} catch (JsonException) {
}
exit();
