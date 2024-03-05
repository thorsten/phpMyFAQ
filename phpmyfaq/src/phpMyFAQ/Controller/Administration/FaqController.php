<?php

/**
 * The Admin FAQ Controller
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
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Faq\FaqImport;
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
    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[Route('admin/api/faq/permissions')]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission(Configuration::getConfigurationInstance());

        return $this->json(
            [
                'user' => $faqPermission->get(FaqPermission::USER, $faqId),
                'group' => $faqPermission->get(FaqPermission::GROUP, $faqId),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faqs')]
    public function listByCategory(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);

        $faq = new Faq(Configuration::getConfigurationInstance());

        return $this->json(
            [
                'faqs' => $faq->getAllFaqsByCategory($categoryId),
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[Route('admin/api/faq/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_APPROVE);

        $jsonResponse = new JsonResponse();

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        if (!($faqIds === false || $faqIds === [] || $faqIds === null)) {
            $faq = new Faq(Configuration::getConfigurationInstance());
            $success = false;

            foreach ($faqIds as $faqId) {
                if (Language::isASupportedLanguage($faqLanguage)) {
                    $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'active');
                }
            }

            if ($success) {
                $jsonResponse->setStatusCode(Response::HTTP_OK);
                $jsonResponse->setData(['success' => Translation::get('ad_entry_savedsuc')]);
            } else {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $jsonResponse->setData(['error' => Translation::get('ad_entry_savedfail')]);
            }
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/faq/sticky')]
    public function sticky(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $jsonResponse = new JsonResponse();

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        if (!($faqIds === false || $faqIds === [] || $faqIds === null)) {
            $faq = new Faq(Configuration::getConfigurationInstance());
            $success = false;

            foreach ($faqIds as $faqId) {
                if (Language::isASupportedLanguage($faqLanguage)) {
                    $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked ?? false, 'sticky');
                }
            }

            if ($success) {
                $jsonResponse->setStatusCode(Response::HTTP_OK);
                $jsonResponse->setData(['success' => Translation::get('ad_entry_savedsuc')]);
            } else {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $jsonResponse->setData(['error' => Translation::get('ad_entry_savedfail')]);
            }
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'No FAQ IDs provided.']);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/faq/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        $faq = new Faq($configuration);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $adminLog = new AdminLog($configuration);
        $adminLog->log($user, 'Deleted FAQ ID ' . $faqId);

        try {
            $faq->deleteRecord($faqId, $faqLanguage);
        } catch (FileException | AttachmentException $e) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => $e->getMessage()]);

            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData(['success' => Translation::get('ad_entry_delsuc')]);

        return $jsonResponse;
    }

    #[Route('admin/api/faq/search')]
    public function search(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('edit-faq', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $faqPermission = new FaqPermission($configuration);
        $faqSearch = new Search($configuration);
        $faqSearch->setCategory(new Category($configuration));

        $searchResultSet = new SearchResultSet($user, $faqPermission, $configuration);
        $searchString = Filter::filterVar($data->search, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($searchString)) {
            $searchResult = $faqSearch->search($searchString, false);

            $searchResultSet->reviewResultSet($searchResult);

            $searchHelper = new SearchHelper($configuration);
            $searchHelper->setSearchTerm($searchString);

            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData(
                ['success' => $searchHelper->renderAdminSuggestionResult($searchResultSet)]
            );
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'No search string provided.']);
        }

        return $jsonResponse;
    }

    #[Route('admin/api/faqs/sticky/order')]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $jsonResponse = new JsonResponse();
        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('order-stickyfaqs', $data->csrf)) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);
            $jsonResponse->send();
            exit();
        }

        $faq = new Faq(Configuration::getConfigurationInstance());
        $faq->setStickyFaqOrder($data->faqIds);

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData(['success' => Translation::get('ad_categ_save_order')]);

        return $jsonResponse;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[Route('admin/api/faq/import')]
    public function import(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        $jsonResponse = new JsonResponse();

        $file = $request->files->get('file');
        if (!isset($file)) {
            $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
            $jsonResponse->setData(['error' => 'Bad request: There is no file submitted.']);

            return $jsonResponse;
        }

        if (!Token::getInstance()->verifyToken('importfaqs', $request->request->get('csrf'))) {
            $jsonResponse->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $jsonResponse->setData(['error' => Translation::get('err_NotAuth')]);

            return $jsonResponse;
        }

        $faqImport = new FaqImport(Configuration::getConfigurationInstance());

        $result = [];
        $errors = [];

        if (0 === $file->getError() && $faqImport->isCSVFile($file)) {
            $handle = fopen($file->getRealPath(), 'r');
            $csvData = $faqImport->parseCSV($handle);

            if (!$faqImport->validateCSV($csvData)) {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $result = [
                    'storedAll' => false,
                    'error' => Translation::get('msgCSVFileNotValidated'),
                ];
                $jsonResponse->setData($result);

                return $jsonResponse;
            }

            foreach ($csvData as $record) {
                $error = $faqImport->import($record);
                if (!$error) {
                    $errors[] = $error;
                }
            }

            if ($errors === []) {
                $jsonResponse->setStatusCode(Response::HTTP_OK);
                $result = [
                    'storedAll' => true,
                    'success' => Translation::get('msgImportSuccessful'),
                ];
            } else {
                $jsonResponse->setStatusCode(Response::HTTP_BAD_REQUEST);
                $result = [
                    'storedAll' => false,
                    'messages' => $errors,
                ];
            }

            $jsonResponse->setData($result);

            return $jsonResponse;
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
