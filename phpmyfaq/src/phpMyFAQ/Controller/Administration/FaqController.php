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
 * @copyright 2023 phpMyFAQ Team
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

class FaqController extends AbstractController
{
    #[Route('admin/api/faq/permissions')]
    public function listPermissions(Request $request): JsonResponse
    {
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
    public function listByCategory(Request $request): JsonResponse
    {
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
    public function activate(Request $request): JsonResponse
    {
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

            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $success]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faq/sticky')]
    public function sticky(Request $request): JsonResponse
    {
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
            $response->setStatusCode(Response::HTTP_OK);
            $response->setData(['success' => $success]);
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faq/delete')]
    public function delete(Request $request): JsonResponse
    {
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
        $response->setData(['success' => Translation::get('ad_entry_delsuc') ]);

        return $response;
    }

    #[Route('admin/api/faq/search')]
    public function search(Request $request): JsonResponse
    {
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
                ['success' => $searchHelper->renderAdminSuggestionResult($faqSearchResult) ]
            );
        } else {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'No search string provided.']);
        }

        return $response;
    }

    #[Route('admin/api/faqs/sticky/order')]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse
    {
        $response = new JsonResponse();

        $data = json_decode($request->getContent());
        $faq = new Faq(Configuration::getConfigurationInstance());
        $faq->setStickyFaqOrder($data->faqIds);

        $response->setStatusCode(Response::HTTP_OK);
        $response->setData(['success' => Translation::get('ad_categ_save_order')]);

        return $response;
    }
}
