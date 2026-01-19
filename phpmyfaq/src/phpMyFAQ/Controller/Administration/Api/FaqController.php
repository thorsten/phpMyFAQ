<?php

/**
 * The Admin FAQ Controller
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use DateTime;
use Exception;
use phpMyFAQ\Administration\Faq as FaqAdministration;
use phpMyFAQ\Administration\Revision;
use phpMyFAQ\Attachment\AttachmentException;
use phpMyFAQ\Attachment\Filesystem\File\FileException;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Import;
use phpMyFAQ\Faq\Permission as FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Language;
use phpMyFAQ\Link;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

final class FaqController extends AbstractAdministrationApiController
{
    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/create', name: 'admin.api.faq.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $tagging = $this->container->get(id: 'phpmyfaq.tags');
        $notification = $this->container->get(id: 'phpmyfaq.notification');
        $changelog = $this->container->get(id: 'phpmyfaq.admin.changelog');
        $visits = $this->container->get(id: 'phpmyfaq.visits');
        $seo = $this->container->get(id: 'phpmyfaq.seo');
        $categoryPermission = new CategoryPermission($this->configuration);
        $faqPermission = new FaqPermission($this->configuration);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = json_decode($request->getContent())->data;

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: $data->{'pmf-csrf-token'},
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);

        $categories = is_array($data->{'categories[]'})
            ? Filter::filterArray($data->{'categories[]'})
            : [Filter::filterVar($data->{'categories[]'}, FILTER_VALIDATE_INT)];

        $language = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $tags = Filter::filterVar($data->tags, FILTER_SANITIZE_SPECIAL_CHARS);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL, default: '');
        $comment = Filter::filterVar($data->comment ?? 'n', FILTER_SANITIZE_SPECIAL_CHARS);
        $changed = Filter::filterVar($data->changed, FILTER_SANITIZE_SPECIAL_CHARS);
        $notes = Filter::filterVar($data->notes, FILTER_SANITIZE_SPECIAL_CHARS);

        $serpTitle = Filter::filterVar($data->serpTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $serpDescription = Filter::filterVar($data->serpDescription, FILTER_SANITIZE_SPECIAL_CHARS);

        // Permissions
        $permissions = $faqPermission->createPermissionArray();

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_ADD->value);

        if ($question === '' && $content === '') {
            return $this->json(['error' => Translation::get(key: 'msgNoQuestionAndAnswer')], Response::HTTP_CONFLICT);
        }

        $faqData = new FaqEntity();
        $faqData
            ->setLanguage($language)
            ->setActive($active === 'yes')
            ->setSticky($sticky !== 'no' ? $sticky : false)
            ->setQuestion(Filter::removeAttributes(html_entity_decode(
                (string) $question,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAnswer(Filter::removeAttributes(html_entity_decode(
                (string) $content,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setComment($comment === 'y')
            ->setCreatedDate(new DateTime())
            ->setNotes(Filter::removeAttributes($notes));

        // Add a new record and get that ID
        $faqData = $faq->create($faqData);

        if ($faqData->getId()) {
            // Create ChangeLog entry
            $changelog->add(
                $faqData->getId(),
                $this->currentUser->getUserId(),
                nl2br((string) $changed),
                $faqData->getLanguage(),
            );

            // Create the visit entry
            $visits->logViews($faqData->getId());

            $categoryRelation = new Relation($this->configuration, $category);
            $categoryRelation->add($categories, $faqData->getId(), $faqData->getLanguage());

            // Insert the tags
            if ($tags !== '') {
                $tagging->create($faqData->getId(), explode(separator: ',', string: trim((string) $tags)));
            }

            // Add user permissions
            $faqPermission->add(FaqPermission::USER, $faqData->getId(), $permissions['restricted_user']);
            $categoryPermission->add(CategoryPermission::USER, $categories, $permissions['restricted_user']);
            // Add group permission
            if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
                $faqPermission->add(FaqPermission::GROUP, $faqData->getId(), $permissions['restricted_groups']);
                $categoryPermission->add(CategoryPermission::GROUP, $categories, $permissions['restricted_groups']);
            }

            // Add the SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setSeoType(SeoType::FAQ)
                ->setReferenceId($faqData->getId())
                ->setReferenceLanguage($faqData->getLanguage())
                ->setTitle($serpTitle)
                ->setDescription($serpDescription);
            $seo->create($seoEntity);

            // Open question answered
            $questionObject = $this->container->get(id: 'phpmyfaq.question');
            $openQuestionId = Filter::filterVar($data->openQuestionId, FILTER_VALIDATE_INT);
            if (0 !== $openQuestionId) {
                if ($this->configuration->get(item: 'records.enableDeleteQuestion')) {
                    // deletes question
                    $questionObject->delete($openQuestionId);
                }

                if (!$this->configuration->get(item: 'records.enableDeleteQuestion')) {
                    // adds this faq record id to the related open question
                    $questionObject->updateQuestionAnswer($openQuestionId, $faqData->getId(), $categories[0]);
                }

                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $categories[0],
                    $faqData->getId(),
                    $faqData->getLanguage(),
                    TitleSlugifier::slug($faqData->getQuestion()),
                );
                $oLink = new Link($url, $this->configuration);

                // notify the user who added the question
                try {
                    $notifyEmail = Filter::filterVar($data->notifyEmail, FILTER_SANITIZE_EMAIL);
                    $notifyUser = Filter::filterVar($data->notifyUser, FILTER_SANITIZE_SPECIAL_CHARS);
                    $notification->sendOpenQuestionAnswered($notifyEmail, $notifyUser, $oLink->toString());
                } catch (Exception|TransportExceptionInterface $e) {
                    $this->configuration
                        ->getLogger()
                        ->error('Send open question answered notification failed: ' . $e->getMessage());
                }
            }

            // Let the admin and the category owners be informed by email of this new entry
            try {
                $categoryHelper = new CategoryHelper();
                $categoryHelper->setCategory($category)->setConfiguration($this->configuration);
                $moderators = $categoryHelper->getModerators($categories);
                $notification->sendNewFaqAdded($moderators, $faqData);
            } catch (Exception|TransportExceptionInterface $e) {
                $this->configuration->getLogger()->error('Send moderator notification failed: ' . $e->getMessage());
            }

            // If Elasticsearch is enabled, index the new FAQ document
            if ($this->configuration->get(item: 'search.enableElasticsearch')) {
                $elasticsearch = new Elasticsearch($this->configuration);
                $elasticsearch->index([
                    'id' => $faqData->getId(),
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0],
                ]);
            }

            // If OpenSearch is enabled, index the new FAQ document
            if ($this->configuration->get(item: 'search.enableOpenSearch')) {
                $openSearch = new OpenSearch($this->configuration);
                $openSearch->index([
                    'id' => $faqData->getId(),
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0],
                ]);
            }

            return $this->json([
                'success' => Translation::get(key: 'ad_entry_savedsuc'),
                'data' => $faqData->getJson(),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(key: 'ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/update', name: 'admin.api.faq.update', methods: ['POST'])]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $tagging = $this->container->get(id: 'phpmyfaq.tags');
        $logging = $this->container->get(id: 'phpmyfaq.admin.admin-log');
        $changelog = $this->container->get(id: 'phpmyfaq.admin.changelog');
        $visits = $this->container->get(id: 'phpmyfaq.visits');
        $seo = $this->container->get(id: 'phpmyfaq.seo');
        $faqPermission = new FaqPermission($this->configuration);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = json_decode($request->getContent())->data;

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: $data->{'pmf-csrf-token'},
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $solutionId = Filter::filterVar($data->solutionId, FILTER_VALIDATE_INT);
        $revisionId = Filter::filterVar($data->revisionId, FILTER_VALIDATE_INT);
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $categories = is_array($data->{'categories[]'})
            ? Filter::filterArray($data->{'categories[]'})
            : [Filter::filterVar($data->{'categories[]'}, FILTER_VALIDATE_INT)];

        $faqLang = Filter::filterVar($data->lang, FILTER_SANITIZE_SPECIAL_CHARS);
        $tags = Filter::filterVar($data->tags, FILTER_SANITIZE_SPECIAL_CHARS);
        $active = Filter::filterVar($data->active, FILTER_SANITIZE_SPECIAL_CHARS);
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);
        $content = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_VALIDATE_EMAIL, default: '');
        $comment = Filter::filterVar($data->comment ?? 'n', FILTER_SANITIZE_SPECIAL_CHARS);
        $changed = Filter::filterVar($data->changed, FILTER_SANITIZE_SPECIAL_CHARS);
        $date = Filter::filterVar($data->date, FILTER_SANITIZE_SPECIAL_CHARS);
        $notes = Filter::filterVar($data->notes, FILTER_SANITIZE_SPECIAL_CHARS);
        $revision = Filter::filterVar($data->revision ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS);
        $recordDateHandling = Filter::filterVar($data->recordDateHandling, FILTER_SANITIZE_SPECIAL_CHARS);

        $serpTitle = Filter::filterVar($data->serpTitle, FILTER_SANITIZE_SPECIAL_CHARS);
        $serpDescription = Filter::filterVar($data->serpDescription, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($question === '' && $content === '') {
            return $this->json(['error' => Translation::get(key: 'msgNoQuestionAndAnswer')], Response::HTTP_CONFLICT);
        }

        // Permissions
        $permissions = $faqPermission->createPermissionArray();

        $logging->log($this->currentUser, AdminLogType::FAQ_EDIT->value . ':' . $faqId);
        if ($active === 'yes') {
            $logging->log($this->currentUser, AdminLogType::FAQ_PUBLISH->value . ':' . $faqId);
        }

        if ('yes' === $revision && $this->configuration->get(item: 'records.enableAutoRevisions')) {
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
            ->setSticky($sticky !== 'no')
            ->setQuestion(Filter::removeAttributes(html_entity_decode(
                (string) $question,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAnswer(Filter::removeAttributes(html_entity_decode(
                (string) $content,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setComment($comment === 'y')
            ->setNotes(Filter::removeAttributes($notes));

        switch ($recordDateHandling) {
            case 'updateDate':
                $faqData->setUpdatedDate(new DateTime());
                break;
            case 'manualDate':
                $faqData->setUpdatedDate(new DateTime($date));
                break;
            case 'keepDate':
                break;
        }

        // Create ChangeLog entry
        $changelog->add(
            $faqData->getId(),
            $this->currentUser->getUserId(),
            (string) $changed,
            $faqData->getLanguage(),
            $revisionId,
        );

        // Create the visit entry
        $visits->logViews($faqData->getId());

        // save or update the FAQ record
        if ($faq->hasTranslation($faqData->getId(), $faqData->getLanguage())) {
            $faqData = $faq->update($faqData);
        }

        if (!$faq->hasTranslation($faqData->getId(), $faqData->getLanguage())) {
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
            $tagging->create($faqData->getId(), explode(separator: ',', string: trim((string) $tags)));
        }

        if ($tags === '') {
            $tagging->deleteByRecordId($faqData->getId());
        }

        // Update the SEO data
        $seoEntity = new SeoEntity();
        $seoEntity
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId($faqData->getId())
            ->setReferenceLanguage($faqData->getLanguage())
            ->setTitle($serpTitle)
            ->setDescription($serpDescription);

        if ($seo->get($seoEntity)->getId() === null) {
            $seoEntity->setTitle($serpTitle)->setDescription($serpDescription);
            $seo->create($seoEntity);
        }

        if ($seo->get($seoEntity)->getId() !== null) {
            $seoEntity->setTitle($serpTitle)->setDescription($serpDescription);
            $seo->update($seoEntity);
        }

        // Add user permissions
        $faqPermission->delete(FaqPermission::USER, $faqData->getId());
        $faqPermission->add(FaqPermission::USER, $faqData->getId(), $permissions['restricted_user']);
        // Add group permission
        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $faqPermission->delete(FaqPermission::GROUP, $faqData->getId());
            $faqPermission->add(FaqPermission::GROUP, $faqData->getId(), $permissions['restricted_groups']);
        }

        // If Elasticsearch is enabled, update an active or delete inactive FAQ document
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            $elasticsearch = new Elasticsearch($this->configuration);
            if ('yes' === $active) {
                $elasticsearch->update([
                    'id' => $faqData->getId(),
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0],
                ]);
            }
        }

        // If OpenSearch is enabled, update an active or delete an inactive FAQ document
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            $openSearch = new OpenSearch($this->configuration);
            if ('yes' === $active) {
                $openSearch->update([
                    'id' => $faqData->getId(),
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0],
                ]);
            }
        }

        return $this->json([
            'success' => Translation::get(key: 'ad_entry_savedsuc'),
            'data' => $faqData->getJson(),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/permissions', name: 'admin.api.faq.permissions', methods: ['GET'])]
    public function listPermissions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $faqId = (int) Filter::filterVar($request->attributes->get(key: 'faqId'), FILTER_VALIDATE_INT);

        $faqPermission = new FaqPermission($this->configuration);

        return $this->json([
            'user' => $faqPermission->get(FaqPermission::USER, $faqId),
            'group' => $faqPermission->get(FaqPermission::GROUP, $faqId),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faqs/{categoryId}/{language}', name: 'admin.api.faqs', methods: ['GET'])]
    public function listByCategory(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $categoryId = (int) Filter::filterVar($request->attributes->get(key: 'categoryId'), FILTER_VALIDATE_INT);
        $language = Filter::filterVar($request->attributes->get(key: 'language'), FILTER_SANITIZE_SPECIAL_CHARS);

        $onlyInactive = Filter::filterVar(
            $request->query->get(key: 'only-inactive'),
            FILTER_VALIDATE_BOOLEAN,
            default: false,
        );
        $onlyNew = Filter::filterVar($request->query->get(key: 'only-new'), FILTER_VALIDATE_BOOLEAN, default: false);

        $faq = new FaqAdministration($this->configuration);
        $faq->setLanguage($language);

        return $this->json([
            'faqs' => $faq->getAllFaqsByCategory($categoryId, $onlyInactive, $onlyNew),
            'isAllowedToTranslate' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_TRANSLATE->value,
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/activate', name: 'admin.api.faq.activate', methods: ['POST'])]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_APPROVE);

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance($this->session)->verifyToken(page: 'pmf-csrf-token', requestToken: $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($faqIds, [false, [], null], true)) {
            $faq = new FaqAdministration($this->configuration);
            $success = false;

            foreach ($faqIds as $faqId) {
                if (!Language::isASupportedLanguage($faqLanguage)) {
                    continue;
                }

                $success = $faq->updateRecordFlag((int) $faqId, $faqLanguage, $checked ?? false, type: 'active');
            }

            if ($success) {
                $this->adminLog->log($this->currentUser, AdminLogType::FAQ_EDIT->value);
                return $this->json(['success' => Translation::get(key: 'ad_entry_savedsuc')], Response::HTTP_OK);
            }

            return $this->json(['error' => Translation::get(key: 'ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['error' => 'No FAQ IDs provided.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/sticky', name: 'admin.api.faq.sticky', methods: ['POST'])]
    public function sticky(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = json_decode($request->getContent());

        $faqIds = Filter::filterArray($data->faqIds);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);
        $checked = Filter::filterVar($data->checked, FILTER_VALIDATE_BOOLEAN);

        if (!Token::getInstance($this->session)->verifyToken(page: 'pmf-csrf-token', requestToken: $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($faqIds, [false, [], null], true)) {
            $faq = new FaqAdministration($this->configuration);
            $success = false;

            foreach ($faqIds as $faqId) {
                if (!Language::isASupportedLanguage($faqLanguage)) {
                    continue;
                }

                $success = $faq->updateRecordFlag((int) $faqId, $faqLanguage, $checked ?? false, type: 'sticky');
            }

            if ($success) {
                return $this->json(['success' => Translation::get(key: 'ad_entry_savedsuc')], Response::HTTP_OK);
            }

            return $this->json(['error' => Translation::get(key: 'ad_entry_savedfail')], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['error' => 'No FAQ IDs provided.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/delete', name: 'admin.api.faq.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        $faq = new Faq($this->configuration);

        $data = json_decode($request->getContent());

        $faqId = Filter::filterVar($data->faqId, FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($data->faqLanguage, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!Token::getInstance($this->session)->verifyToken(page: 'pmf-csrf-token', requestToken: $data->csrf)) {
            return $this->json([
                'error' => 'CSRF Token - ' . Translation::get(key: 'msgNoPermission'),
            ], Response::HTTP_UNAUTHORIZED);
        }

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_DELETE->value . ':' . $faqId);

        try {
            $faq->delete($faqId, $faqLanguage);
        } catch (FileException|AttachmentException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        return $this->json(['success' => Translation::get(key: 'ad_entry_delsuc')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/search', name: 'admin.api.faq.search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(page: 'pmf-csrf-token', requestToken: $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faqPermission = new FaqPermission($this->configuration);
        $faqSearch = new Search($this->configuration);
        $faqSearch->setCategory(new Category($this->configuration));

        $searchResultSet = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);
        $searchString = Filter::filterVar($data->search, FILTER_SANITIZE_SPECIAL_CHARS);

        if (!is_null($searchString)) {
            $searchResult = $faqSearch->search($searchString, allLanguages: false);

            $searchResultSet->reviewResultSet($searchResult);

            $searchHelper = new SearchHelper($this->configuration);
            $searchHelper->setSearchTerm($searchString);

            return $this->json(['success' =>
                $searchHelper->renderAdminSuggestionResult($searchResultSet)], Response::HTTP_OK);
        }

        return $this->json(['error' => 'No search string provided.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faqs/sticky/order', name: 'admin.api.faqs.sticky.order', methods: ['POST'])]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken(page: 'order-stickyfaqs', requestToken: $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faq = $this->container->get(id: 'phpmyfaq.admin.faq');
        $faq->setStickyFaqOrder($data->faqIds);

        return $this->json(['success' => Translation::get(key: 'ad_categ_save_order')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'admin/api/faq/import', name: 'admin.api.faq.import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        $file = $request->files->get(key: 'file');
        if (!isset($file)) {
            return $this->json(['error' => 'Bad request: There is no file submitted.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'importfaqs',
            requestToken: $request->attributes->get(key: 'csrf'),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faqImport = new Import($this->configuration);

        $errors = [];

        if (0 === $file->getError() && $faqImport->isCSVFile($file)) {
            $handle = fopen(filename: $file->getRealPath(), mode: 'r');
            $csvData = $faqImport->parseCSV($handle);

            if (!$faqImport->validateCSV($csvData)) {
                $result = [
                    'storedAll' => false,
                    'error' => Translation::get(key: 'msgCSVFileNotValidated'),
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
                    'success' => Translation::get(key: 'msgImportSuccessful'),
                ];
                return $this->json($result, Response::HTTP_OK);
            }

            $result = [
                'storedAll' => false,
                'messages' => $errors,
            ];

            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $result = [
            'storedAll' => false,
            'error' => 'Bad request: The file is not a CSV file.',
        ];

        return $this->json($result, Response::HTTP_BAD_REQUEST);
    }
}
