<?php

/**
 * The Administration FAQs Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Revision;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\FormatBytesTwigExtension;
use phpMyFAQ\Twig\Extensions\IsoDateTwigExtension;
use phpMyFAQ\Twig\Extensions\UserNameTwigExtension;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;
use Twig\Extension\AttributeExtension;

final class FaqController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/faqs', name: 'admin.faqs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);
        $this->userHasPermission(PermissionType::FAQ_APPROVE);
        $this->userHasPermission(PermissionType::FAQ_EDIT);
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryRelation = new Relation($this->configuration, $category);
        $categoryRelation->setGroups($currentAdminGroups);

        $comments = $this->container->get(id: 'phpmyfaq.comments');
        $sessions = $this->session;

        return $this->render('@admin/content/faq.overview.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'csrfTokenSearch' => Token::getInstance($sessions)->getTokenInput('pmf-csrf-token'),
            'csrfTokenOverview' => Token::getInstance($sessions)->getTokenString('pmf-csrf-token'),
            'categories' => $category->getCategoryTree(),
            'numberOfRecords' => $categoryRelation->getNumberOfFaqsPerCategory(),
            'numberOfComments' => $comments->getNumberOfCommentsByCategory(),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route(path: '/faq/add', name: 'admin.faq.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_ADD->value);
        $categories = [];

        $faqData = [
            'id' => 0,
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'revision_id' => 0,
            'author' => $this->currentUser->getUserData('display_name'),
            'email' => $this->currentUser->getUserData('email'),
            'comment' => $this->configuration->get(item: 'records.defaultAllowComments') ? 'checked' : null,
        ];

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'msgAddFAQ'),
            'editExistingFaq' => false,
            'faqRevisionId' => 0,
            'faqData' => $faqData,
            'openQuestionId' => 0,
            'notifyUser' => '',
            'notifyEmail' => '',
            'categoryOptions' => $categoryHelper->renderOptions($categories),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqData['lang'], false, [], 'lang'),
            'attachments' => [],
            'allGroups' => true,
            'restrictedGroups' => false,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => true,
            'restrictedUsers' => false,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => [],
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => null,
            'isInActive' => 'checked',
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    #[Route(path: '/faq/add/:categoryId/:categoryLanguage', name: 'admin.faq.add', methods: ['GET'])]
    public function addInCategory(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryId = (int) Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT);
        $categoryLanguage = Filter::filterVar(
            $request->attributes->get('categoryLanguage'),
            FILTER_SANITIZE_SPECIAL_CHARS,
        );

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_ADD->value);

        $faqData = [
            'id' => 0,
            'lang' => $categoryLanguage,
            'revision_id' => 0,
            'author' => $this->currentUser->getUserData('display_name'),
            'email' => $this->currentUser->getUserData('email'),
            'comment' => $this->configuration->get(item: 'records.defaultAllowComments') ? 'checked' : null,
        ];

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'msgAddFAQ'),
            'editExistingFaq' => false,
            'faqRevisionId' => 0,
            'faqData' => $faqData,
            'openQuestionId' => 0,
            'notifyUser' => '',
            'notifyEmail' => '',
            'categoryOptions' => $categoryHelper->renderOptions($categoryId),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqData['lang'], false, [], 'lang'),
            'attachments' => [],
            'allGroups' => true,
            'restrictedGroups' => false,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => true,
            'restrictedUsers' => false,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => [],
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => null,
            'isInActive' => 'checked',
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route(path: '/faq/edit/:faqId/:faqLanguage', name: 'admin.faq.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $categoryRelation = new Relation($this->configuration, $category);
        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $faqId = (int) Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);
        $selectedRevisionId = Filter::filterVar($request->attributes->get('selectedRevisionId'), FILTER_VALIDATE_INT);

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_EDIT->value . ':' . $faqId);

        $categories = $categoryRelation->getCategories($faqId, $faqLanguage);

        $faq->getFaq($faqId, null, true);
        $faqData = $faq->faqRecord;

        // Tags
        $faqData['tags'] = implode(', ', $this->container->get(id: 'phpmyfaq.tags')->getAllTagsById($faqId));

        // SERP
        $seoEntity = new SeoEntity();
        $seoEntity->setSeoType(SeoType::FAQ)->setReferenceId($faqId)->setReferenceLanguage($faqLanguage);
        $seoData = $this->container->get(id: 'phpmyfaq.seo')->get($seoEntity);
        $faqData['serp-title'] = $seoData->getTitle();
        $faqData['serp-description'] = $seoData->getDescription();

        $attachmentList = AttachmentFactory::fetchByRecordId($this->configuration, $faqId);

        $faqRevision = new Revision($this->configuration);
        $revisions = $faqRevision->get($faqId, $faqLanguage, $faqData['author']);

        $faqUrl = sprintf(
            '%sindex.php?action=faq&cat=%s&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $category->getCategoryIdFromFaq($faqId),
            $faqId,
            $faqLanguage,
        );

        $link = new Link($faqUrl, $this->configuration);
        $link->setTitle($faqData['title']);

        // User permissions
        $userPermission = $this->container->get(id: 'phpmyfaq.faq.permission')->get(Permission::USER, $faqId);
        if (count($userPermission) === 0 || $userPermission[0] === -1) {
            $allUsers = true;
            $restrictedUsers = false;
            $userPermission[0] = -1;
        } else {
            $allUsers = false;
            $restrictedUsers = true;
        }

        // Group permissions
        $groupPermission = $this->container->get(id: 'phpmyfaq.faq.permission')->get(Permission::GROUP, $faqId);
        if (count($groupPermission) === 0 || $groupPermission[0] === -1) {
            $allGroups = true;
            $restrictedGroups = false;
            $groupPermission[0] = -1;
        } else {
            $allGroups = false;
            $restrictedGroups = true;
        }

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));

        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'ad_entry_edit_1') . ' ' . Translation::get(key: 'ad_entry_edit_2'),
            'editExistingFaq' => true,
            'currentRevision' => sprintf('%s 1.%d', Translation::get(key: 'msgRevision'), $selectedRevisionId),
            'numberOfRevisions' => count($revisions),
            'faqId' => $faqId,
            'faqLang' => $faqLanguage,
            'revisions' => $revisions,
            'selectedRevisionId' => $selectedRevisionId ?? $faqData['revision_id'],
            'faqRevisionId' => $faqData['revision_id'],
            'faqData' => $faqData,
            'faqUrl' => $link->toString(),
            'openQuestionId' => 0,
            'notifyUser' => '',
            'notifyEmail' => '',
            'categoryOptions' => $categoryHelper->renderOptions($categories),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqLanguage, false, [], 'lang'),
            'attachments' => $attachmentList,
            'allGroups' => $allGroups,
            'restrictedGroups' => $restrictedGroups,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => $allUsers,
            'restrictedUsers' => $restrictedUsers,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => $this->container->get(id: 'phpmyfaq.admin.changelog')->getByFaqId($faqId),
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => $faqData['active'] === 'yes' ? 'checked' : null,
            'isInActive' => $faqData['active'] !== 'yes' ? 'checked' : null,
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route(path: '/faq/copy/:faqId/:faqLanguage', name: 'admin.faq.copy', methods: ['GET'])]
    public function copy(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $faqId = (int) Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_COPY->value . ':' . $faqId);

        $categories = [];

        $faq->getFaq($faqId, null, true);
        $faqData = $faq->faqRecord;
        $faqData['title'] = 'Copy of ' . $faqData['title'];

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'ad_entry_edit_1') . ' ' . Translation::get(key: 'ad_entry_edit_2'),
            'editExistingFaq' => false,
            'faqId' => 0,
            'faqLang' => $faqLanguage,
            'faqRevisionId' => 0,
            'faqData' => $faqData,
            'openQuestionId' => 0,
            'notifyUser' => '',
            'notifyEmail' => '',
            'categoryOptions' => $categoryHelper->renderOptions($categories),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqLanguage, false, [], 'lang'),
            'attachments' => [],
            'allGroups' => true,
            'restrictedGroups' => false,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => true,
            'restrictedUsers' => false,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => [],
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => null,
            'isInActive' => null,
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route(path: '/faq/translate/:faqId/:faqLanguage', name: 'admin.faq.translate', methods: ['GET'])]
    public function translate(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $faqId = (int) Filter::filterVar($request->attributes->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_TRANSLATE->value . ':' . $faqId);

        $categories = [];

        $faq->getFaq($faqId, null, true);
        $faqData = $faq->faqRecord;
        $faqData['title'] = 'Translation of ' . $faqData['title'];

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'ad_entry_edit_1') . ' ' . Translation::get(key: 'ad_entry_edit_2'),
            'editExistingFaq' => false,
            'faqId' => 0,
            'faqLang' => $faqLanguage,
            'faqRevisionId' => 0,
            'faqData' => $faqData,
            'openQuestionId' => 0,
            'notifyUser' => '',
            'notifyEmail' => '',
            'categoryOptions' => $categoryHelper->renderOptions($categories),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqLanguage, false, [], 'lang'),
            'attachments' => [],
            'allGroups' => true,
            'restrictedGroups' => false,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => true,
            'restrictedUsers' => false,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => [],
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => null,
            'isInActive' => null,
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route(path: '/faq/answer/:questionId/:faqLanguage', name: 'admin.faq.answer', methods: ['GET'])]
    public function answer(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get(id: 'phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $userHelper = $this->container->get(id: 'phpmyfaq.helper.user-helper');

        $questionId = (int) Filter::filterVar($request->attributes->get('questionId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->attributes->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);

        $this->adminLog->log($this->currentUser, AdminLogType::FAQ_ANSWER_ADD->value . ':' . $questionId);

        /** @var Question $question */
        $question = $this->container->get(id: 'phpmyfaq.question');
        $questionData = $question->get($questionId);

        $faqData = [
            'id' => 0,
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'title' => $questionData['question'] ?? '',
            'revision_id' => 0,
            'author' => $this->currentUser->getUserData('display_name'),
            'email' => $this->currentUser->getUserData('email'),
            'comment' => $this->configuration->get(item: 'records.defaultAllowComments') ? 'checked' : null,
        ];

        $categories = [
            'category_id' => $questionData['category_id'] ?? [],
            'category_lang' => $faqLanguage,
        ];

        $this->addExtension(new AttributeExtension(IsoDateTwigExtension::class));
        $this->addExtension(new AttributeExtension(UserNameTwigExtension::class));
        $this->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));
        return $this->render('@admin/content/faq.editor.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            ...$this->getBaseTemplateVars(),
            'header' => Translation::get(key: 'ad_entry_edit_1') . ' ' . Translation::get(key: 'ad_entry_edit_2'),
            'editExistingFaq' => false,
            'faqId' => 0,
            'faqLang' => $faqLanguage,
            'faqRevisionId' => 0,
            'faqData' => $faqData,
            'openQuestionId' => 0,
            'notifyUser' => $questionData['username'] ?? $this->currentUser->getUserData('display_name'),
            'notifyEmail' => $questionData['email'] ?? $this->currentUser->getUserData('email'),
            'categoryOptions' => $categoryHelper->renderOptions($categories),
            'languageOptions' => LanguageHelper::renderSelectLanguage($faqLanguage, false, [], 'lang'),
            'attachments' => [],
            'allGroups' => true,
            'restrictedGroups' => false,
            'groupPermissionOptions' => $this->configuration->get(item: 'security.permLevel') === 'medium'
                ? $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser)
                : '',
            'allUsers' => true,
            'restrictedUsers' => false,
            'userSelection' => $userHelper->getAllUsersForTemplate(-1, true),
            'changelogs' => [],
            'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                $this->currentUser->getUserId(),
                PermissionType::FAQ_APPROVE->value,
            ),
            'isActive' => null,
            'isInActive' => null,
            'nextSolutionId' => $faq->getNextSolutionId(),
            'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
        ]);
    }

    /**
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        $token = Token::getInstance($this->session);

        $canAddAttachments = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::ATTACHMENT_ADD->value,
        );

        $canDeleteAttachments = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::ATTACHMENT_DELETE->value,
        );

        $canTranslateFaqs = $this->currentUser->perm->hasPermission(
            $this->currentUser->getUserId(),
            PermissionType::FAQ_TRANSLATE->value,
        );

        return [
            'csrfToken' => $token->getTokenString('pmf-csrf-token'),
            'csrfTokenDeleteAttachment' => $token->getTokenString('delete-attachment'),
            'csrfTokenUploadAttachment' => $token->getTokenString('upload-attachment'),
            'isEditorEnabled' => $this->configuration->get(item: 'main.enableWysiwygEditor'),
            'isMarkdownEditorEnabled' => $this->configuration->get(item: 'main.enableMarkdownEditor'),
            'isBasicPermission' => $this->configuration->get(item: 'security.permLevel') === 'basic',
            'defaultUrl' => $this->configuration->getDefaultUrl(),
            'canBeNewRevision' => !$this->configuration->get(item: 'records.enableAutoRevisions'),
            'maxAttachmentSize' => $this->configuration->get(item: 'records.maxAttachmentSize'),
            'hasPermissionForAddAttachments' => $canAddAttachments,
            'hasPermissionForDeleteAttachments' => $canDeleteAttachments,
            'hasPermissionForTranslateFaqs' => $canTranslateFaqs,
            'ad_entry_restricted_groups' => Translation::get(key: 'ad_entry_restricted_groups'),
            'ad_entry_userpermission' => Translation::get(key: 'ad_entry_userpermission'),
            'ad_entry_restricted_users' => Translation::get(key: 'ad_entry_restricted_users'),
            'ad_entry_changelog' => Translation::get(key: 'ad_entry_changelog'),
            'ad_entry_changed' => Translation::get(key: 'ad_entry_changed'),
            'ad_admin_notes_hint' => Translation::get(key: 'ad_admin_notes_hint'),
            'ad_admin_notes' => Translation::get(key: 'ad_admin_notes'),
            'ad_entry_changelog_history' => Translation::get(key: 'ad_entry_changelog_history'),
            'ad_gen_reset' => Translation::get(key: 'ad_gen_reset'),
            'ad_entry_save' => Translation::get(key: 'ad_entry_save'),
            'ad_entry_status' => Translation::get(key: 'ad_entry_status'),
            'ad_entry_visibility' => Translation::get(key: 'ad_entry_visibility'),
            'ad_entry_not_visibility' => Translation::get(key: 'ad_entry_not_visibility'),
            'ad_entry_new_revision' => Translation::get(key: 'ad_entry_new_revision'),
            'ad_gen_yes' => Translation::get(key: 'ad_gen_yes'),
            'ad_gen_no' => Translation::get(key: 'ad_gen_no'),
            'ad_entry_allowComments' => Translation::get(key: 'ad_entry_allowComments'),
            'ad_entry_solution_id' => Translation::get(key: 'ad_entry_solution_id'),
            'ad_att_addto' => Translation::get(key: 'ad_att_addto'),
            'ad_att_addto_2' => Translation::get(key: 'ad_att_addto_2'),
            'ad_att_att' => Translation::get(key: 'ad_att_att'),
            'ad_att_butt' => Translation::get(key: 'ad_att_butt'),
            'ad_changerev' => Translation::get(key: 'ad_changerev'),
            'ad_view_faq' => Translation::get(key: 'ad_view_faq'),
        ];
    }
}
