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
 * @copyright 2024 phpMyFAQ Team
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
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Template\Extensions\FormatBytesTwigExtension;
use phpMyFAQ\Template\Extensions\IsoDateTwigExtension;
use phpMyFAQ\Template\Extensions\UserNameTwigExtension;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class FaqController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/faqs', name: 'admin.faqs', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);
        $this->userHasPermission(PermissionType::FAQ_APPROVE);
        $this->userHasPermission(PermissionType::FAQ_EDIT);
        $this->userHasPermission(PermissionType::FAQ_DELETE);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryRelation = new Relation($this->configuration, $category);
        $categoryRelation->setGroups($currentAdminGroups);

        $comments = $this->container->get('phpmyfaq.comments');
        $sessions = $this->container->get('session');

        return $this->render(
            '@admin/content/faq.overview.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'csrfTokenSearch' => Token::getInstance($sessions)->getTokenInput('edit-faq'),
                'csrfTokenOverview' => Token::getInstance($sessions)->getTokenString('faq-overview'),
                'categories' => $category->getCategoryTree(),
                'numberOfRecords' => $categoryRelation->getNumberOfFaqsPerCategory(),
                'numberOfComments' => $comments->getNumberOfCommentsByCategory(),
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route('/faq/add', name: 'admin.faq.add', methods: ['GET'])]
    public function add(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get('phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $faq = $this->container->get('phpmyfaq.faq');
        $userHelper = $this->container->get('phpmyfaq.helper.user-helper');
        $logging = $this->container->get('phpmyfaq.admin.admin-log');
        $session = $this->container->get('session');

        $logging->log($this->currentUser, 'admin-add-faq');
        $categories = [];

        $faqData = [
            'id' => 0,
            'lang' => $this->configuration->getLanguage()->getLanguage(),
            'revision_id' => 0,
            'author' => $this->currentUser->getUserData('display_name'),
            'email' => $this->currentUser->getUserData('email'),
            'comment' => $this->configuration->get('records.defaultAllowComments') ? 'checked' : null,
        ];

        $this->addExtension(new IsoDateTwigExtension());
        $this->addExtension(new UserNameTwigExtension());
        $this->addExtension(new FormatBytesTwigExtension());
        return $this->render(
            '@admin/content/faq.editor.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                'header' => Translation::get('msgAddFAQ'),
                'editExistingFaq' => false,
                'isEditorEnabled' => $this->configuration->get('main.enableWysiwygEditor'),
                'isMarkdownEditorEnabled' => $this->configuration->get('main.enableMarkdownEditor'),
                'isBasicPermission' => $this->configuration->get('security.permLevel') === 'basic',
                'defaultUrl' => $this->configuration->getDefaultUrl(),
                'faqRevisionId' => 0,
                'faqData' => $faqData,
                'openQuestionId' => 0,
                'notifyUser' => '',
                'notifyEmail' => '',
                'categoryOptions' => $categoryHelper->renderOptions($categories),
                'languageOptions' => LanguageHelper::renderSelectLanguage($faqData['lang'], false, [], 'lang'),
                'hasPermissionForAddAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_ADD->value
                ),
                'hasPermissionForDeleteAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_DELETE->value
                ),
                'attachments' => [],
                'allGroups' => true,
                'restrictedGroups' => false,
                'groupPermissionOptions' => ($this->configuration->get('security.permLevel') === 'medium') ?
                    $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser) : '',
                'allUsers' => true,
                'restrictedUsers' => false,
                'userPermissionOptions' => $userHelper->getAllUserOptions(-1, true),
                'changelogs' => [],
                'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::FAQ_APPROVE->value
                ),
                'isActive' => null,
                'isInActive' => null,
                'canBeNewRevision' => false,
                'nextSolutionId' => $faq->getNextSolutionId(),
                'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
                'maxAttachmentSize' => $this->configuration->get('records.maxAttachmentSize'),
            ]
        );
    }

    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     * @todo refactor Twig template variables
     */
    #[Route('/faq/edit/:faqId/:faqLanguage', name: 'admin.faq.edit', methods: ['GET'])]
    public function edit(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentAdminGroups, true);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->buildCategoryTree();

        $categoryHelper = $this->container->get('phpmyfaq.helper.category-helper');
        $categoryHelper->setCategory($category);

        $categoryRelation = new Relation($this->configuration, $category);
        $faq = $this->container->get('phpmyfaq.faq');
        $faqPermission = $this->container->get('phpmyfaq.faq.permission');
        $logging = $this->container->get('phpmyfaq.admin.admin-log');
        $seo = $this->container->get('phpmyfaq.seo');
        $tagging = $this->container->get('phpmyfaq.tags');
        $userHelper = $this->container->get('phpmyfaq.helper.user-helper');

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);
        $faqLanguage = Filter::filterVar($request->get('faqLanguage'), FILTER_SANITIZE_SPECIAL_CHARS);
        $selectedRevisionId = Filter::filterVar($request->get('selectedRevisionId'), FILTER_VALIDATE_INT);

        $logging->log($this->currentUser, 'admin-edit-faq ' . $faqId);

        $categories = $categoryRelation->getCategories($faqId, $faqLanguage);

        $faq->getFaq($faqId, null, true);
        $faqData = $faq->faqRecord;

        // Tags
        $faqData['tags'] = implode(', ', $tagging->getAllTagsById((int) $faqData['id']));

        // SERP
        $seoEntity = new SeoEntity();
        $seoEntity
            ->setType(SeoType::FAQ)
            ->setReferenceId((int) $faqData['id'])
            ->setReferenceLanguage($faqData['lang']);
        $seoData = $seo->get($seoEntity);
        $faqData['serp-title'] = $seoData->getTitle();
        $faqData['serp-description'] = $seoData->getDescription();


        $attList = AttachmentFactory::fetchByRecordId(
            $this->configuration,
            $faqId
        );

        if (is_null($selectedRevisionId)) {
            $selectedRevisionId = $faqData['revision_id'];
        }

        $faqRevision = new Revision($this->configuration);
        $revisions = $faqRevision->get($faqId, $faqData['lang'], $faqData['author']);

        $faqUrl = sprintf(
            '%sindex.php?action=faq&cat=%s&id=%d&artlang=%s',
            $this->configuration->getDefaultUrl(),
            $category->getCategoryIdFromFaq((int) $faqData['id']),
            $faqData['id'],
            $faqData['lang']
        );

        $link = new Link($faqUrl, $this->configuration);
        $link->itemTitle = $faqData['title'];

        // User permissions
        $userPermission = $faqPermission->get(Permission::USER, (int) $faqData['id']);
        if (count($userPermission) == 0 || $userPermission[0] == -1) {
            $allUsers = true;
            $restrictedUsers = false;
            $userPermission[0] = -1;
        } else {
            $allUsers = false;
            $restrictedUsers = true;
        }

        // Group permissions
        $groupPermission = $faqPermission->get(Permission::GROUP, (int) $faqData['id']);
        if (count($groupPermission) == 0 || $groupPermission[0] == -1) {
            $allGroups = true;
            $restrictedGroups = false;
            $groupPermission[0] = -1;
        } else {
            $allGroups = false;
            $restrictedGroups = true;
        }

        $this->addExtension(new IsoDateTwigExtension());
        $this->addExtension(new UserNameTwigExtension());
        $this->addExtension(new FormatBytesTwigExtension());
        return $this->render(
            '@admin/content/faq.editor.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                ... $this->getBaseTemplateVars(),
                'header' => Translation::get('ad_entry_edit_1') . ' ' . Translation::get('ad_entry_edit_2'),
                'editExistingFaq' => true,
                'currentRevision' => sprintf('%s 1.%d', Translation::get('ad_entry_revision'), $selectedRevisionId),
                'isEditorEnabled' => $this->configuration->get('main.enableWysiwygEditor'),
                'isMarkdownEditorEnabled' => $this->configuration->get('main.enableMarkdownEditor'),
                'isBasicPermission' => $this->configuration->get('security.permLevel') === 'basic',
                'defaultUrl' => $this->configuration->getDefaultUrl(),
                'numberOfRevisions' => count($revisions),
                'faqId' => $faqData['id'],
                'faqLang' => $faqData['lang'],
                'revisions' => $revisions,
                'selectedRevisionId' => $selectedRevisionId,
                'faqRevisionId' => $faqData['revision_id'],
                'faqData' => $faqData,
                'faqUrl' => $link->toString(),
                'openQuestionId' => 0,
                'notifyUser' => '',
                'notifyEmail' => '',
                'categoryOptions' => $categoryHelper->renderOptions($categories),
                'languageOptions' => LanguageHelper::renderSelectLanguage($faqLanguage, false, [], 'lang'),
                'hasPermissionForAddAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_ADD->value
                ),
                'hasPermissionForDeleteAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_DELETE->value
                ),
                'attachments' => $attList,
                'allGroups' => $allGroups,
                'restrictedGroups' => $restrictedGroups,
                'groupPermissionOptions' => ($this->configuration->get('security.permLevel') === 'medium') ?
                    $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser) : '',
                'allUsers' => $allUsers,
                'restrictedUsers' => $restrictedUsers,
                'userPermissionOptions' => $userHelper->getAllUserOptions(-1, true),
                'changelogs' => $this->container->get('phpmyfaq.admin.changelog')->getByFaqId((int) $faqData['id']),
                'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::FAQ_APPROVE->value
                ),
                'isActive' => $faqData['active'] === 'yes' ? 'checked' : null,
                'isInActive' => $faqData['active'] !== 'yes' ? 'checked' : null,
                'canBeNewRevision' => !$this->configuration->get('records.enableAutoRevisions'),
                'nextSolutionId' => $faq->getNextSolutionId(),
                'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
                'maxAttachmentSize' => $this->configuration->get('records.maxAttachmentSize'),
            ]
        );
    }


    /**
     * @throws \Exception
     * @return array<string, string>
     */
    private function getBaseTemplateVars(): array
    {
        $session = $this->container->get('session');
        $token = Token::getInstance($session);

        return [
            'csrfToken' => $token->getTokenString('edit-faq'),
            'csrfTokenDeleteAttachment' => $token->getTokenString('delete-attachment'),
            'csrfTokenUploadAttachment' => $token->getTokenString('upload-attachment'),
            'ad_entry_keywords' => Translation::get('ad_entry_keywords'),
            'ad_entry_author' => Translation::get('ad_entry_author'),
            'ad_entry_grouppermission' => Translation::get('ad_entry_grouppermission'),
            'ad_entry_all_groups' => Translation::get('ad_entry_all_groups'),
            'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
            'ad_entry_userpermission' => Translation::get('ad_entry_userpermission'),
            'ad_entry_restricted_users' => Translation::get('ad_entry_restricted_users'),
            'ad_entry_changelog' => Translation::get('ad_entry_changelog'),
            'ad_entry_changed' => Translation::get('ad_entry_changed'),
            'ad_admin_notes_hint' => Translation::get('ad_admin_notes_hint'),
            'ad_admin_notes' => Translation::get('ad_admin_notes'),
            'ad_entry_changelog_history' => Translation::get('ad_entry_changelog_history'),
            'ad_entry_revision' => Translation::get('ad_entry_revision'),
            'ad_gen_reset' => Translation::get('ad_gen_reset'),
            'ad_entry_save' => Translation::get('ad_entry_save'),
            'ad_entry_status' => Translation::get('ad_entry_status'),
            'ad_entry_visibility' => Translation::get('ad_entry_visibility'),
            'ad_entry_not_visibility' => Translation::get('ad_entry_not_visibility'),
            'ad_entry_new_revision' => Translation::get('ad_entry_new_revision'),
            'ad_gen_yes' => Translation::get('ad_gen_yes'),
            'ad_gen_no' => Translation::get('ad_gen_no'),
            'ad_entry_allowComments' => Translation::get('ad_entry_allowComments'),
            'ad_entry_solution_id' => Translation::get('ad_entry_solution_id'),
            'ad_att_addto' => Translation::get('ad_att_addto'),
            'ad_att_addto_2' => Translation::get('ad_att_addto_2'),
            'ad_att_att' => Translation::get('ad_att_att'),
            'ad_att_butt' => Translation::get('ad_att_butt'),
            'ad_changerev' => Translation::get('ad_changerev'),
            'ad_view_faq' => Translation::get('ad_view_faq'),
        ];
    }
}
