<?php

/**
 * The API Service Layer.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @deprecated will be migrated to api/index.php
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2010-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2010-09-15
 */

const IS_VALID_PHPMYFAQ = null;

use phpMyFAQ\Captcha\Captcha;
use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\Comment;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\FaqHelper;
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
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$faqConfig = Configuration::getConfigurationInstance();

$postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

$apiLanguage = Filter::filterVar($postData['lang'], FILTER_SANITIZE_SPECIAL_CHARS);
$currentToken = Filter::filterVar($postData['csrf'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
$action = Filter::filterVar($request->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);

if ($faqConfig->get('security.enableGoogleReCaptchaV2')) {
    $code = Filter::filterVar($postData['g-recaptcha-response'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
} else {
    $code = Filter::filterVar($postData['captcha'] ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
}

$Language = new Language($faqConfig);
$languageCode = $Language->setLanguage($faqConfig->get('main.languageDetection'), $faqConfig->get('main.language'));
require_once __DIR__ . '/translations/language_en.php';
$faqConfig->setLanguage($Language);

if (Language::isASupportedLanguage($apiLanguage)) {
    $languageCode = trim((string) $apiLanguage);
    require_once 'translations/language_' . $languageCode . '.php';
} else {
    $languageCode = 'en';
    require_once __DIR__ . '/translations/language_en.php';
}

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_TRANSLATION_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($languageCode);
} catch (Exception $exception) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => $exception->getMessage()]);
}

//
// Initializing static string wrapper
//
Strings::init($languageCode);

//
// Check, if user is logged in
//
$user = CurrentUser::getCurrentUser($faqConfig);

$faqSession = new Session($faqConfig);
$faqSession->setCurrentUser($user);
$network = new Network($faqConfig);
$stopWords = new StopWords($faqConfig);
$faqHelper = new FaqHelper($faqConfig);

if ($network->isBanned($request->server->get('REMOTE_ADDR'))) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => Translation::get('err_bannedIP')]);
}

//
// Check captcha
//
$captcha = Captcha::getInstance($faqConfig);
$captcha->setUserIsLoggedIn($user->isLoggedIn());

$fatalError = false;

if (
    'add-voting' !== $action && 'submit-user-data' !== $action && 'change-password' !== $action &&
    'submit-request-removal' !== $action && !$captcha->checkCaptchaCode($code ?? '')
) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => Translation::get('msgCaptcha')]);
    $fatalError = true;
}

//
// Check if the user is logged in when FAQ is completely secured
//
if (
    !$user->isLoggedIn() && $faqConfig->get('security.enableLoginOnly') && 'submit-request-removal' !== $action &&
    'change-password' !== $action && 'save-registration' !== $action
) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => Translation::get('ad_msg_noauth')]);
    $fatalError = true;
}

if ($fatalError) {
    $response->send();
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
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
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
            case 'faq':
                $id = $faqId;
                break;
        }

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($mailer)) {
            $mailer = $faqConfig->getAdminEmail();
        }

        // Check display name and e-mail address for not logged-in users
        if (!$user->isLoggedIn()) {
            $user = new User($faqConfig);
            if ($user->checkDisplayName($username) && $user->checkMailAddress($mailer)) {
                $response->setStatusCode(Response::HTTP_CONFLICT);
                $response->setData(['error' => Translation::get('err_SaveComment')]);
                $faqConfig->getLogger()->error('Name and mail already used by registered user.');
                break;
            }
        }

        if (
            !is_null($username) && !is_null($mailer) && !is_null($comment) && $stopWords->checkBannedWord($comment) &&
            !$faq->commentDisabled($id, $languageCode, $type) && !$faq->isActive($id, $languageCode, $type)
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
                ->setComment(nl2br(strip_tags((string) $comment)))
                ->setDate($request->server->get('REQUEST_TIME'));

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

                        if ($catOwnerEmail !== '' && (!isset($send[$catOwnerEmail]) && $catOwnerEmail !== $emailTo)) {
                            $mailer->addCc($catOwnerEmail);
                            $send[$catOwnerEmail] = 1;
                        }
                    }
                }

                $mailer->subject = $faqConfig->getTitle() . ': New comment for "' . $title . '"';
                $mailer->message = strip_tags($commentMail);

                $result = $mailer->send();
                unset($mailer);

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('msgCommentThanks')]);
            } else {
                try {
                    $faqSession->userTracking('error_save_comment', $id);
                } catch (Exception) {
                    // @todo handle the exception
                }

                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('err_SaveComment')]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Please add your name, your e-mail address and a comment!']);
        }

        break;

    case 'add-faq':
        if (
            !$faqConfig->get('records.allowNewFaqsForGuests') &&
            !$user->perm->hasPermission($user->getUserId(), 'addfaq')
        ) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            break;
        }

        $faq = new Faq($faqConfig);
        $category = new Category($faqConfig);
        $questionObject = new Question($faqConfig);

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $question = Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS);
        $question = trim(strip_tags((string) $question));
        if ($faqConfig->get('main.enableWysiwygEditorFrontend')) {
            $answer = Filter::filterVar($postData['answer'], FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = trim(html_entity_decode((string) $answer));
        } else {
            $answer = Filter::filterVar($postData['answer'], FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = strip_tags((string) $answer);
            $answer = trim(nl2br($answer));
        }

        $contentLink = Filter::filterVar($postData['contentlink'], FILTER_VALIDATE_URL);
        $keywords = Filter::filterVar($postData['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);
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
            $faqLanguage = Filter::filterVar($postData['lang'], FILTER_SANITIZE_SPECIAL_CHARS);
            $answer = trim((string) Filter::filterVar($postData['translated_answer'], FILTER_SANITIZE_SPECIAL_CHARS));
        }

        if (
            !is_null($author) && !is_null($email) && ($question !== '' && $question !== '0') &&
            $stopWords->checkBannedWord(strip_tags($question)) &&
            ($answer !== '' && $answer !== '0') && $stopWords->checkBannedWord(strip_tags($answer))
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
                ->setLanguage(($isTranslation ? $newLanguage : $languageCode))
                ->setQuestion($question)
                ->setActive(($autoActivate ? FAQ_SQL_ACTIVE_YES : FAQ_SQL_ACTIVE_NO))
                ->setSticky(false)
                ->setAnswer($answer)
                ->setKeywords($keywords)
                ->setAuthor($author)
                ->setEmail($email)
                ->setComment(true)
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
            } catch (Exception | TransportExceptionInterface $e) {
                $faqConfig->getLogger()->info('Notification could not be sent: ', [ $e->getMessage() ]);
            }

            if ($faqConfig->get('records.defaultActivation')) {
                $link = [
                    'link' => $faqHelper->createFaqUrl($faqEntity, $categories[0]),
                    'info' => Translation::get('msgRedirect')
                ];
            } else {
                $link = [];
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData([
                'success' =>
                    ($isNew ? Translation::get('msgNewContentThanks') : Translation::get('msgNewTranslationThanks')),
                ... $link
            ]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_SaveEntries')]);
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
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            break;
        }

        $faq = new Faq($faqConfig);
        $cat = new Category($faqConfig);
        $questionObject = new Question($faqConfig);
        $categories = $cat->getAllCategories();

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $ucategory = Filter::filterVar($postData['category'], FILTER_VALIDATE_INT);
        $question = Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS);
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

        if ($author !== '' && $author !== '0' && ($email !== '' && $email !== '0') && ($question !== '' && $question !== '0') && $stopWords->checkBannedWord($question)) {
            $visibility = $faqConfig->get('records.enableVisibilityQuestions') ? 'Y' : 'N';

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
                $plr = new Plurals();
                $searchResult = [];
                $mergedResult = [];

                foreach ($cleanQuestion as $word) {
                    if (!empty($word)) {
                        try {
                            $searchResult[] = $faqSearch->search($word, false);
                        } catch (Exception) {
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
                    $smartAnswer = sprintf(
                        '<h5>%s</h5>',
                        $plr->getMsg('plmsgSearchAmount', $faqSearchResult->getNumberOfResults())
                    );

                    $smartAnswer .= '<ul>';

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
                            $smartAnswer .= sprintf(
                                '<li>%s<br><small class="pmf-search-preview">%s...</small></li>',
                                $oLink->toHtmlAnchor(),
                                $faqHelper->renderAnswerPreview($result->answer, 10)
                            );
                        } catch (Exception) {
                            // handle exception
                        }
                    }

                    $smartAnswer .= '</ul>';

                    $response->setData(['result' => $smartAnswer]);
                } else {
                    $questionObject->addQuestion($questionData);
                    $questionHelper = new QuestionHelper($faqConfig, $cat);
                    try {
                        $questionHelper->sendSuccessMail($questionData, $categories);
                    } catch (Exception | TransportExceptionInterface $exception) {
                        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                        $response->setData(['error' => $exception->getMessage()]);
                    }

                    $response->setStatusCode(Response::HTTP_OK);
                    $response->setData(['success' => Translation::get('msgAskThx4Mail')]);
                }
            } else {
                $questionObject->addQuestion($questionData);
                $questionHelper = new QuestionHelper($faqConfig, $cat);
                try {
                    $questionHelper->sendSuccessMail($questionData, $categories);
                } catch (Exception | TransportExceptionInterface $exception) {
                    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                    $response->setData(['error' => $exception->getMessage()]);
                }

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('msgAskThx4Mail')]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_SaveQuestion')]);
        }

        break;

    case 'save-registration':
        $registration = new RegistrationHelper($faqConfig);

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $fullName = trim((string) Filter::filterVar($postData['realname'], FILTER_SANITIZE_SPECIAL_CHARS));
        $userName = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $isVisible = Filter::filterVar($postData['is_visible'], FILTER_SANITIZE_SPECIAL_CHARS) ?? false;

        if (!$registration->isDomainAllowed($email)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'The domain is not whitelisted.']);
            break;
        }

        if (!is_null($userName) && !is_null($email) && !is_null($fullName)) {
            try {
                $response->setData($registration->createUser($userName, $fullName, $email, $isVisible));
            } catch (Exception | TransportExceptionInterface $exception) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $exception->getMessage()]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_sendMail')]);
        }

        break;

    case 'add-voting':
        $faq = new Faq($faqConfig);
        $rating = new Rating($faqConfig);

        $faqId = Filter::filterVar($postData['id'] ?? null, FILTER_VALIDATE_INT, 0);
        $vote = Filter::filterVar($postData['value'], FILTER_VALIDATE_INT);
        $userIp = Filter::filterVar($request->server->get('REMOTE_ADDR'), FILTER_VALIDATE_IP);

        if (isset($vote) && $rating->check($faqId, $userIp) && $vote > 0 && $vote < 6) {
            try {
                $faqSession->userTracking('save_voting', $faqId);
            } catch (Exception) {
                // @todo handle the exception
            }

            $votingData = [
                'record_id' => $faqId,
                'vote' => $vote,
                'user_ip' => $userIp,
            ];

            if ($rating->getNumberOfVotings($faqId) === 0) {
                $rating->addVoting($votingData);
            } else {
                $rating->update($votingData);
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData([
                'success' => Translation::get('msgVoteThanks'),
                'rating' => $rating->getVotingResult($faqId),
            ]);
        } elseif (!$rating->check($faqId, $userIp)) {
            try {
                $faqSession->userTracking('error_save_voting', $faqId);
            } catch (Exception $exception) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $exception->getMessage()]);
            }

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_VoteTooMuch')]);
        } else {
            try {
                $faqSession->userTracking('error_save_voting', $faqId);
            } catch (Exception $exception) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $exception->getMessage()]);
            }

            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_noVote')]);
        }

        break;

    //
    // Send mails from contact form
    //
    case 'submit-contact':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
        $question = trim((string) Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS));

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->getAdminEmail();
        }

        if ($author !== '' && $author !== '0' && !empty($email) && ($question !== '' && $question !== '0') && $stopWords->checkBannedWord($question)) {
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

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('msgMailContact')]);
            } catch (Exception | TransportExceptionInterface $e) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $e->getMessage()]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_sendMail')]);
        }

        break;

    // Send mails to friends
    case 'sendtofriends':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $author = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL);
        $attached = trim((string) Filter::filterVar($postData['message'], FILTER_SANITIZE_SPECIAL_CHARS));
        $mailto = Filter::filterArray($postData['mailto[]']);

        $faqLanguage = trim((string) Filter::filterVar($postData['lang'], FILTER_SANITIZE_SPECIAL_CHARS));
        $faqId = trim((string) Filter::filterVar($postData['faqId'], FILTER_VALIDATE_INT));
        $categoryId = trim((string) Filter::filterVar($postData['categoryId'], FILTER_VALIDATE_INT));

        if (is_array($mailto) && count($mailto) > 5) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_sendMail')]);
            break;
        }

        if (
            !is_null($author) && !is_null($email) && is_array($mailto) &&
            $stopWords->checkBannedWord(Strings::htmlspecialchars($attached))
        ) {
            $send2friendLink = sprintf(
                '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                $faqConfig->getDefaultUrl(),
                $categoryId,
                $faqId,
                urlencode($faqLanguage)
            );

            foreach ($mailto as $recipient) {
                $recipient = trim(strip_tags((string) $recipient));
                if ($recipient !== '' && $recipient !== '0') {
                    $mailer = new Mail($faqConfig);
                    try {
                        $mailer->setReplyTo($email, $author);
                        $mailer->addTo($recipient);
                    } catch (Exception $exception) {
                        $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                        $response->setData(['error' => $exception->getMessage()]);
                    }

                    $mailer->subject = Translation::get('msgS2FMailSubject') . $author;
                    $mailer->message = sprintf(
                        "%s\r\n\r\n%s\r\n%s\r\n\r\n%s",
                        $faqConfig->get('main.send2friendText'),
                        Translation::get('msgS2FText2'),
                        $send2friendLink,
                        strip_tags($attached)
                    );

                    // Send the email
                    $result = $mailer->send();
                    unset($mailer);
                    usleep(250);
                }
            }

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => Translation::get('msgS2FThx')]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_sendMail')]);
        }

        break;

    //
    // Request removal of user
    //
    case 'submit-request-removal':
        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);

        $csrfToken = Filter::filterVar($postData[Token::PMF_SESSION_NAME], FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance()->verifyToken('request-removal', $csrfToken)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('ad_msg_noauth')]);
            break;
        }

        $userId = Filter::filterVar($postData['userId'], FILTER_VALIDATE_INT);
        $author = trim((string) Filter::filterVar($postData['name'], FILTER_SANITIZE_SPECIAL_CHARS));
        $loginName = trim((string) Filter::filterVar($postData['loginname'], FILTER_SANITIZE_SPECIAL_CHARS));
        $email = trim((string) Filter::filterVar($postData['email'], FILTER_VALIDATE_EMAIL));
        $question = trim((string) Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS));

        // If e-mail address is set to optional
        if (!$faqConfig->get('main.optionalMailAddress') && is_null($email)) {
            $email = $faqConfig->getAdminEmail();
        }

        // Validate User ID, Username and email
        $user = new User($faqConfig);
        if (
            !$user->getUserById($userId) ||
            $userId !== $user->getUserId() ||
            $loginName !== $user->getLogin() ||
            $email !== $user->getUserData('email')
        ) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('ad_user_error_loginInvalid')]);
            break;
        }

        if ($author !== '' && $author !== '0' && ($email !== '' && $email !== '0') && ($question !== '' && $question !== '0') && $stopWords->checkBannedWord($question)) {
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

                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('msgMailContact')]);
            } catch (Exception | TransportExceptionInterface $exception) {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => $exception->getMessage()]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => Translation::get('err_sendMail')]);
        }

        break;
}

$response->send();
