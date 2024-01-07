<?php

/**
 * The Admin FAQ Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-28
 */

namespace phpMyFAQ\Controller\Administration;

use Exception;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FaqController extends AbstractController {

    #[Route('admin/api/faq/permissions')]
    public function listPermissions(Request $request): JsonResponse {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission($configuration);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(
                [
                    'user' => $faqPermission->get(FaqPermission::USER, $faqId),
                    'group' => $faqPermission->get(FaqPermission::GROUP, $faqId)
                ]
        );

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faqs')]
    public function listByCategory(Request $request): JsonResponse {
        $this->userHasPermission('edit_faq');

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);

        $faq = new Faq($configuration);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(
                [
                    'faqs' => $faq->getAllFaqsByCategory($categoryId)
                ]
        );

        return $response;
    }

    #[Route('admin/api/faq/activate')]
    public function activate(Request $request): JsonResponse {
        $this->userHasPermission('approverec');

        $response = new JsonResponse();

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (!empty($faqIds)) {
            $faq = new Faq(Configuration::getConfigurationInstance());
            $success = false;

            foreach ($faqIds as $faqId) {
                if (Language::isASupportedLanguage($faqLanguage)) {
                    $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'active');
                }
            }

            if ($success) {
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('ad_entry_savedsuc')]);
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('ad_entry_savedfail')]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faq/sticky')]
    public function sticky(Request $request): JsonResponse {
        $this->userHasPermission('edit_faq');

        $response = new JsonResponse();

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        if (!empty($faqIds)) {
            $faq = new Faq(Configuration::getConfigurationInstance());
            $success = false;

            foreach ($faqIds as $faqId) {
                if (Language::isASupportedLanguage($faqLanguage)) {
                    $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'sticky');
                }
            }

            if ($success) {
                $response->setStatusCode(Response::HTTP_OK);
                $response->setData(['success' => Translation::get('ad_entry_savedsuc')]);
            } else {
                $response->setStatusCode(Response::HTTP_BAD_REQUEST);
                $response->setData(['error' => Translation::get('ad_entry_savedfail')]);
            }
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faq/delete')]
    public function delete(Request $request): JsonResponse {
        $this->userHasPermission('delete_faq');

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        $faq = new Faq($configuration);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $adminLog = new AdminLog($configuration);
        $adminLog->log($user, 'Deleted FAQ ID ' . $faqId);

        try {
            $faq->deleteRecord($faqId, $faqLanguage);
        } catch (FileException | AttachmentException $e) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => $e->getMessage()]);
            return $response;
        }

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => Translation::get('ad_entry_delsuc')]);

        return $response;
    }

    #[Route('admin/api/faq/search')]
    public function search(Request $request): JsonResponse {
        $this->userHasPermission('edit_faq');

        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('edit-faq', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $faqPermission = new FaqPermission($configuration);
        $faqSearch = new Search($configuration);
        $faqSearch->setCategory(new Category($configuration));
        $faqSearchResult = new SearchResultSet($user, $faqPermission, $configuration);
        $searchString = Filter::filterVar($data->search, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($searchString)) {
            $searchResult = $faqSearch->search($searchString, false);

            $faqSearchResult->reviewResultSet($searchResult);

            $searchHelper = new SearchHelper($configuration);
            $searchHelper->setSearchTerm($searchString);

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(
                    ['success' => $searchHelper->renderAdminSuggestionResult($faqSearchResult)]
            );
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No search string provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faqs/sticky/order')]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse {
        $response = new JsonResponse();
        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('order-stickyfaqs', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $faq = new Faq(Configuration::getConfigurationInstance());
        $faq->setStickyFaqOrder($data->faqIds);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => Translation::get('ad_categ_save_order')]);

        return $response;
    }

    #[Route('admin/api/faq/add')]
    public function addFaq(Request $request): JsonResponse {
        $response = new JsonResponse();
        $data = json_decode($request->getContent());
        $faqConfig = Configuration::getConfigurationInstance();

        $language = new Language($faqConfig);
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        $user = CurrentUser::getCurrentUser($faqConfig);
        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($user);

        if (!Token::getInstance()->verifyToken('addFaq', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            $response->send();
            exit();
        }

        $faq = new Faq($faqConfig);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        if (isset($data->faq_id)) {
            $faqId = Filter::filterVar($data->faq_id, FILTER_VALIDATE_INT);
        } else {
            $faqId = null;
        }
        $languageCode = Filter::filterVar($data->languageCode, FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterVar($data->cat_id, FILTER_VALIDATE_INT);
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($data->is_active, FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($data->is_sticky, FILTER_VALIDATE_BOOLEAN);

        if ($faq->hasTitleAHash($question)) {
            $response->setStatusCode(400);
            $result = [
                'stored' => false,
                'error' => 'It is not allowed, that the question title contains a hash.'
            ];
            $response->setData($result);
            exit();
        }

        $categories = [$categoryId];
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

        $result = [
            'stored' => true
        ];
        $response->setData($result);

        return $response;
    }

}
