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

use DateTime;
use Exception;
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Revision;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission as FaqPermission;
use phpMyFAQ\Faq\Import;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Notification;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Annotation\Route;

class FaqController extends AbstractController
{
    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[Route('admin/api/faq/create')]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        $user = CurrentUser::getCurrentUser($this->configuration);
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faqPermission = new FaqPermission($this->configuration);
        $categoryPermission = new CategoryPermission($this->configuration);
        $tagging = new Tags($this->configuration);
        $notification = new Notification($this->configuration);
        $logging = new AdminLog($this->configuration);
        $changelog = new Changelog($this->configuration);
        $visits = new Visits($this->configuration);
        $seo = new Seo($this->configuration);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = json_decode($request->getContent())->data;

        if (!Token::getInstance()->verifyToken('edit-faq', $data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);

        if (is_array($data->{'categories[]'})) {
            $categories = Filter::filterArray($data->{'categories[]'});
        } else {
            $categories = [Filter::filterVar($data->{'categories[]'}, FILTER_VALIDATE_INT)];
        }

        $recordLang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $tags = Filter::filterVar($data->tags, FILTER_SANITIZE_SPECIAL_CHARS);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL, '');
        $comment = Filter::filterVar($data->comment ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $changed = Filter::filterVar($data->changed, FILTER_SANITIZE_SPECIAL_CHARS);
        $notes = Filter::filterVar($data->notes, FILTER_SANITIZE_SPECIAL_CHARS);

        $serpTitle = Filter::filterVar($data->serpTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $serpDescription = Filter::filterVar($data->serpDescription, FILTER_SANITIZE_SPECIAL_CHARS);

        // Permissions
        $permissions = $faqPermission->createPermissionArray();

        $logging->log($user, 'admin-save-new-faq');

        if (empty($question) && empty($answer)) {
            return $this->json(['error' => Translation::get('msgNoQuestionAndAnswer')], Response::HTTP_CONFLICT);
        }

        $faqData = new FaqEntity();
        $faqData
            ->setLanguage($recordLang)
            ->setActive($active === 'yes')
            ->setSticky(($sticky !== 'no') ? $sticky : false)
            ->setQuestion(
                Filter::removeAttributes(html_entity_decode((string) $question, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            )
            ->setAnswer(
                Filter::removeAttributes(html_entity_decode((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            )
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setComment(!is_null($comment))
            ->setCreatedDate(new DateTime())
            ->setNotes(Filter::removeAttributes($notes));

        // Add new record and get that ID
        $faqData = $faq->create($faqData);

        if ($faqData->getId()) {
            // Create ChangeLog entry
            $changelog->add($faqData->getId(), $user->getUserId(), nl2br((string) $changed), $faqData->getLanguage());

            // Create the visit entry
            $visits->logViews($faqData->getId());

            $categoryRelation = new Relation($this->configuration, $category);
            $categoryRelation->add($categories, $faqData->getId(), $faqData->getLanguage());

            // Insert the tags
            if ($tags !== '') {
                $tagging->create($faqData->getId(), explode(',', trim((string) $tags)));
            }

            // Add user permissions
            $faqPermission->add(FaqPermission::USER, $faqData->getId(), $permissions['restricted_user']);
            $categoryPermission->add(CategoryPermission::USER, $categories, $permissions['restricted_user']);
            // Add group permission
            if ($this->configuration->get('security.permLevel') !== 'basic') {
                $faqPermission->add(FaqPermission::GROUP, $faqData->getId(), $permissions['restricted_groups']);
                $categoryPermission->add(
                    CategoryPermission::GROUP,
                    $categories,
                    $permissions['restricted_groups']
                );
            }

            // Add the SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setType(SeoType::FAQ)
                ->setReferenceId($faqData->getId())
                ->setReferenceLanguage($faqData->getLanguage())
                ->setTitle($serpTitle)
                ->setDescription($serpDescription);
            $seo->create($seoEntity);


            // Open question answered
            $questionObject = new Question($this->configuration);
            $openQuestionId = Filter::filterVar($data->openQuestionId, FILTER_VALIDATE_INT);
            if (0 !== $openQuestionId) {
                if ($this->configuration->get('records.enableDeleteQuestion')) { // deletes question
                    $questionObject->delete($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $questionObject->updateQuestionAnswer($openQuestionId, $faqData->getId(), $categories[0]);
                }

                $url = sprintf(
                    '%s?action=faq&cat=%d&id=%d&artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $categories[0],
                    $faqData->getId(),
                    $recordLang
                );
                $oLink = new Link($url, $this->configuration);

                // notify the user who added the question
                try {
                    $notifyEmail = Filter::filterVar($data->notifyEmail, FILTER_SANITIZE_EMAIL);
                    $notifyUser = Filter::filterVar($data->notifyUser, FILTER_SANITIZE_SPECIAL_CHARS);
                    $notification->sendOpenQuestionAnswered($notifyEmail, $notifyUser, $oLink->toString());
                } catch (Exception | TransportExceptionInterface $e) {
                    return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
                }
            }

            // Let the admin and the category owners to be informed by email of this new entry
            try {
                $categoryHelper = new CategoryHelper();
                $categoryHelper
                    ->setCategory($category)
                    ->setConfiguration($this->configuration);
                $moderators = $categoryHelper->getModerators($categories);
                $notification->sendNewFaqAdded($moderators, $faqData->getId(), $recordLang);
            } catch (Exception | TransportExceptionInterface $e) {
                return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            // If Elasticsearch is enabled, index new FAQ document
            if ($this->configuration->get('search.enableElasticsearch')) {
                $esInstance = new Elasticsearch($this->configuration);
                $esInstance->index(
                    [
                        'id' => $faqData->getId(),
                        'lang' => $faqData->getLanguage(),
                        'solution_id' => $faqData->getSolutionId(),
                        'question' => $faqData->getQuestion(),
                        'answer' => $faqData->getAnswer(),
                        'keywords' => $faqData->getKeywords(),
                        'category_id' => $categories[0]
                    ]
                );
            }

            return $this->json(
                [
                    'success' => Translation::get('ad_entry_savedsuc'),
                    'data' => $faqData->getJson(),
                ],
                Response::HTTP_OK
            );
        } else {
            return $this->json(['error' => Translation::get('ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[Route('admin/api/faq/update')]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $user = CurrentUser::getCurrentUser($this->configuration);
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faqPermission = new FaqPermission($this->configuration);
        $tagging = new Tags($this->configuration);
        $logging = new AdminLog($this->configuration);
        $changelog = new Changelog($this->configuration);
        $visits = new Visits($this->configuration);
        $seo = new Seo($this->configuration);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = json_decode($request->getContent())->data;

        if (!Token::getInstance()->verifyToken('edit-faq', $data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $solutionId = Filter::filterVar($data->solutionId, FILTER_VALIDATE_INT);
        $revisionId = Filter::filterVar($data->revisionId, FILTER_VALIDATE_INT);
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        if (is_array($data->{'categories[]'})) {
            $categories = Filter::filterArray($data->{'categories[]'});
        } else {
            $categories = [Filter::filterVar($data->{'categories[]'}, FILTER_VALIDATE_INT)];
        }
        $faqLang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $tags = Filter::filterVar($data->tags, FILTER_SANITIZE_SPECIAL_CHARS);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL, '');
        $comment = Filter::filterVar($data->comment ?? '', FILTER_SANITIZE_SPECIAL_CHARS);
        $changed = Filter::filterVar($data->changed, FILTER_SANITIZE_SPECIAL_CHARS);
        $notes = Filter::filterVar($data->notes, FILTER_SANITIZE_SPECIAL_CHARS);
        $revision = Filter::filterVar($data->revision ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);

        $serpTitle = Filter::filterVar($data->serpTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $serpDescription = Filter::filterVar($data->serpDescription, FILTER_SANITIZE_SPECIAL_CHARS);

        if (empty($question) && empty($answer)) {
            return $this->json(['error' => Translation::get('msgNoQuestionAndAnswer')], Response::HTTP_CONFLICT);
        }

        // Permissions
        $permissions = $faqPermission->createPermissionArray();

        $logging->log($user, 'admin-save-existing-faq ' . $faqId);
        if ($active === 'yes') {
            $logging->log($user, 'admin-publish-existing-faq ' . $faqId);
        }

        if ('yes' === $revision || $this->configuration->get('records.enableAutoRevisions')) {
            $faqRevision = new Revision($this->configuration);
            $faqRevision->create($faqId, $faqLang);
            ++$revisionId;
        }

        $faqData = new FaqEntity();
        $faqData
            ->setId($faqId)
            ->setLanguage($faqLang)
            ->setRevisionId($revisionId)
            ->setSolutionId($solutionId)
            ->setActive($active === 'yes')
            ->setSticky(($sticky !== 'no') ? $sticky : false)
            ->setQuestion(
                Filter::removeAttributes(html_entity_decode((string) $question, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            )
            ->setAnswer(
                Filter::removeAttributes(html_entity_decode((string) $content, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
            )
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setComment(!is_null($comment))
            ->setCreatedDate(new DateTime())
            ->setNotes(Filter::removeAttributes($notes));

        // Create ChangeLog entry
        $changelog->add($faqData->getId(), $user->getUserId(), (string) $changed, $faqData->getLanguage(), $revisionId);

        // Create the visit entry
        $visits->logViews($faqData->getId());

        // save or update the FAQ record
        if ($faq->hasTranslation($faqData->getId(), $faqData->getLanguage())) {
            $faqData = $faq->update($faqData);
        } else {
            $faqData = $faq->create($faqData);
        }

        if (!isset($categories)) {
            $categories = [];
        }

        $categoryRelation = new Relation($this->configuration, $category);
        $categoryRelation->deleteByFaq($faqData->getId(), $faqData->getLanguage());
        $categoryRelation->add($categories, $faqData->getId(), $faqData->getLanguage());

        // Insert the tags
        if ($tags !== '') {
            $tagging->create($faqData->getId(), explode(',', trim((string) $tags)));
        } else {
            $tagging->deleteByRecordId($faqData->getId());
        }

        // Update the SEO data
        $seoEntity = new SeoEntity();
        $seoEntity
            ->setType(SeoType::FAQ)
            ->setReferenceId($faqData->getId())
            ->setReferenceLanguage($faqData->getLanguage())
            ->setTitle($serpTitle)
            ->setDescription($serpDescription);

        if ($seo->get($seoEntity)->getId() === null) {
            $seoEntity
                ->setTitle($serpTitle)
                ->setDescription($serpDescription);
            $seo->create($seoEntity);
        } else {
            $seoEntity
                ->setTitle($serpTitle)
                ->setDescription($serpDescription);
            $seo->update($seoEntity);
        }

        // Add user permissions
        $faqPermission->delete(FaqPermission::USER, $faqData->getId());
        $faqPermission->add(FaqPermission::USER, $faqData->getId(), $permissions['restricted_user']);
        // Add group permission
        if ($this->configuration->get('security.permLevel') !== 'basic') {
            $faqPermission->delete(FaqPermission::GROUP, $faqData->getId());
            $faqPermission->add(FaqPermission::GROUP, $faqData->getId(), $permissions['restricted_groups']);
        }

        // If Elasticsearch is enabled, update an active or delete inactive FAQ document
        if ($this->configuration->get('search.enableElasticsearch')) {
            $esInstance = new Elasticsearch($this->configuration);
            if ('yes' === $active) {
                $esInstance->update(
                    [
                        'id' => $faqData->getId(),
                        'lang' => $faqData->getLanguage(),
                        'solution_id' => $faqData->getSolutionId(),
                        'question' => $faqData->getQuestion(),
                        'answer' => $faqData->getAnswer(),
                        'keywords' => $faqData->getKeywords(),
                        'category_id' => $categories[0]
                    ]
                );
            }
        }

        return $this->json(
            [
                'success' => Translation::get('ad_entry_savedsuc'),
                'data' => $faqData->getJson(),
            ],
            Response::HTTP_OK
        );
    }

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
    #[Route('admin/api/faqs/:categoryId/:language')]
    public function listByCategory(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
        $language = Filter::filterVar($request->get('language'), FILTER_SANITIZE_SPECIAL_CHARS);

        $onlyInactive = Filter::filterVar($request->query->get('only-inactive'), FILTER_VALIDATE_BOOLEAN, false);
        $onlyNew = Filter::filterVar($request->query->get('only-new'), FILTER_VALIDATE_BOOLEAN, false);

        $faq = new \phpMyFAQ\Administration\Faq($this->configuration);
        $faq->setLanguage($language);

        return $this->json(
            [
                'faqs' => $faq->getAllFaqsByCategory($categoryId, $onlyInactive, $onlyNew),
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
            $faq = new \phpMyFAQ\Administration\Faq($this->configuration);
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

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance()->verifyToken('faq-overview', $data->csrf)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        if (!($faqIds === false || $faqIds === [] || $faqIds === null)) {
            $faq = new \phpMyFAQ\Administration\Faq(Configuration::getConfigurationInstance());
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

        $faqImport = new Import(Configuration::getConfigurationInstance());

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
