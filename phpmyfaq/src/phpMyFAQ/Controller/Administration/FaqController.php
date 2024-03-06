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
     * @throws Exception
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
     * @throws Exception
     */
    #[Route('admin/api/faq/activate')]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_APPROVE);

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
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
                return $this->json(['success' => Translation::get('ad_entry_savedsuc')], Response::HTTP_OK);
            } else {
                return $this->json(['error' => Translation::get('ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => 'No FAQ IDs provided.'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
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
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
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
                return $this->json(['success' => Translation::get('ad_entry_savedsuc')], Response::HTTP_OK);
            } else {
                return $this->json(['error' => Translation::get('ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
            }
        } else {
            return $this->json(['error' => 'No FAQ IDs provided.'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faq/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        $faq = new Faq($configuration);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $adminLog = new AdminLog($configuration);
        $adminLog->log($user, 'Deleted FAQ ID ' . $faqId);

        try {
            $faq->deleteRecord($faqId, $faqLanguage);
        } catch (FileException | AttachmentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => Translation::get('ad_entry_delsuc')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faq/search')]
    public function search(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('edit-faq', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
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

            return $this->json(
                ['success' => $searchHelper->renderAdminSuggestionResult($searchResultSet)],
                Response::HTTP_OK
            );
        } else {
            return $this->json(['error' => 'No search string provided.'], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faqs/sticky/order')]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('order-stickyfaqs', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $faq = new Faq(Configuration::getConfigurationInstance());
        $faq->setStickyFaqOrder($data->faqIds);

        return $this->json(['success' => Translation::get('ad_categ_save_order')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/faq/import')]
    public function import(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        $file = $request->files->get('file');
        if (!isset($file)) {
            return $this->json(['error' => 'Bad request: There is no file submitted.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance()->verifyToken('importfaqs', $request->request->get('csrf'))) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $faqImport = new FaqImport(Configuration::getConfigurationInstance());

        $errors = [];

        if (0 === $file->getError() && $faqImport->isCSVFile($file)) {
            $handle = fopen($file->getRealPath(), 'r');
            $csvData = $faqImport->parseCSV($handle);

            if (!$faqImport->validateCSV($csvData)) {
                $result = [
                    'storedAll' => false,
                    'error' => Translation::get('msgCSVFileNotValidated'),
                ];
                return $this->json($result, Response::HTTP_BAD_REQUEST);
            }

            foreach ($csvData as $record) {
                $error = $faqImport->import($record);
                if (!$error) {
                    $errors[] = $error;
                }
            }

            if ($errors === []) {
                $result = [
                    'storedAll' => true,
                    'success' => Translation::get('msgImportSuccessful'),
                ];
                return $this->json($result, Response::HTTP_OK);
            } else {
                $result = [
                    'storedAll' => false,
                    'messages' => $errors,
                ];
                return $this->json($result, Response::HTTP_BAD_REQUEST);
            }
        } else {
            $result = [
                'storedAll' => false,
                'error' => 'Bad request: The file is not a CSV file.',
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }
    }
}
