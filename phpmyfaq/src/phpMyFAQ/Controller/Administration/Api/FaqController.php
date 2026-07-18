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
use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
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
use phpMyFAQ\Notification;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Question;
use phpMyFAQ\Search;
use phpMyFAQ\Search\SearchResultSet;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;
use stdClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;

/* @mago-expect lint:cyclomatic-complexity - the create/update endpoints validate every field inline; split planned with the admin API rework */
final class FaqController extends AbstractAdministrationApiController
{
    /* @mago-expect lint:excessive-parameter-list - the endpoint dependencies are injected explicitly; a service split is planned with the admin API rework */
    public function __construct(
        private readonly Faq $faq,
        private readonly FaqAdministration $adminFaq,
        private readonly Tags $tags,
        private readonly Notification $notification,
        private readonly Changelog $changelog,
        private readonly Visits $visits,
        private readonly Seo $seo,
        private readonly Question $question,
        private readonly AdminLog $logging,
        private readonly WebPushService $webPushService,
    ) {
        parent::__construct();
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws Exception
     */
    /* @mago-expect lint:halstead - validates and persists the full FAQ payload in one endpoint */
    #[Route(path: 'faq/create', name: 'admin.api.faq.create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->tags->setBypassPermissionCheck();
        $categoryPermission = new CategoryPermission($this->configuration);
        $faqPermission = new FaqPermission($this->configuration);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = $this->getJsonObject($request)->data ?? null;
        if (!$data instanceof stdClass) {
            return $this->json(['error' => 'The request body must contain a data object.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->{'pmf-csrf-token'} ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $question = Filter::filterVar($data->question ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $rawCategories = $data->{'categories[]'} ?? null;
        $categories = is_array($rawCategories)
            ? array_map(static fn(mixed $categoryId): int => (int) $categoryId, $rawCategories)
            : [(int) Filter::filterVar($rawCategories, FILTER_VALIDATE_INT)];

        $language = Filter::filterVar($data->lang ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $tags = Filter::filterVar($data->tags ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $active = Filter::filterVar($data->active ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
        $content = Filter::filterVar($data->answer ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $keywords = Filter::filterVar($data->keywords ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $author = Filter::filterVar($data->author ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $email = (string) Filter::filterEmail($data->email ?? '', default: '');
        $comment = Filter::filterVar($data->comment ?? 'n', FILTER_SANITIZE_SPECIAL_CHARS, 'n');
        $changed = Filter::filterVar($data->changed ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $notes = Filter::filterVar($data->notes ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $serpTitle = Filter::filterVar($data->serpTitle ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $serpDescription = Filter::filterVar($data->serpDescription ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

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
            ->setSticky($sticky !== 'no')
            ->setQuestion(Filter::removeAttributes(html_entity_decode(
                $question,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAnswer(Filter::removeAttributes(html_entity_decode(
                $content,
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
        $faqData = $this->faq->create($faqData);

        $faqId = $faqData->getId();
        if ($faqId) {
            // Create ChangeLog entry
            $this->changelog->add($faqId, $this->currentUser->getUserId(), nl2br($changed), $faqData->getLanguage());

            // Create the visit entry
            $this->visits->logViews($faqId);

            $categoryRelation = new Relation($this->configuration, $category);
            $categoryRelation->add($categories, $faqId, $faqData->getLanguage());

            // Insert the tags
            if ($tags !== '') {
                $this->tags->create($faqId, explode(separator: ',', string: trim($tags)));
            }

            // Add user permissions
            $faqPermission->add(FaqPermission::USER, $faqId, $permissions['restricted_user']);
            $categoryPermission->add(CategoryPermission::USER, $categories, $permissions['restricted_user']);
            // Add group permission
            if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
                $faqPermission->add(FaqPermission::GROUP, $faqId, $permissions['restricted_groups']);
                $categoryPermission->add(CategoryPermission::GROUP, $categories, $permissions['restricted_groups']);
            }

            // Add the SEO data
            $seoEntity = new SeoEntity();
            $seoEntity
                ->setSeoType(SeoType::FAQ)
                ->setReferenceId($faqId)
                ->setReferenceLanguage($faqData->getLanguage())
                ->setTitle($serpTitle)
                ->setDescription($serpDescription);
            $this->seo->create($seoEntity);

            // Open question answered
            $openQuestionId = (int) Filter::filterVar($data->openQuestionId ?? null, FILTER_VALIDATE_INT);
            if (0 !== $openQuestionId) {
                if ($this->configuration->get(item: 'records.enableDeleteQuestion')) {
                    // deletes question
                    $this->question->delete($openQuestionId);
                }

                if (!$this->configuration->get(item: 'records.enableDeleteQuestion')) {
                    // adds this faq record id to the related open question
                    $this->question->updateQuestionAnswer($openQuestionId, $faqId, $categories[0] ?? 0);
                }

                $url = sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $categories[0] ?? 0,
                    $faqId,
                    $faqData->getLanguage(),
                    TitleSlugifier::slug($faqData->getQuestion()),
                );
                $oLink = new Link($url, $this->configuration);

                // notify the user who added the question
                try {
                    $notifyEmail = (string) Filter::filterVar($data->notifyEmail ?? '', FILTER_SANITIZE_EMAIL, '');
                    $notifyUser = Filter::filterVar($data->notifyUser ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
                    $this->notification->sendOpenQuestionAnswered($notifyEmail, $notifyUser, $oLink->toString());
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
                $this->notification->sendNewFaqAdded($moderators, $faqData);
            } catch (Exception|TransportExceptionInterface $e) {
                $this->configuration->getLogger()->error('Send moderator notification failed: ' . $e->getMessage());
            }

            // If Elasticsearch is enabled, index the new FAQ document
            if ($this->configuration->get(item: 'search.enableElasticsearch')) {
                $elasticsearch = new Elasticsearch($this->configuration);
                $elasticsearch->index([
                    'id' => $faqId,
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0] ?? 0,
                ]);
            }

            // If OpenSearch is enabled, index the new FAQ document
            if ($this->configuration->get(item: 'search.enableOpenSearch')) {
                $openSearch = new OpenSearch($this->configuration);
                $openSearch->index([
                    'id' => $faqId,
                    'lang' => $faqData->getLanguage(),
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0] ?? 0,
                ]);
            }

            // Send Web Push notification for new active FAQs.
            // This is done here (not in Notification::sendNewFaqAdded) to provide
            // the public FAQ URL, which is more useful for end-users.
            if ($faqData->isActive()) {
                try {
                    $faqUrl = sprintf(
                        '%scontent/%d/%d/%s/%s.html',
                        $this->configuration->getDefaultUrl(),
                        $categories[0] ?? 0,
                        $faqId,
                        $faqData->getLanguage(),
                        TitleSlugifier::slug($faqData->getQuestion()),
                    );
                    $this->webPushService->sendToAll(
                        Translation::getString('msgPushNewFaq'),
                        $faqData->getQuestion(),
                        $faqUrl,
                        'new-faq-' . $faqId,
                    );
                } catch (\Throwable $e) {
                    $this->configuration->getLogger()->error('Send web push notification failed: ' . $e->getMessage());
                }
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
    /* @mago-expect lint:halstead - validates and persists the full FAQ payload in one endpoint */
    #[Route(path: 'faq/update', name: 'admin.api.faq.update', methods: ['POST', 'PUT'])]
    public function update(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->tags->setBypassPermissionCheck();
        $faqPermission = new FaqPermission($this->configuration);

        $category = new Category($this->configuration, [], withPermission: false);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);

        $data = $this->getJsonObject($request)->data ?? null;
        if (!$data instanceof stdClass) {
            return $this->json(['error' => 'The request body must contain a data object.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->{'pmf-csrf-token'} ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        // Collect FAQ data
        $faqId = (int) Filter::filterVar($data->faqId ?? null, FILTER_VALIDATE_INT);
        $solutionId = (int) Filter::filterVar($data->solutionId ?? null, FILTER_VALIDATE_INT);
        $revisionId = (int) Filter::filterVar($data->revisionId ?? null, FILTER_VALIDATE_INT);
        $question = Filter::filterVar($data->question ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $rawCategories = $data->{'categories[]'} ?? null;
        $categories = is_array($rawCategories)
            ? array_map(static fn(mixed $categoryId): int => (int) $categoryId, $rawCategories)
            : [(int) Filter::filterVar($rawCategories, FILTER_VALIDATE_INT)];

        $faqLang = Filter::filterVar($data->lang ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $tags = Filter::filterVar($data->tags ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $active = Filter::filterVar($data->active ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
        $sticky = Filter::filterVar($data->sticky ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
        $content = Filter::filterVar($data->answer ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $keywords = Filter::filterVar($data->keywords ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $author = Filter::filterVar($data->author ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $email = (string) Filter::filterEmail($data->email ?? '', default: '');
        $comment = Filter::filterVar($data->comment ?? 'n', FILTER_SANITIZE_SPECIAL_CHARS, 'n');
        $changed = Filter::filterVar($data->changed ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $date = Filter::filterVar($data->date ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $notes = Filter::filterVar($data->notes ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $revision = Filter::filterVar($data->revision ?? 'no', FILTER_SANITIZE_SPECIAL_CHARS, 'no');
        $recordDateHandling = Filter::filterVar($data->recordDateHandling ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        $serpTitle = Filter::filterVar($data->serpTitle ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $serpDescription = Filter::filterVar($data->serpDescription ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        if ($question === '' && $content === '') {
            return $this->json(['error' => Translation::get(key: 'msgNoQuestionAndAnswer')], Response::HTTP_CONFLICT);
        }

        // Permissions
        $permissions = $faqPermission->createPermissionArray();

        $this->logging->log($this->currentUser, AdminLogType::FAQ_EDIT->value . ':' . $faqId);
        if ($active === 'yes') {
            $this->logging->log($this->currentUser, AdminLogType::FAQ_PUBLISH->value . ':' . $faqId);
        }

        if ('yes' === $revision && true === $this->configuration->get(item: 'records.enableAutoRevisions')) {
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
                $question,
                ENT_QUOTES | ENT_HTML5,
                encoding: 'UTF-8',
            )))
            ->setAnswer(Filter::removeAttributes(html_entity_decode(
                $content,
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
        $this->changelog->add($faqId, $this->currentUser->getUserId(), $changed, $faqLang, $revisionId);

        // Create the visit entry
        $this->visits->logViews($faqId);

        // save or update the FAQ record
        if ($this->faq->hasTranslation($faqId, $faqLang)) {
            $faqData = $this->faq->update($faqData);
        }

        if (!$this->faq->hasTranslation($faqId, $faqLang)) {
            $faqData = $this->faq->create($faqData);
        }

        $faqId = $faqData->getId() ?? $faqId;

        $categoryRelation = new Relation($this->configuration, $category);
        $categoryRelation->deleteByFaq($faqId, $faqLang);
        $categoryRelation->add($categories, $faqId, $faqLang);

        // Insert the tags
        if ($tags !== '') {
            $this->tags->create($faqId, explode(separator: ',', string: trim($tags)));
        }

        if ($tags === '') {
            $this->tags->deleteByRecordId($faqId);
        }

        // Update the SEO data
        $seoEntity = new SeoEntity();
        $seoEntity
            ->setSeoType(SeoType::FAQ)
            ->setReferenceId($faqId)
            ->setReferenceLanguage($faqLang)
            ->setTitle($serpTitle)
            ->setDescription($serpDescription);

        if ($this->seo->get($seoEntity)->getId() === null) {
            $seoEntity->setTitle($serpTitle)->setDescription($serpDescription);
            $this->seo->create($seoEntity);
        }

        if ($this->seo->get($seoEntity)->getId() !== null) {
            $seoEntity->setTitle($serpTitle)->setDescription($serpDescription);
            $this->seo->update($seoEntity);
        }

        // Add user permissions
        $faqPermission->delete(FaqPermission::USER, $faqId);
        $faqPermission->add(FaqPermission::USER, $faqId, $permissions['restricted_user']);
        // Add group permission
        if ($this->configuration->get(item: 'security.permLevel') !== 'basic') {
            $faqPermission->delete(FaqPermission::GROUP, $faqId);
            $faqPermission->add(FaqPermission::GROUP, $faqId, $permissions['restricted_groups']);
        }

        // If Elasticsearch is enabled, update an active or delete inactive FAQ document
        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            $elasticsearch = new Elasticsearch($this->configuration);
            if ('yes' === $active) {
                $elasticsearch->update([
                    'id' => $faqId,
                    'lang' => $faqLang,
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0] ?? 0,
                ]);
            }
        }

        // If OpenSearch is enabled, update an active or delete an inactive FAQ document
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            $openSearch = new OpenSearch($this->configuration);
            if ('yes' === $active) {
                $openSearch->update([
                    'id' => $faqId,
                    'lang' => $faqLang,
                    'solution_id' => $faqData->getSolutionId(),
                    'question' => $faqData->getQuestion(),
                    'answer' => $faqData->getAnswer(),
                    'keywords' => $faqData->getKeywords(),
                    'category_id' => $categories[0] ?? 0,
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
    #[Route(path: 'faq/permissions', name: 'admin.api.faq.permissions', methods: ['GET'])]
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
    #[Route(path: 'faqs/{categoryId}/{language}', name: 'admin.api.faqs', methods: ['GET'])]
    public function listByCategory(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $categoryId = (int) Filter::filterVar($request->attributes->get(key: 'categoryId'), FILTER_VALIDATE_INT);
        $language = Filter::filterVar($request->attributes->get(key: 'language'), FILTER_SANITIZE_SPECIAL_CHARS, '');

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
            'isAllowedToTranslate' => $this->currentUser?->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_TRANSLATE->value,
            ),
        ], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'faq/activate', name: 'admin.api.faq.activate', methods: ['POST'])]
    public function activate(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_APPROVE);

        $data = $this->getJsonObject($request);

        $rawFaqIds = $data->faqIds ?? null;
        $faqIds = is_array($rawFaqIds) ? array_map(static fn(mixed $faqId): int => (int) $faqId, $rawFaqIds) : [];
        $faqLanguage = Filter::filterVar($data->faqLanguage ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $checked = Filter::filterVar($data->checked ?? false, FILTER_VALIDATE_BOOLEAN, false);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->csrf ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($faqIds !== []) {
            $faq = new FaqAdministration($this->configuration);
            $success = false;

            foreach ($faqIds as $faqId) {
                if (!Language::isASupportedLanguage($faqLanguage)) {
                    continue;
                }

                $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked, type: 'active');
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
    #[Route(path: 'faq/sticky', name: 'admin.api.faq.sticky', methods: ['POST'])]
    public function sticky(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = $this->getJsonObject($request);

        $rawFaqIds = $data->faqIds ?? null;
        $faqIds = is_array($rawFaqIds) ? array_map(static fn(mixed $faqId): int => (int) $faqId, $rawFaqIds) : [];
        $faqLanguage = Filter::filterVar($data->faqLanguage ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $checked = Filter::filterVar($data->checked ?? false, FILTER_VALIDATE_BOOLEAN, false);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->csrf ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($faqIds !== []) {
            $faq = new FaqAdministration($this->configuration);
            $success = false;

            foreach ($faqIds as $faqId) {
                if (!Language::isASupportedLanguage($faqLanguage)) {
                    continue;
                }

                $success = $faq->updateRecordFlag($faqId, $faqLanguage, $checked, type: 'sticky');
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
    #[Route(path: 'faq/delete', name: 'admin.api.faq.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        $faq = new Faq($this->configuration);

        $data = $this->getJsonObject($request);

        $faqId = (int) Filter::filterVar($data->faqId ?? null, FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($data->faqLanguage ?? '', FILTER_SANITIZE_SPECIAL_CHARS, '');

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->csrf ?? ''),
        )) {
            return $this->json([
                'error' => 'CSRF Token - ' . Translation::getString(key: 'msgNoPermission'),
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
    #[Route(path: 'faq/search', name: 'admin.api.faq.search', methods: ['POST'])]
    public function search(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $data = $this->getJsonObject($request);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'pmf-csrf-token',
            requestToken: (string) ($data->csrf ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faqPermission = new FaqPermission($this->configuration);
        $faqSearch = new Search($this->configuration);
        $faqSearch->setCategory(new Category($this->configuration));

        $searchResultSet = new SearchResultSet($this->currentUser, $faqPermission, $this->configuration);
        $searchString = Filter::filterVar($data->search ?? null, FILTER_SANITIZE_SPECIAL_CHARS);

        if (is_string($searchString)) {
            $searchResult = $faqSearch->search($searchString, allLanguages: false);

            $searchResultSet->reviewResultSet($searchResult);

            $searchHelper = new SearchHelper($this->configuration);
            $searchHelper->setSearchTerm($searchString);

            return $this->json([
                'success' => $searchHelper->renderAdminSuggestionResult($searchResultSet),
            ], Response::HTTP_OK);
        }

        return $this->json(['error' => 'No search string provided.'], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'faqs/sticky/order', name: 'admin.api.faqs.sticky.order', methods: ['POST'])]
    public function saveOrderOfStickyFaqs(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $data = $this->getJsonObject($request);

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'order-stickyfaqs',
            requestToken: (string) ($data->csrf ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faqIds = $data->faqIds ?? null;
        if (!is_array($faqIds)) {
            return $this->json(['error' => 'No FAQ IDs provided.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->adminFaq->setStickyFaqOrder($faqIds, $currentUser, $currentGroups)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json(['success' => Translation::get(key: 'ad_categ_save_order')], Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'faq/import', name: 'admin.api.faq.import', methods: ['POST'])]
    public function import(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        $file = $request->files->get(key: 'file');
        if (!$file instanceof UploadedFile) {
            return $this->json(['error' => 'Bad request: There is no file submitted.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            page: 'importfaqs',
            requestToken: (string) $request->request->get(key: 'csrf'),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $faqImport = new Import($this->configuration);

        $errors = [];

        if (0 === $file->getError() && $faqImport->isCSVFile($file)) {
            $handle = fopen(filename: (string) $file->getRealPath(), mode: 'r');
            if ($handle === false) {
                return $this->json(['error' => 'The uploaded file could not be read.'], Response::HTTP_BAD_REQUEST);
            }

            $csvData = $faqImport->parseCSV($handle);

            if (!$faqImport->validateCSV($csvData)) {
                $result = [
                    'storedAll' => false,
                    'error' => Translation::get(key: 'msgCSVFileNotValidated'),
                ];
                return $this->json($result, Response::HTTP_BAD_REQUEST);
            }

            foreach ($csvData as $index => $record) {
                try {
                    if (!$faqImport->import($record)) {
                        $errors[] = sprintf('Row %d: import failed.', $index + 1);
                    }
                } catch (\Throwable $throwable) {
                    $errors[] = sprintf('Row %d: %s', $index + 1, $throwable->getMessage());
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
