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
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\FaqHelper;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Link;
use phpMyFAQ\Network;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
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
}

$response->send();
