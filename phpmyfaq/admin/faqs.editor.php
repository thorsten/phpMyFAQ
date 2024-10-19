<?php

/**
 * The FAQ record editor.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Revision;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\SeoType;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Question;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Tags;
use phpMyFAQ\Template\FormatBytesTwigExtension;
use phpMyFAQ\Template\IsoDateTwigExtension;
use phpMyFAQ\Template\TwigWrapper;
use phpMyFAQ\Template\UserNameTwigExtension;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$user = CurrentUser::getCurrentUser($faqConfig);
$currentUserId = $user->getUserId();

if (
    (
    $user->perm->hasPermission($currentUserId, PermissionType::FAQ_EDIT->value) ||
    $user->perm->hasPermission($currentUserId, PermissionType::FAQ_EDIT->value)) &&
    !Database::checkOnEmptyTable('faqcategories')
) {
    $category = new Category($faqConfig, [], false);

    if ($faqConfig->get('main.enableCategoryRestrictions')) {
        $category = new Category($faqConfig, $currentAdminGroups, true);
    }

    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildCategoryTree();

    $categoryRelation = new Relation($faqConfig, $category);

    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);

    $faq = new Faq($faqConfig);

    $faqPermission = new Permission($faqConfig);
    $questionObject = new Question($faqConfig);
    $changelog = new Changelog($faqConfig);
    $userHelper = new UserHelper($user);
    $tagging = new Tags($faqConfig);
    $seo = new Seo($faqConfig);
    $logging = new AdminLog($faqConfig);

    $date = new Date($faqConfig);

    $twig = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates');
    $twig->addExtension(new FormatBytesTwigExtension());
    $twig->addExtension(new IsoDateTwigExtension());
    $twig->addExtension(new UserNameTwigExtension());
    $template = $twig->loadTemplate('./admin/content/faq.editor.twig');

    $selectedCategory = '';
    $queryString = '';
    $categories = [];
    $faqData = [
        'id' => 0,
        'lang' => $faqLangCode,
        'revision_id' => 0,
        'title' => '',
        'dateStart' => '',
        'dateEnd' => '',
    ];

    if ('takequestion' === $action) {
        $questionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $question = $questionObject->get($questionId);
        $selectedCategory = $question['category_id'];
        $faqData['title'] = $question['question'];
        $notifyUser = $question['username'];
        $notifyEmail = $question['email'];
        $categories = [
            'category_id' => $selectedCategory,
            'category_lang' => $faqData['lang'],
        ];
    } else {
        $questionId = 0;
        $notifyUser = '';
        $notifyEmail = '';
    }

    if ('editentry' === $action) {
        $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $translateTo = Filter::filterInput(INPUT_GET, 'translateTo', FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);
        $logging->log($user, 'admin-edit-faq ' . $id);

        if (!is_null($translateTo)) {
            $faqData['lang'] = $lang = $translateTo;
            $selectedCategory = $categoryId;
        }

        if ((!isset($selectedCategory) && !isset($faqData['title'])) || !is_null($id)) {
            $logging = new AdminLog($faqConfig);
            $logging->log($user, 'admin-edit-faq ' . $id);

            $categories = $categoryRelation->getCategories($id, $lang);
            if (count($categories) === 0) {
                $categories = [
                    'category_id' => $selectedCategory,
                    'category_lang' => $faqData['lang'],
                ];
            }

            $faq->getFaq($id, null, true);
            $faqData = $faq->faqRecord;
            if (!is_null($translateTo)) {
                $faqData['lang'] = $translateTo; // once again
            }
            $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));
        } else {
            $queryString = 'insertentry';
            if (isset($categoryId)) {
                $categories = ['category_id' => $categoryId, 'category_lang' => $lang];
            }
        }
    } elseif ('copyentry' === $action) {
        $logging->log($user, 'admin-copy-faq');
        $faqData['id'] = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $faqData['lang'] = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $categories = $categoryRelation->getCategories($faqData['id'], $faqData['lang']);

        $faq->getFaq($faqData['id'], null, true);

        $faqData = $faq->faqRecord;
        $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));
        $faqData['id'] = 0;
        $faqData['revision_id'] = 0;
        $queryString = 'insertentry';
    } else {
        $logging->log($user, 'admin-add-faq');
        $queryString = 'insertentry';
        if (!is_array($categories)) {
            $categories = [];
        }
    }

    // Revisions
    $selectedRevisionId = Filter::filterInput(INPUT_POST, 'selectedRevisionId', FILTER_VALIDATE_INT);
    if (is_null($selectedRevisionId)) {
        $selectedRevisionId = $faqData['revision_id'];
    }

    // User permissions
    $userPermission = $faqPermission->get(Permission::USER, $faqData['id']);
    if (count($userPermission) == 0 || $userPermission[0] == -1) {
        $allUsers = true;
        $restrictedUsers = false;
        $userPermission[0] = -1;
    } else {
        $allUsers = false;
        $restrictedUsers = true;
    }

    // Group permissions
    $groupPermission = $faqPermission->get(Permission::GROUP, $faqData['id']);
    if (count($groupPermission) == 0 || $groupPermission[0] == -1) {
        $allGroups = true;
        $restrictedGroups = false;
        $groupPermission[0] = -1;
    } else {
        $allGroups = false;
        $restrictedGroups = true;
    }

    // SEO
    $seoEntity = new SeoEntity();
    $seoEntity
        ->setType(SeoType::FAQ)
        ->setReferenceId($faqData['id'])
        ->setReferenceLanguage($faqData['lang']);
    $seoData = $seo->get($seoEntity);
    $faqData['serp-title'] = $seoData->getTitle();
    $faqData['serp-description'] = $seoData->getDescription();

    // Set data for forms
    $faqData['title'] = ($faqData['title'] ?? '');
    $faqData['content'] = (isset($faqData['content']) ? trim($faqData['content']) : '');
    $faqData['tags'] = $faqData['tags'] ?? '';
    $faqData['keywords'] = ($faqData['keywords'] ?? '');
    $faqData['author'] = ($faqData['author'] ?? $user->getUserData('display_name'));
    $faqData['email'] = ($faqData['email'] ?? $user->getUserData(
        'email'
    ));
    $faqData['isoDate'] = ($faqData['date'] ?? date('Y-m-d H:i'));
    $faqData['date'] = (isset($faqData['date']) ? $date->format($faqData['date']) : $date->format(date('Y-m-d H:i')));
    $faqData['changed'] ??= '';

    if (isset($faqData['comment']) && $faqData['comment'] == 'y') {
        $faqData['comment'] = ' checked';
    } elseif ($faqConfig->get('records.defaultAllowComments')) {
        $faqData['comment'] = ' checked';
    } else {
        $faqData['comment'] = '';
    }

    $templateVars = [
        'ad_record_faq' => Translation::get('ad_record_faq'),
        'ad_menu_faq_meta' => Translation::get('ad_menu_faq_meta'),
        'ad_record_permissions' => Translation::get('ad_record_permissions'),
        'ad_admin_notes' => Translation::get('ad_admin_notes'),
        'ad_entry_changelog' => Translation::get('ad_entry_changelog'),
    ];

    // Header
    if (0 !== $faqData['id'] && 'copyentry' !== $action) {
        $currentRevision = sprintf('%s 1.%d', Translation::get('ad_entry_revision'), $selectedRevisionId);

        $faqUrl = sprintf(
            '%sindex.php?action=faq&cat=%s&id=%d&artlang=%s',
            $faqConfig->getDefaultUrl(),
            $category->getCategoryIdFromFaq($faqData['id']),
            $faqData['id'],
            $faqData['lang']
        );

        $link = new Link($faqUrl, $faqConfig);
        $link->itemTitle = $faqData['title'];

        $templateVars = [
            ...$templateVars,
            'adminFaqEditorHeader' => Translation::get('ad_entry_edit_1') . ' ' . Translation::get('ad_entry_edit_2'),
            'editExistingFaq' => true,
            'currentRevision' => $currentRevision,
            'faqUrl' => $link->toString(),
            'ad_view_faq' => Translation::get('ad_view_faq'),
        ];
    } else {
        $templateVars = [
            ...$templateVars,
            'adminFaqEditorHeader' => Translation::get('ad_entry_add'),
            'editExistingFaq' => false,
        ];
    }

    //
    // Revisions
    //
    if ($user->perm->hasPermission($currentUserId, PermissionType::REVISION_UPDATE->value) && $action === 'editentry') {
        $faqRevision = new Revision($faqConfig);
        $revisions = $faqRevision->get($faqData['id'], $faqData['lang'], $faqData['author']);

        if (isset($selectedRevisionId) && isset($faqData['revision_id']) && $selectedRevisionId !== $faqData['revision_id']) {
            $faq->getFaq($faqData['id'], $selectedRevisionId, true);
            $faqData = $faq->faqRecord;
            $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));
            $faqData['revision_id'] = $selectedRevisionId;
        }

        $templateVars = [
            ...$templateVars,
            'numberOfRevisions' => count($revisions),
            'faqId' => $faqData['id'],
            'faqLang' => $faqData['lang'],
            'faqRevisionId' => $faqData['revision_id'],
            'ad_changerev' => Translation::get('ad_changerev'),
            'revisions' => $revisions,
            'selectedRevisionId' => $selectedRevisionId,
            'ad_entry_revision' => Translation::get('ad_entry_revision'),
        ];
    }

    if (isset($faqData['active']) && $faqData['active'] === 'yes') {
        $isActive = ' checked';
        $isInActive = null;
    } else {
        $isActive = null;
        $isInActive = ' checked';
    }

    // Override value, if FAQs activated by default
    if ($faqConfig->get('records.defaultActivation') && $queryString === 'insertentry') {
        $isActive = ' checked';
        $isInActive = null;
    }

    $attList = AttachmentFactory::fetchByRecordId(
        $faqConfig,
        $faqData['id']
    );

    $templateVars = [
        ...$templateVars,
        'isEditorEnabled' => $faqConfig->get('main.enableWysiwygEditor'),
        'isMarkdownEditorEnabled' => $faqConfig->get('main.enableMarkdownEditor'),
        'isBasicPermission' => $faqConfig->get('security.permLevel') === 'basic',
        'defaultUrl' => $faqConfig->getDefaultUrl(),
        'faqRevisionId' => $faqData['revision_id'],
        'faqData' => $faqData,
        'openQuestionId' => $questionId,
        'notifyUser' => $notifyUser,
        'notifyEmail' => $notifyEmail,
        'csrfToken' => Token::getInstance()->getTokenString('edit-faq'),
        'ad_entry_theme' => Translation::get('ad_entry_theme'),
        'msgNoHashAllowed' => Translation::get('msgNoHashAllowed'),
        'msgShowHelp' => Translation::get('msgShowHelp'),
        'ad_entry_content' => Translation::get('ad_entry_content'),
        'ad_entry_category' => Translation::get('ad_entry_category'),
        'categoryOptions' => $categoryHelper->renderOptions($categories),
        'ad_entry_locale' => Translation::get('ad_entry_locale'),
        'languageOptions' => LanguageHelper::renderSelectLanguage($faqData['lang'], false, [], 'lang'),
        'hasPermissionForAddAttachments' => $user->perm->hasPermission(
            $currentUserId,
            PermissionType::ATTACHMENT_ADD->value
        ),
        'hasPermissionForDeleteAttachments' => $user->perm->hasPermission(
            $currentUserId,
            PermissionType::ATTACHMENT_DELETE->value
        ),
        'ad_menu_attachments' => Translation::get('ad_menu_attachments'),
        'csrfTokenDeleteAttachment' => Token::getInstance()->getTokenString('delete-attachment'),
        'attachments' => $attList,
        'ad_att_add' => Translation::get('ad_att_add'),
        'ad_entry_tags' => Translation::get('ad_entry_tags'),
        'ad_entry_keywords' => Translation::get('ad_entry_keywords'),
        'ad_entry_author' => Translation::get('ad_entry_author'),
        'msgEmail' => Translation::get('msgEmail'),
        'msgSeoCenter' => Translation::get('seoCenter'),
        'msgSerp' => Translation::get('msgSerp'),
        'msgSerpTitle' => Translation::get('msgSerpTitle'),
        'msgSerpDescription' => Translation::get('msgSerpDescription'),
        'ad_entry_grouppermission' => Translation::get('ad_entry_grouppermission'),
        'ad_entry_all_groups' => Translation::get('ad_entry_all_groups'),
        'allGroups' => $allGroups,
        'restrictedGroups' => $restrictedGroups,
        'ad_entry_restricted_groups' => Translation::get('ad_entry_restricted_groups'),
        'groupPermissionOptions' => ($faqConfig->get('security.permLevel') === 'medium') ?
            $user->perm->getAllGroupsOptions($groupPermission, $user) : '',
        'ad_entry_userpermission' => Translation::get('ad_entry_userpermission'),
        'allUsers' => $allUsers,
        'ad_entry_all_users' => Translation::get('ad_entry_all_users'),
        'restrictedUsers' => $restrictedUsers,
        'ad_entry_restricted_users' => Translation::get('ad_entry_restricted_users'),
        'userPermissionOptions' => $userHelper->getAllUserOptions($userPermission[0], true),
        'ad_entry_changelog' => Translation::get('ad_entry_changelog'),
        'ad_entry_date' => Translation::get('ad_entry_date'),
        'ad_entry_changed' => Translation::get('ad_entry_changed'),
        'ad_admin_notes_hint' => Translation::get('ad_admin_notes_hint'),
        'ad_admin_notes' => Translation::get('ad_admin_notes'),
        'ad_entry_changelog_history' => Translation::get('ad_entry_changelog_history'),
        'changelogs' => $changelog->getByFaqId($faqData['id']),
        'ad_entry_revision' => Translation::get('ad_entry_revision'),
        'ad_gen_reset' => Translation::get('ad_gen_reset'),
        'ad_entry_save' => Translation::get('ad_entry_save'),
        'msgUpdateFaqDate' => Translation::get('msgUpdateFaqDate'),
        'msgKeepFaqDate' => Translation::get('msgKeepFaqDate'),
        'msgEditFaqDat' => Translation::get('msgEditFaqDat'),
        'ad_entry_status' => Translation::get('ad_entry_status'),
        'hasPermissionForApprove' => $user->perm->hasPermission($currentUserId, PermissionType::FAQ_APPROVE->value),
        'isActive' => $isActive,
        'isInActive' => $isInActive,
        'ad_entry_visibility' => Translation::get('ad_entry_visibility'),
        'ad_entry_not_visibility' => Translation::get('ad_entry_not_visibility'),
        'canBeNewRevision' => $queryString !== 'insertentry' && !$faqConfig->get('records.enableAutoRevisions'),
        'ad_entry_new_revision' => Translation::get('ad_entry_new_revision'),
        'ad_gen_yes' => Translation::get('ad_gen_yes'),
        'ad_gen_no' => Translation::get('ad_gen_no'),
        'ad_entry_sticky' => Translation::get('ad_entry_sticky'),
        'ad_entry_allowComments' => Translation::get('ad_entry_allowComments'),
        'ad_entry_solution_id' => Translation::get('ad_entry_solution_id'),
        'nextSolutionId' => $faq->getNextSolutionId(),
        'nextFaqId' => 0 === $faqData['id'] ? $faqConfig->getDb()->nextId(
            Database::getTablePrefix() . 'faqdata',
            'id'
        ) : $faqData['id'],
        'ad_att_addto' => Translation::get('ad_att_addto'),
        'ad_att_addto_2' => Translation::get('ad_att_addto_2'),
        'ad_att_att' => Translation::get('ad_att_att'),
        'maxAttachmentSize' => $faqConfig->get('records.maxAttachmentSize'),
        'csrfTokenUploadAttachment' => Token::getInstance()->getTokenString('upload-attachment'),
        'msgAttachmentsFilesize' => Translation::get('msgAttachmentsFilesize'),
        'ad_att_butt' => Translation::get('ad_att_butt'),
    ];

    echo $template->render($templateVars);
} elseif (
    $user->perm->hasPermission($currentUserId, PermissionType::FAQ_EDIT->value) &&
    !Database::checkOnEmptyTable('faqcategories')
) {
    require __DIR__ . '/no-permission.php';
} elseif (
    $user->perm->hasPermission($currentUserId, PermissionType::FAQ_EDIT->value) &&
    Database::checkOnEmptyTable('faqcategories')
) {
    echo Translation::get('no_cats');
}
