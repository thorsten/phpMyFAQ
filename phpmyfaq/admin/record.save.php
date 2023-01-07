<?php

/**
 * Save an existing FAQ record.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-23
 */

use Elasticsearch\Common\Exceptions\Missing404Exception;
use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Changelog;
use phpMyFAQ\Faq\FaqPermission;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LinkVerifierHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Logging;
use phpMyFAQ\Revision;
use phpMyFAQ\Tags;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

$category = new Category($faqConfig, [], false);
$category->setUser($currentAdminUser);
$category->setGroups($currentAdminGroups);

if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
    // Get submit action
    $submit = Filter::filterInputArray(
        INPUT_POST,
        [
            'submit' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ],
        ]
    );

    // FAQ data
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_UNSAFE_RAW);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_UNSAFE_RAW);
    $question = Filter::filterInput(INPUT_POST, 'question', FILTER_UNSAFE_RAW);
    $categories = Filter::filterInputArray(
        INPUT_POST,
        [
            'rubrik' => [
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ],
        ]
    );
    $recordLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_UNSAFE_RAW);
    $tags = Filter::filterInput(INPUT_POST, 'tags', FILTER_UNSAFE_RAW);
    $active = 'yes' == Filter::filterInput(
        INPUT_POST,
        'active',
        FILTER_UNSAFE_RAW
    ) && $user->perm->hasPermission($user->getUserId(), 'approverec') ? 'yes' : 'no';
    $sticky = Filter::filterInput(INPUT_POST, 'sticky', FILTER_UNSAFE_RAW);
    $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_UNSAFE_RAW);
    $author = Filter::filterInput(INPUT_POST, 'author', FILTER_UNSAFE_RAW);
    $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_UNSAFE_RAW);
    $recordId = Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solutionId = Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision = Filter::filterInput(INPUT_POST, 'revision', FILTER_UNSAFE_RAW);
    $revisionId = Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed = Filter::filterInput(INPUT_POST, 'changed', FILTER_UNSAFE_RAW);
    $date = Filter::filterInput(INPUT_POST, 'date', FILTER_UNSAFE_RAW);
    $notes = Filter::filterInput(INPUT_POST, 'notes', FILTER_UNSAFE_RAW);

    // Permissions
    $faqPermission = new FaqPermission($faqConfig);
    $permissions = [];
    if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_UNSAFE_RAW)) {
        $permissions += [
            'restricted_user' => [
                -1,
            ],
        ];
    } else {
        $permissions += [
            'restricted_user' => [
                Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
            ],
        ];
    }

    if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_UNSAFE_RAW)) {
        $permissions += [
            'restricted_groups' => [
                -1,
            ],
        ];
    } else {
        $permissions += Filter::filterInputArray(
            INPUT_POST,
            [
                'restricted_groups' => [
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ],
            ]
        );
    }

    if (!is_null($question) && !is_null($categories)) {
        // Save entry
        $logging = new Logging($faqConfig);
        $logging->logAdmin($user, 'admin-save-existing-faq ' . $recordId);
        if ($active === 'yes') {
            $logging->logAdmin($user, 'admin-publish-existing-faq ' . $recordId);
        }

        printf(
            '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> %s</h2></div></header>',
            $PMF_LANG['ad_entry_aor']
        );

        $tagging = new Tags($faqConfig);

        if ('yes' === $revision || $faqConfig->get('records.enableAutoRevisions')) {
            $faqRevision = new Revision($faqConfig);
            $faqRevision->create($recordId, $recordLang);
            ++$revisionId;
        }

        $recordData = [
            'id' => $recordId,
            'lang' => $recordLang,
            'revision_id' => $revisionId,
            'active' => $active,
            'sticky' => (!is_null($sticky) ? 1 : 0),
            'thema' => Filter::removeAttributes(html_entity_decode($question, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'content' => Filter::removeAttributes(html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8')),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => empty($date) ? date('YmdHis') : str_replace(['-', ':', ' '], '', $date),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
            'notes' => Filter::removeAttributes($notes)
        ];

        // Create ChangeLog entry
        $changelog = new Changelog($faqConfig);
        $changelog->addEntry($recordId, $user->getUserId(), nl2br($changed), $recordLang, $revisionId);

        // Create the visit entry
        $visits = new Visits($faqConfig);
        $visits->logViews((int)$recordId);

        // save or update the FAQ record
        if ($faq->hasTranslation($recordId, $recordLang)) {
            $faq->updateRecord($recordData);
        } else {
            $recordId = $faq->addRecord($recordData, false);
        }

        if ($recordId) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);
            LinkVerifierHelper::linkOndemandJavascript($recordId, $recordLang);
        } else {
            printf(
                '<p class="alert alert-danger">%s</p>',
                $PMF_LANG['ad_entry_savedfail'] . $faqConfig->getDb()->error()
            );
        }

        if (!isset($categories['rubrik'])) {
            $categories['rubrik'] = [];
        }

        $categoryRelation = new CategoryRelation($faqConfig);
        $categoryRelation->deleteByFaq($recordId, $recordLang);
        $categoryRelation->add($categories['rubrik'], $recordId, $recordLang);

        // Insert the tags
        if ($tags != '') {
            $tagging->saveTags($recordId, explode(',', trim($tags)));
        } else {
            $tagging->deleteTagsFromRecordId($recordId);
        }

        // Add user permissions
        $faqPermission->delete(FaqPermission::USER, $recordId);
        $faqPermission->add(FaqPermission::USER, $recordId, $permissions['restricted_user']);
        // Add group permission
        if ($faqConfig->get('security.permLevel') !== 'basic') {
            $faqPermission->delete(FaqPermission::GROUP, $recordId);
            $faqPermission->add(FaqPermission::GROUP, $recordId, $permissions['restricted_groups']);
        }

        // If Elasticsearch is enabled, update active or delete inactive FAQ document
        if ($faqConfig->get('search.enableElasticsearch')) {
            $esInstance = new Elasticsearch($faqConfig);
            try {
                if ('yes' === $active) {
                    $esInstance->update(
                        [
                            'id' => $recordId,
                            'lang' => $recordLang,
                            'solution_id' => $solutionId,
                            'question' => $recordData['thema'],
                            'answer' => $recordData['content'],
                            'keywords' => $keywords,
                            'category_id' => $categories['rubrik'][0]
                        ]
                    );
                }
            } catch (Missing404Exception $e) {
                // @todo handle exception
            }
        }

        // All the other translations
        $languages = Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_UNSAFE_RAW);
        ?>
      <script>
        (() => {
          setTimeout(() => {
            window.location = "index.php?action=editentry&id=<?= $recordId ?>&lang=<?= $recordData['lang'] ?>";
          }, 5000);
        })();
      </script>
        <?php
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
