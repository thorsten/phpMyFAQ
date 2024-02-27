<?php

/**
 * The REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @deprecated will be migrated to api/index.php
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-09-03
 */

const IS_VALID_PHPMYFAQ = null;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqMetaData;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\Services;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

$action = Filter::filterVar($request->get('action'), FILTER_SANITIZE_SPECIAL_CHARS);
$lang = Filter::filterVar($request->get('lang'), FILTER_SANITIZE_SPECIAL_CHARS, 'en');
$categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
$recordId = Filter::filterVar($request->get('recordId'), FILTER_VALIDATE_INT);
$tagId = Filter::filterVar($request->get('tagId'), FILTER_VALIDATE_INT);

$faqUsername = Filter::filterVar($request->request->get('username'), FILTER_SANITIZE_SPECIAL_CHARS);
$faqPassword = Filter::filterVar(
    $request->request->get('password'),
    FILTER_SANITIZE_SPECIAL_CHARS,
    FILTER_FLAG_NO_ENCODE_QUOTES
);

//
// Get language (default: english)
//
$language = new Language($faqConfig);
$currentLanguage = $language->setLanguageByAcceptLanguage();

//
// Set language
//
if (Language::isASupportedLanguage($currentLanguage)) {
    require PMF_TRANSLATION_DIR . '/language_' . $currentLanguage . '.php';
} else {
    require PMF_TRANSLATION_DIR . '/language_en.php';
}

$faqConfig->setLanguage($language);

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_TRANSLATION_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage($currentLanguage);
} catch (Exception $exception) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData(['error' => $exception->getMessage()]);
}

//
// Initializing static string wrapper
//
Strings::init($currentLanguage);

//
// Set empty result
//
$result = [];

//
// Check if user is already authenticated
//
$user = CurrentUser::getCurrentUser($faqConfig);
[ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

//
// Handle actions
//
switch ($action) {
    case 'faq':
        //
        // GET
        //
        $filter = Filter::filterInput(INPUT_GET, 'filter', FILTER_SANITIZE_SPECIAL_CHARS);
        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        if ($request->getMethod() === 'GET' && $recordId > 0) {
            $faq->getRecord($recordId);
            $result = $faq->faqRecord;

            if ((is_countable($result) ? count($result) : 0) === 0 || $result['solution_id'] === 42) {
                $result = new stdClass();
                $response->setStatusCode(Response::HTTP_NOT_FOUND);
            }

            if ('pdf' === $filter) {
                $service = new Services($faqConfig);
                $service->setFaqId($recordId);
                $service->setLanguage($currentLanguage);
                $service->setCategoryId($categoryId);

                $result = $service->getPdfApiLink();
            }

            $response->setData($result);
            break;
        }

        //
        // POST or PUT
        //
        if ($faqConfig->get('api.apiClientToken') !== $request->headers->get('x-pmf-token')) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $result = [
                'stored' => false,
                'error' => 'X_PMF_Token not valid.'
            ];
            $response->setData($result);
            break;
        }

        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $postData = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
        $faqId = isset($postData['faq-id']) ? Filter::filterVar($postData['faq-id'], FILTER_VALIDATE_INT) : null;

        $languageCode = Filter::filterVar($postData['language'], FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterVar($postData['category-id'], FILTER_VALIDATE_INT);
        if (isset($postData['category-name'])) {
            $categoryName = Filter::filterVar($postData['category-name'], FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $categoryName = null;
        }

        $question = Filter::filterVar($postData['question'], FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($postData['answer'], FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($postData['keywords'], FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($postData['author'], FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($postData['email'], FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($postData['is-active'], FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($postData['is-sticky'], FILTER_VALIDATE_BOOLEAN);

        // Check if category name can be mapped
        if (!is_null($categoryName)) {
            $categoryIdFound = $category->getCategoryIdFromName($categoryName);
            if ($categoryIdFound === false) {
                $response->setStatusCode(Response::HTTP_CONFLICT);
                $result = [
                    'stored' => false,
                    'error' => 'The given category name was not found.'
                ];
                $response->setData($result);
                break;
            }

            $categoryId = $categoryIdFound;
        }

        if ($faq->hasTitleAHash($question)) {
            $response->setStatusCode(\Symfony\Component\HttpFoundation\Response::HTTP_BAD_REQUEST);
            $result = [
                'stored' => false,
                'error' => 'It is not allowed, that the question title contains a hash.'
            ];
            $response->setData($result);
            break;
        }

        $categories = [ $categoryId ];
        $isActive = !is_null($isActive);
        $isSticky = !is_null($isSticky);

        $faqData = new FaqEntity();
        $faqData
            ->setLanguage($languageCode)
            ->setQuestion($question)
            ->setAnswer($answer)
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($isActive)
            ->setSticky($isSticky)
            ->setComment(false)
            ->setNotes('');

        if (is_null($faqId)) {
            $faqId = $faq->create($faqData);
        } else {
            $faqData->setId($faqId);
            $faqData->setRevisionId(0);
            $faq->update($faqData);
        }

        if ($request->getMethod() !== 'PUT') {
            $faqMetaData = new FaqMetaData($faqConfig);
            $faqMetaData->setFaqId($faqId)->setFaqLanguage($languageCode)->setCategories($categories)->save();
        }

        $result = [
            'stored' => true
        ];
        $response->setData($result);
        break;
}

//
// Check if the FAQ should be secured
//
if (!$user->isLoggedIn() && $faqConfig->get('security.enableLoginOnly')) {
    $response->setStatusCode(Response::HTTP_FORBIDDEN);
    $response->setData([ 'error' => 'You are not allowed to view this content.' ]);
}

$response->send();
