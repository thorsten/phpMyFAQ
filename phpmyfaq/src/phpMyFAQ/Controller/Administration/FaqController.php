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

use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Helper\LanguageHelper;
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
                'ad_record_faq' => Translation::get('ad_record_faq'),
                'ad_menu_faq_meta' => Translation::get('ad_menu_faq_meta'),
                'ad_record_permissions' => Translation::get('ad_record_permissions'),
                'adminFaqEditorHeader' => Translation::get('ad_entry_add'),
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
                'csrfToken' => Token::getInstance($session)->getTokenString('edit-faq'),
                'msgQuestion' => Translation::get('msgQuestion'),
                'msgNoHashAllowed' => Translation::get('msgNoHashAllowed'),
                'msgShowHelp' => Translation::get('msgShowHelp'),
                'ad_entry_content' => Translation::get('ad_entry_content'),
                'ad_entry_category' => Translation::get('ad_entry_category'),
                'categoryOptions' => $categoryHelper->renderOptions($categories),
                'ad_entry_locale' => Translation::get('ad_entry_locale'),
                'languageOptions' => LanguageHelper::renderSelectLanguage('de', false, [], 'lang'),
                'hasPermissionForAddAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_ADD->value
                ),
                'hasPermissionForDeleteAttachments' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::ATTACHMENT_DELETE->value
                ),
                'ad_menu_attachments' => Translation::get('ad_menu_attachments'),
                'csrfTokenDeleteAttachment' => Token::getInstance($session)->getTokenString('delete-attachment'),
                'attachments' => [],
                'ad_att_add' => Translation::get('ad_att_add'),
                'ad_entry_tags' => Translation::get('ad_entry_tags'),
                'ad_entry_keywords' => Translation::get('ad_entry_keywords'),
                'ad_entry_author' => Translation::get('ad_entry_author'),
                'msgEmail' => Translation::get('msgEmail'),
                'msgSeoCenter' => Translation::get('seoCenter'),
                'msgSerp' => Translation::get('msgSerp'),
                'msgSerpTitle' => Translation::get('msgSerpTitle'),
                'ad_entry_grouppermission' => Translation::get('ad_entry_grouppermission'),
                'ad_entry_all_groups' => Translation::get('ad_entry_all_groups'),
                'allGroups' => true,
                'restrictedGroups' => false,
                'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
                'groupPermissionOptions' => ($this->configuration->get('security.permLevel') === 'medium') ?
                    $this->currentUser->perm->getAllGroupsOptions([-1], $this->currentUser) : '',
                'ad_entry_userpermission' => Translation::get('ad_entry_userpermission'),
                'allUsers' => true,
                'msgAccessAllUsers' => Translation::get('msgAccessAllUsers'),
                'restrictedUsers' => false,
                'ad_entry_restricted_users' => Translation::get('ad_entry_restricted_users'),
                'userPermissionOptions' => $userHelper->getAllUserOptions(-1, true),
                'ad_entry_changelog' => Translation::get('ad_entry_changelog'),
                'msgDate' => Translation::get('msgDate'),
                'ad_entry_changed' => Translation::get('ad_entry_changed'),
                'ad_admin_notes_hint' => Translation::get('ad_admin_notes_hint'),
                'ad_admin_notes' => Translation::get('ad_admin_notes'),
                'ad_entry_changelog_history' => Translation::get('ad_entry_changelog_history'),
                'changelogs' => [],
                'ad_entry_revision' => Translation::get('ad_entry_revision'),
                'ad_gen_reset' => Translation::get('ad_gen_reset'),
                'ad_entry_save' => Translation::get('ad_entry_save'),
                'msgUpdateFaqDate' => Translation::get('msgUpdateFaqDate'),
                'msgKeepFaqDate' => Translation::get('msgKeepFaqDate'),
                'msgEditFaqDat' => Translation::get('msgEditFaqDat'),
                'ad_entry_status' => Translation::get('ad_entry_status'),
                'hasPermissionForApprove' => $this->currentUser->perm->hasPermission(
                    $this->currentUser->getUserId(),
                    PermissionType::FAQ_APPROVE->value
                ),
                'isActive' => null,
                'isInActive' => null,
                'ad_entry_visibility' => Translation::get('ad_entry_visibility'),
                'ad_entry_not_visibility' => Translation::get('ad_entry_not_visibility'),
                'canBeNewRevision' => false,
                'ad_entry_new_revision' => Translation::get('ad_entry_new_revision'),
                'ad_gen_yes' => Translation::get('ad_gen_yes'),
                'ad_gen_no' => Translation::get('ad_gen_no'),
                'msgStickyFAQ' => Translation::get('msgStickyFAQ'),
                'ad_entry_allowComments' => Translation::get('ad_entry_allowComments'),
                'ad_entry_solution_id' => Translation::get('ad_entry_solution_id'),
                'nextSolutionId' => $faq->getNextSolutionId(),
                'nextFaqId' => $this->configuration->getDb()->nextId(Database::getTablePrefix() . 'faqdata', 'id'),
                'ad_att_addto' => Translation::get('ad_att_addto'),
                'ad_att_addto_2' => Translation::get('ad_att_addto_2'),
                'ad_att_att' => Translation::get('ad_att_att'),
                'maxAttachmentSize' => $this->configuration->get('records.maxAttachmentSize'),
                'csrfTokenUploadAttachment' => Token::getInstance($session)->getTokenString('upload-attachment'),
                'msgAttachmentsFilesize' => Translation::get('msgAttachmentsFilesize'),
                'ad_att_butt' => Translation::get('ad_att_butt'),
            ]
        );
    }
}
