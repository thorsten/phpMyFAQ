<?php

/**
 * The FAQ record editor.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Changelog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Link;
use phpMyFAQ\AdminLog;
use phpMyFAQ\Question;
use phpMyFAQ\Revision;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Utils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$faqConfig = Configuration::getConfigurationInstance();
$currentUserId = CurrentUser::getCurrentUser($faqConfig)->getUserId();

if (
    ($user->perm->hasPermission($currentUserId, 'edit_faq') ||
     $user->perm->hasPermission($currentUserId, 'add_faq')) && !Database::checkOnEmptyTable('faqcategories')
) {
    $category = new Category($faqConfig, [], false);

    if ($faqConfig->get('main.enableCategoryRestrictions')) {
        $category = new Category($faqConfig, $currentAdminGroups, true);
    }

    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildCategoryTree();

    $categoryRelation = new CategoryRelation($faqConfig, $category);

    $categoryHelper = new CategoryHelper();
    $categoryHelper->setCategory($category);

    $faq = new Faq($faqConfig);

    $faqPermission = new FaqPermission($faqConfig);

    $questionObject = new Question($faqConfig);

    $changelog = new Changelog($faqConfig);

    $userHelper = new UserHelper($user);

    $selectedCategory = '';
    $categories = [];
    $faqData = [
        'id' => 0,
        'lang' => $faqLangCode,
        'revision_id' => 0,
        'title' => '',
        'dateStart' => '',
        'dateEnd' => '',
    ];

    $tagging = new Tags($faqConfig);
    $date = new Date($faqConfig);

    if ('takequestion' === $action) {
        $questionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $question = $questionObject->getQuestion($questionId);
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

    if ('editpreview' === $action) {
        $faqData['id'] = Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!is_null($faqData['id'])) {
            $queryString = 'saveentry&id=' . $faqData['id'];
        } else {
            $queryString = 'insertentry';
        }

        $faqData['lang'] = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $selectedCategory = Filter::filterInputArray(
            INPUT_POST,
            [
                'rubrik' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ],
            ]
        );
        if (is_array($selectedCategory)) {
            foreach ($selectedCategory as $cats) {
                $categories[] = ['category_id' => $cats, 'category_lang' => $faqData['lang']];
            }
        }
        $faqData['active'] = Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['keywords'] = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['title'] = Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['content'] = Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['author'] = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['email'] = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $faqData['comment'] = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['solution_id'] = Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
        $faqData['revision_id'] = Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT, 0);
        $faqData['sticky'] = Filter::filterInput(INPUT_POST, 'sticky', FILTER_VALIDATE_INT);
        $faqData['tags'] = Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['changed'] = Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['dateStart'] = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['dateEnd'] = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['content'] = html_entity_decode((string) $faqData['content']);
    } elseif ('editentry' === $action) {
        $id = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lang = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $translateTo = Filter::filterInput(INPUT_GET, 'translateTo', FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterInput(INPUT_GET, 'cat', FILTER_VALIDATE_INT);

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

            $faq->getRecord($id, null, true);
            $faqData = $faq->faqRecord;
            if (!is_null($translateTo)) {
                $faqData['lang'] = $translateTo; // once again
            }
            $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));

            $queryString = 'saveentry&amp;id=' . $faqData['id'];
        } else {
            $queryString = 'insertentry';
            if (isset($categoryId)) {
                $categories = ['category_id' => $categoryId, 'category_lang' => $lang];
            }
        }
    } elseif ('copyentry' === $action) {
        $faqData['id'] = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $faqData['lang'] = Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_SPECIAL_CHARS);
        $categories = $categoryRelation->getCategories($faqData['id'], $faqData['lang']);

        $faq->getRecord($faqData['id'], null, true);

        $faqData = $faq->faqRecord;
        $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));
        $queryString = 'insertentry';
    } else {
        $logging = new AdminLog($faqConfig);
        $logging->log($user, 'admin-add-faq');
        $queryString = 'insertentry';
        if (!is_array($categories)) {
            $categories = [];
        }
    }

    // Revisions
    $selectedRevisionId = Filter::filterInput(INPUT_POST, 'revisionid_selected', FILTER_VALIDATE_INT);
    if (is_null($selectedRevisionId)) {
        $selectedRevisionId = $faqData['revision_id'];
    }

    // User permissions
    $userPermission = $faqPermission->get(FaqPermission::USER, $faqData['id']);
    if (count($userPermission) == 0 || $userPermission[0] == -1) {
        $allUsers = true;
        $restrictedUsers = false;
        $userPermission[0] = -1;
    } else {
        $allUsers = false;
        $restrictedUsers = true;
    }

    // Group permissions
    $groupPermission = $faqPermission->get(FaqPermission::GROUP, $faqData['id']);
    if (count($groupPermission) == 0 || $groupPermission[0] == -1) {
        $allGroups = true;
        $restrictedGroups = false;
        $groupPermission[0] = -1;
    } else {
        $allGroups = false;
        $restrictedGroups = true;
    }

    // Set data for forms
    $faqData['title'] = (isset($faqData['title']) ? Strings::htmlentities($faqData['title'], ENT_HTML5 | ENT_COMPAT) : '');
    $faqData['content'] =
        (isset($faqData['content']) ? trim(Strings::htmlentities($faqData['content'], ENT_COMPAT, 'utf-8', true)) : '');
    $faqData['tags'] = (isset($faqData['tags']) ? Strings::htmlentities($faqData['tags']) : '');
    $faqData['keywords'] = (isset($faqData['keywords']) ? Strings::htmlentities($faqData['keywords']) : '');
    $faqData['author'] = (isset($faqData['author']) ? Strings::htmlentities(
        $faqData['author']
    ) : $user->getUserData('display_name'));
    $faqData['email'] = (isset($faqData['email']) ? Strings::htmlentities($faqData['email']) : $user->getUserData(
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

        ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i aria-hidden="true" class="fa fa-edit"></i>
                <?= Translation::get('ad_entry_edit_1') ?>
                <?= Translation::get('ad_entry_edit_2') ?>
            </h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <div class="btn-group mr-2">
              <span class="btn btn-sm btn-info">
                <i class="fa fa-hashtag" aria-hidden="true"></i>
                <?= $currentRevision ?>
              </span>
                    <a href="<?= $link->toString() ?>" class="btn btn-sm btn-success">
                        <i class="fa fa-arrow-alt-circle-right" aria-hidden="true"></i>
                        <?= Translation::get('ad_view_faq') ?>
                    </a>
                </div>
            </div>
        </div>

    <?php } else { ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">
                <i aria-hidden="true" class="fa fa-edit"></i>
                <?= Translation::get('ad_entry_add') ?>
            </h1>
        </div>

    <?php } ?>

        <div class="row">
            <div class="col-lg-9">
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="nav-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tab-question-answer" role="tab">
                                    <i class="fa fa-pencil-square-o"></i> <?= Translation::get('ad_record_faq') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-meta-data" role="tab">
                                    <i class="fa fa-database"></i> <?= Translation::get('ad_menu_faq_meta') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-permissions" role="tab">
                                    <i class="fa fa-unlock-alt"></i> <?= Translation::get('ad_record_permissions') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tab-notes-changelog" role="tab">
                                    <i class="fa fa-sticky-note-o"></i>
                                    <?= Translation::get('ad_admin_notes') . ' / ' . Translation::get('ad_entry_changelog') ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="tab-question-answer">
                                <!-- Revision -->
                                <?php
                                if ($user->perm->hasPermission($currentUserId, 'changebtrevs') && $action === 'editentry') {
                                    $faqRevision = new Revision($faqConfig);
                                    $revisions = $faqRevision->get($faqData['id'], $faqData['lang'], $faqData['author']);
                                    if (count($revisions)) { ?>
                                        <div class="form-group mb-2">
                                            <form id="selectRevision" name="selectRevision" method="post"
                                                  action="?action=editentry&amp;id=<?= $faqData['id'] ?>&amp;lang=<?= $faqData['lang'] ?>">
                                                <select name="revisionid_selected" onchange="this.form.submit();"
                                                        class="form-select">
                                                    <option value="<?= $faqData['revision_id'] ?>">
                                                        <?= Translation::get('ad_changerev') ?>
                                                    </option>
                                                    <?php foreach ($revisions as $revisionData) { ?>
                                                        <option value="<?= $revisionData['revision_id'] ?>" <?php if ($selectedRevisionId == $revisionData['revision_id']) {
                                                            echo 'selected';
                                                                       }
                                                                        ?>>
                                                            <?php printf(
                                                                '%s 1.%d: %s - %s',
                                                                Translation::get('ad_entry_revision'),
                                                                $revisionData['revision_id'],
                                                                Date::createIsoDate($revisionData['updated']),
                                                                $revisionData['author']
                                                            );
                                                            ?>
                                                        </option>
                                                        <?php
                                                    }
                                                    ?>
                                                </select>
                                            </form>
                                        </div>
                                    <?php }
                                    if (
                                        isset($selectedRevisionId) &&
                                        isset($faqData['revision_id']) &&
                                        $selectedRevisionId !== $faqData['revision_id']
                                    ) {
                                        $faq->language = $faqData['lang'];
                                        $faq->getRecord($faqData['id'], $selectedRevisionId, true);
                                        $faqData = $faq->faqRecord;
                                        $faqData['tags'] = implode(', ', $tagging->getAllTagsById($faqData['id']));
                                        $faqData['revision_id'] = $selectedRevisionId;
                                    }
                                }
                                ?>

                              <form id="faqEditor" action="?action=<?= $queryString ?>" method="post"
                                    data-pmf-enable-editor="<?= $faqConfig->get('main.enableWysiwygEditor') ?>"
                                    data-pmf-editor-language="en"
                                    data-pmf-default-url="<?= $faqConfig->getDefaultUrl() ?>">
                                <input type="hidden" name="revision_id" id="revision_id" value="<?= $faqData['revision_id'] ?>">
                                <input type="hidden" name="record_id" id="record_id" value="<?= $faqData['id'] ?>">
                                <input type="hidden" name="openQuestionId" id="openQuestionId" value="<?= $questionId ?>">
                                  <input type="hidden" name="notifyUser" id="notifyUser"
                                         value="<?= Strings::htmlentities($notifyUser) ?>">
                                  <input type="hidden" name="notifyEmail" id="notifyEmail"
                                         value="<?= Strings::htmlentities($notifyEmail) ?>">
                                <?= Token::getInstance()->getTokenInput('edit-faq') ?>


                                <!-- Question -->
                                <div class="form-group mb-2">
                                    <input type="text" name="question" id="question"
                                           class="form-control form-control-lg"
                                           placeholder="<?= Translation::get('ad_entry_theme') ?>"
                                           value="<?= $faqData['title'] ?>">
                                </div>

                                <!-- Answer -->
                                <?php if ($faqConfig->get('main.enableWysiwygEditor')): ?>
                                    <div class="row">
                                        <div class="col-lg-12">
                                            <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                                            <textarea id="editor" name="answer" class="form-control" rows="7"
                                                      placeholder="<?= Translation::get('ad_entry_content') ?>"
                                            ><?= $faqData['content'] ?></textarea>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($faqConfig->get('main.enableMarkdownEditor')) : ?>
                                    <div class="row">
                                      <div class="col-lg-12">
                                        <ul class="nav nav-tabs mb-2" id="markdown-tabs">
                                          <li class="nav-item">
                                            <a class="nav-link active" data-bs-toggle="tab" href="#text">Text</a>
                                          </li>
                                          <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#preview" data-markdown-tab="preview">Preview</a>
                                          </li>
                                        </ul>
                                        <div class="tab-content">
                                          <div class="tab-pane active" id="text">
                                            <div class="row">
                                              <div class="col-lg-12">
                                                <textarea id="answer-markdown" name="answer" class="form-control"
                                                          rows="7" placeholder="<?= Translation::get('ad_entry_content') ?>"
                                                ><?= $faqData['content'] ?></textarea>
                                              </div>
                                            </div>
                                          </div>
                                          <div class="tab-pane" id="preview">
                                            <article id="markdown-preview"></article>
                                          </div>
                                        </div>
                                      </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="tab-pane" id="tab-meta-data">
                                <!-- Categories -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="phpmyfaq-categories">
                                        <?= Translation::get('ad_entry_category') ?>
                                    </label>
                                    <div class="col-lg-10">
                                        <select name="rubrik[]" id="phpmyfaq-categories" size="5" multiple
                                                class="form-control">
                                            <?= $categoryHelper->renderOptions($categories) ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Language -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="lang">
                                        <?= Translation::get('ad_entry_locale') ?>:
                                    </label>
                                    <div class="col-lg-10">
                                        <?= LanguageHelper::renderSelectLanguage($faqData['lang'], false, [], 'lang') ?>
                                    </div>
                                </div>

                                <!-- Attachments -->
                                <?php if ($user->perm->hasPermission($currentUserId, 'addattachment')) : ?>
                                    <div class="row mb-2">
                                        <label class="col-lg-2 col-form-label">
                                            <?= Translation::get('ad_menu_attachments') ?>:
                                        </label>
                                        <div class="col-lg-10">
                                            <ul class="list-unstyled adminAttachments">
                                                <?php
                                                $attList = AttachmentFactory::fetchByRecordId(
                                                    $faqConfig,
                                                    $faqData['id']
                                                );
                                                foreach ($attList as $att) {
                                                    printf(
                                                        '<li><a href="../%s">%s</a> ',
                                                        $att->buildUrl(),
                                                        Strings::htmlentities($att->getFilename())
                                                    );
                                                    if ($user->perm->hasPermission($currentUserId, 'delattachment')) {
                                                        printf(
                                                            '<a class="badge bg-danger" href="?action=delatt&amp;record_id=%d&amp;id=%d&amp;lang=%s"><i aria-hidden="true" class="fa fa-trash"></i></a>',
                                                            $faqData['id'],
                                                            $att->getId(),
                                                            $faqData['lang']
                                                        );
                                                    }
                                                    echo "</li>\n";
                                                }
                                                ?>
                                            </ul>
                                            <?php
                                            printf(
                                                '<button type="button" class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#attachmentModal">%s</button>',
                                                Translation::get('ad_att_add')
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Tags -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="tags">
                                        <?= Translation::get('ad_entry_tags') ?>:
                                    </label>
                                    <div class="col-lg-10">
                                        <input type="text" name="tags" id="tags" value="<?= $faqData['tags'] ?>"
                                               autocomplete="off"
                                               class="form-control pmf-tags-autocomplete">
                                        <small id="tagsHelp"
                                               class="form-text visually-hidden"><?= Translation::get('msgShowHelp') ?></small>
                                    </div>
                                </div>

                                <!-- Keywords -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="keywords">
                                        <?= Translation::get('ad_entry_keywords') ?>:
                                    </label>
                                    <div class="col-lg-10">
                                        <input type="text" name="keywords" id="keywords" maxlength="255"
                                               class="form-control" autocomplete="off"
                                               value="<?= $faqData['keywords'] ?>">
                                        <small id="keywordsHelp"
                                               class="form-text visually-hidden"><?= Translation::get('msgShowHelp') ?></small>
                                    </div>
                                </div>

                                <!-- Author -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="author">
                                        <?= Translation::get('ad_entry_author') ?>
                                    </label>
                                    <div class="col-lg-10">
                                        <input type="text" name="author" id="author" value="<?= $faqData['author'] ?>"
                                               class="form-control">
                                    </div>
                                </div>

                                <!-- E-Mail -->
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="email">
                                        <?= Translation::get('ad_entry_email') ?>
                                    </label>
                                    <div class="col-lg-10">
                                        <input type="email" name="email" id="email" value="<?= $faqData['email'] ?>"
                                               class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="tab-permissions">
                                <!-- Permissions -->
                                <?php if ($faqConfig->get('security.permLevel') !== 'basic') : ?>
                                    <fieldset class="form-group">
                                        <div class="row">
                                            <legend class="col-lg-2 col-form-label pt-0">
                                              <?= Translation::get('ad_entry_grouppermission') ?>
                                            </legend>
                                            <div class="col-lg-10">
                                              <div class="form-check">
                                                <input type="radio" id="allgroups" name="grouppermission"
                                                       value="all" class="form-check-input"
                                                  <?php echo($allGroups ? 'checked' : ''); ?>>
                                                <label class="form-check-label" for="allgroups">
                                                  <?= Translation::get('ad_entry_all_groups') ?>
                                                </label>
                                              </div>
                                              <div class="form-check">
                                                <input type="radio" id="restrictedgroups" name="grouppermission"
                                                       class="form-check-input"
                                                       value="restricted" <?php echo($restrictedGroups ? 'checked' : ''); ?>>
                                                <label for="selected-groups" class="form-check-label"
                                                       for="restrictedgroups">
                                                  <?= Translation::get('ad_entry_restricted_groups') ?>
                                                </label>
                                                <select id="selected-groups" name="restricted_groups[]" size="3"
                                                        class="form-control" multiple>
                                                    <?= $user->perm->getAllGroupsOptions($groupPermission, $user) ?>
                                                </select>
                                              </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                <?php else : ?>
                                    <input type="hidden" name="grouppermission" value="all">
                                <?php endif; ?>

                                <fieldset class="form-group">
                                    <div class="row">
                                        <legend class="col-lg-2 col-form-label pt-0">
                                            <?= Translation::get('ad_entry_userpermission') ?>
                                        </legend>
                                        <div class="col-lg-10">
                                            <div class="form-check">
                                                <input type="radio" id="allusers" name="userpermission" value="all"
                                                       class="form-check-input"
                                                       <?= $allUsers ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="allusers">
                                                    <?= Translation::get('ad_entry_all_users') ?>
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input type="radio" id="restrictedusers" name="userpermission"
                                                       class="form-check-input"
                                                       value="restricted" <?= $restrictedUsers ? 'checked' : '' ?>>
                                                <label class="form-check-label" for="restrictedusers">
                                                    <?= Translation::get('ad_entry_restricted_users') ?>
                                                </label>
                                                <select name="restricted_users" id="selected-user" class="form-select">
                                                    <?= $userHelper->getAllUserOptions($userPermission[0], false) ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>

                            </div>

                            <div class="tab-pane" id="tab-notes-changelog">
                                <h6 class="card-title sr-only">
                                    <?= Translation::get('ad_entry_changelog') ?>
                                </h6>
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="changelog-date">
                                        <?= Translation::get('ad_entry_date') ?>
                                    </label>
                                    <div class="col-lg-10">
                                        <input type="text" readonly class="form-control-plaintext" id="changelog-date"
                                               value="<?= $faqData['date'] ?>">
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <label class="col-lg-2 col-form-label" for="changed">
                                        <?= Translation::get('ad_entry_changed') ?>
                                    </label>
                                    <div class="col-lg-10">
                                        <textarea name="changed" id="changed" rows="3" class="form-control"
                                        ><?= $faqData['changed'] ?></textarea>
                                    </div>
                                </div>

                                <h6 class="card-title">
                                    <label for="notes">
                                        <?php printf(Translation::get('ad_admin_notes_hint'), Translation::get('ad_admin_notes')) ?>
                                    </label>
                                </h6>
                                <div class="row mb-2">
                                    <div class="col-lg-10 offset-lg-2">
                                        <textarea id="notes" name="notes" class="form-control" rows="3"
                                        ><?= $faqData['notes'] ?? '' ?></textarea>
                                    </div>
                                </div>

                                <div class="row mb-2">
                                    <div class="col-lg-2">
                                        <h6 class="card-title">
                                            <?= Translation::get('ad_entry_changelog_history') ?>
                                        </h6>
                                    </div>
                                    <div class="col-lg-10">
                                        <ul>
                                            <?php foreach ($changelog->getByFaqId($faqData['id']) as $entry) {
                                                $entryUser = new User($faqConfig);
                                                $entryUser->getUserById($entry['user']);
                                                ?>
                                                <li class="small pt-0">
                                                    <?php printf(
                                                        '<i class="fa fa-hand-o-right"></i> %s  1.%d <i class="fa fa-calendar"></i> %s <i class="fa fa-user"></i> %s',
                                                        Translation::get('ad_entry_revision'),
                                                        $entry['revision_id'],
                                                        $date->format(date('Y-m-d H:i', $entry['date'])),
                                                        $entryUser->getUserData('display_name')
                                                    );
                                                    ?>
                                                    <br>
                                                    <?= Strings::htmlentities($entry['changelog'] ?? '') ?>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-3">
                <div id="accordion" role="tablist">
                    <div class="card mb-4">
                        <div class="card-header text-center" role="tab" id="pmf-heading-date">
                            <?php if ($selectedRevisionId === $faqData['revision_id']) : ?>
                                <button class="btn btn-lg btn-info" type="reset">
                                    <?= Translation::get('ad_gen_reset') ?>
                                </button>

                                <button class="btn btn-lg btn-primary" type="submit">
                                    <?= Translation::get('ad_entry_save') ?>
                                </button>

                            <?php endif ?>

                        </div>
                        <div class="card-body">
                            <h5 class="mb-0">
                                <?= Translation::get('ad_entry_date') ?>
                            </h5>
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="radio" id="updateDate" checked name="recordDateHandling"
                                           class="form-check-input"
                                           onchange="setRecordDate(this.id);">
                                    <label class="form-check-label" for="updateDate">
                                        <?= Translation::get('msgUpdateFaqDate') ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="keepDate" name="recordDateHandling" class="form-check-input"
                                           onchange="setRecordDate(this.id);">
                                    <label class="form-check-label" for="keepDate">
                                        <?= Translation::get('msgKeepFaqDate') ?>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input type="radio" id="manualDate" name="recordDateHandling"
                                           class="form-check-input"
                                           onchange="setRecordDate(this.id);">
                                    <label class="form-check-label" for="manualDate">
                                        <?= Translation::get('msgEditFaqDat') ?>
                                    </label>
                                </div>
                                <div id="recordDateInputContainer" class="invisible mb-2">
                                    <input type="datetime-local" name="date" id="date" class="form-control"
                                           placeholder="<?= $faqData['date'] ?>">
                                </div>
                            </div>
                            <h5 class="mb-0">
                                <?= Translation::get('ad_entry_status') ?>
                            </h5>
                            <div class="form-group">
                                <!-- active or not -->
                                <?php if ($user->perm->hasPermission($currentUserId, 'approverec')) :
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
                                    ?>
                                    <div class="form-check">
                                        <input type="radio" id="active" name="active" value="yes"
                                               class="form-check-input"
                                            <?php if (isset($isActive)) {
                                                echo $isActive;
                                            } ?>>
                                        <label class="form-check-label" for="active">
                                            <?= Translation::get('ad_entry_visibility') ?>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input type="radio" id="inactive" name="active" value="no"
                                               class="form-check-input"
                                            <?php if (isset($isInActive)) {
                                                echo $isInActive;
                                            } ?>>
                                        <label class="form-check-label" for="inactive">
                                          <?= Translation::get('ad_entry_not_visibility') ?>
                                        </label>
                                    </div>
                                <?php else : ?>
                                    <div class="form-check">
                                        <input type="radio" id="inactive" name="active" value="no"
                                               class="form-check-input" checked>
                                        <label class="form-check-label" for="inactive">
                                            <?= Translation::get('ad_entry_not_visibility') ?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($queryString != 'insertentry' && !$faqConfig->get('records.enableAutoRevisions')) : ?>
                              <h5 class="mb-0">
                                  <?= Translation::get('ad_entry_new_revision') ?>
                              </h5>
                              <div class="form-group">
                                <div class="form-check">
                                  <input type="radio" name="revision" id="revision" value="yes"
                                         class="form-check-input">
                                  <label class="form-check-label"
                                         for="revision"><?= Translation::get('ad_gen_yes') ?></label>
                                </div>
                                <div class="form-check">
                                  <input type="radio" name="revision" id="no-revision" value="no"
                                         checked class="form-check-input">
                                  <label class="form-check-label"
                                         for="no-revision"><?= Translation::get('ad_gen_no') ?></label>
                                </div>
                              </div>
                            <?php endif ?>

                            <div class="form-group">
                                <!-- sticky or not -->
                                <div class="form-check">
                                    <input type="checkbox" id="sticky" name="sticky" class="form-check-input"
                                        <?php echo(isset($faqData['sticky']) && $faqData['sticky'] ? 'checked' : '') ?>>
                                    <label class="form-check-label"
                                           for="sticky"><?= Translation::get('ad_entry_sticky') ?></label>
                                </div>

                                <!-- comments allowed or not -->
                                <div class="form-check">
                                    <input type="checkbox" name="comment" id="comment" value="y"
                                           class="form-check-input"
                                           <?= $faqData['comment'] ?>>
                                    <label class="form-check-label"
                                           for="comment"><?= Translation::get('ad_entry_allowComments') ?></label>
                                </div>
                            </div>

                            <div class="form-group">
                                <!-- solution id -->
                                <label class="col-form-label" for="solution_id">
                                    <?= Translation::get('ad_entry_solution_id') ?>:
                                </label>
                                <input type="number" name="solution_id" id="solution_id" size="5" class="form-control"
                                       readonly
                                       value="<?= $faqData['solution_id'] ?? $faq->getNextSolutionId() ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </form>

    <!-- Attachment Upload Dialog -->
    <?php
    if (0 === $faqData['id']) {
        $faqData['id'] = $faqConfig->getDb()->nextId(
            Database::getTablePrefix() . 'faqdata',
            'id'
        );
    }
    ?>
  <div class="modal fade" id="attachmentModal" tabindex="-1" role="dialog" aria-labelledby="attachmentModalLabel"
       aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="attachmentModalLabel">
              <?= Translation::get('ad_att_addto') . ' ' . Translation::get('ad_att_addto_2') ?>
            (max <?= Utils::formatBytes($faqConfig->get('records.maxAttachmentSize')) ?>)
          </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form action="api/attachment.php?action=upload" enctype="multipart/form-data" method="post"
                id="attachmentForm" novalidate>
            <fieldset>
              <input type="hidden" name="MAX_FILE_SIZE" value="<?= $faqConfig->get('records.maxAttachmentSize') ?>">
              <input type="hidden" name="record_id" id="attachment_record_id" value="<?= $faqData['id'] ?>">
              <input type="hidden" name="record_lang" id="attachment_record_lang" value="<?= $faqData['lang'] ?>">
              <input type="hidden" name="save" value="true">
              <?= Token::getInstance()->getTokenInput('upload-attachment') ?>

              <div class="mb-2">
                <label class="form-label" for="filesToUpload">
                  <?= Translation::get('ad_att_att') ?>
                </label>
                <input type="file" class="form-control" name="filesToUpload[]" id="filesToUpload" multiple>
                <div class="invalid-feedback">
                  The file is too big.
                </div>
              </div>

              <div class="pmf-attachment-upload-files invisible mb-2">
                <?= Translation::get('msgAttachmentsFilesize') ?>:
                <output id="filesize"></output>
              </div>
            </fieldset>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" id="pmf-attachment-modal-upload">
              <?= Translation::get('ad_att_butt') ?>
          </button>
        </div>
      </div>
    </div>
  </div>

    <script>
      function setRecordDate(how) {
        if ('updateDate' === how) {
          document.getElementById('date').value = '';
        } else if ('keepDate' === how) {
          document.getElementById('date').value = '<?= $faqData['isoDate'] ?>';
        } else if ('manualDate' === how) {
          document.getElementById('recordDateInputContainer').classList.remove('invisible');
          document.getElementById('date').value = '';
        }
      }
    </script>
    <?php
} elseif ($user->perm->hasPermission($currentUserId, 'edit_faq') && !Database::checkOnEmptyTable('faqcategories')) {
    echo Translation::get('err_NotAuth');
} elseif ($user->perm->hasPermission($currentUserId, 'edit_faq') && Database::checkOnEmptyTable('faqcategories')) {
    echo Translation::get('no_cats');
}
