<?php

/**
 * Save an existing FAQ record.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-23
 */

use Elasticsearch\Common\Exceptions\Missing404Exception;
use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\LinkverifierHelper;
use phpMyFAQ\Instance\Elasticsearch;
use phpMyFAQ\Logging;
use phpMyFAQ\Tags;
use phpMyFAQ\Visits;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$category = new Category($faqConfig, [], false);
$category->setUser($currentAdminUser);
$category->setGroups($currentAdminGroups);

if ($user->perm->checkRight($user->getUserId(), 'editbt')) {

    // Get submit action
    $submit = Filter::filterInputArray(
        INPUT_POST,
        array(
            'submit' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ),
        )
    );

    // FAQ data
    $dateStart = Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question = Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories = Filter::filterInputArray(
        INPUT_POST,
        array(
            'rubrik' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ),
        )
    );
    $recordLang = Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags = Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active = 'yes' == Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING) && $user->perm->checkRight($user->getUserId(), 'approverec') ? 'yes' : 'no';
    $sticky = Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    if ($faqConfig->get('main.enableMarkdownEditor')) {
        $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_UNSAFE_RAW);
    } else {
        $content = Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    }
    $keywords = Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author = Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email = Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $recordId = Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solutionId = Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision = Filter::filterInput(INPUT_POST, 'revision', FILTER_SANITIZE_STRING);
    $revisionId = Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed = Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    $date = Filter::filterInput(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $notes = Filter::filterInput(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Permissions
    $permissions = [];
    if ('all' === Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_user' => array(
                -1,
            ),
        );
    } else {
        $permissions += array(
            'restricted_user' => array(
                Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
            ),
        );
    }

    if ('all' === Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_groups' => array(
                -1,
            ),
        );
    } else {
        $permissions += Filter::filterInputArray(
            INPUT_POST,
            array(
                'restricted_groups' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags' => FILTER_REQUIRE_ARRAY,
                ),
            )
        );
    }

    if (!is_null($question) && !is_null($categories)) {
        // Save entry
        $logging = new Logging($faqConfig);
        $logging->logAdmin($user, 'Beitragsave '.$recordId);

        printf(
            '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fas fa-pencil"></i> %s</h2></div></header>',
            $PMF_LANG['ad_entry_aor']
        );

        $tagging = new Tags($faqConfig);

        if ('yes' === $revision || $faqConfig->get('records.enableAutoRevisions')) {
            // Add current version into revision table
            $faq->addNewRevision($recordId, $recordLang);
            ++$revisionId;
        }

        $recordData = array(
            'id' => $recordId,
            'lang' => $recordLang,
            'revision_id' => $revisionId,
            'active' => $active,
            'sticky' => (!is_null($sticky) ? 1 : 0),
            'thema' => Filter::removeAttributes(html_entity_decode($question)),
            'content' => Filter::removeAttributes(html_entity_decode($content)),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => empty($date) ? date('YmdHis') : str_replace(array('-', ':', ' '), '', $date),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart).'000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd).'235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
            'notes' => Filter::removeAttributes($notes)
        );

        // Create ChangeLog entry
        $faq->createChangeEntry($recordId, $user->getUserId(), nl2br($changed), $recordLang, $revisionId);

        // Create the visit entry
        $visits = new Visits($faqConfig);
        $visits->logViews($recordId);

        // save or update the FAQ record
        if ($faq->isAlreadyTranslated($recordId, $recordLang)) {
            $faq->updateRecord($recordData);
        } else {
            $recordId = $faq->addRecord($recordData, false);
        }

        if ($recordId) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);
            LinkverifierHelper::linkOndemandJavascript($recordId, $recordLang);
        } else {
            printf(
                '<p class="alert alert-danger">%s</p>',
                $PMF_LANG['ad_entry_savedfail'].$faqConfig->getDb()->error()
            );
        }

        if (!isset($categories['rubrik'])) {
            $categories['rubrik'] = [];
        }

        // delete category relations
        $faq->deleteCategoryRelations($recordId, $recordLang);
        // save or update the category relations
        $faq->addCategoryRelations($categories['rubrik'], $recordId, $recordLang);

        // Insert the tags
        if ($tags != '') {
            $tagging->saveTags($recordId, explode(',', trim($tags)));
        } else {
            $tagging->deleteTagsFromRecordId($recordId);
        }

        // Add user permissions
        $faq->deletePermission('user', $recordId);
        $faq->addPermission('user', $recordId, $permissions['restricted_user']);
        // Add group permission
        if ($faqConfig->get('security.permLevel') != 'basic') {
            $faq->deletePermission('group', $recordId);
            $faq->addPermission('group', $recordId, $permissions['restricted_groups']);
        }

        // If Elasticsearch is enabled, update active or delete inactive FAQ document
        if ($faqConfig->get('search.enableElasticsearch')) {
            $esInstance = new Elasticsearch($faqConfig);
            try {
                $esInstance->delete($solutionId);
            } catch (Missing404Exception $e) {

            }
            if ('yes' === $active) {
                $esInstance->index(
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
        }

        // All the other translations        
        $languages = Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
        ?>
    <script>
        (function() {
            setTimeout(function() {
                window.location = "index.php?action=editentry&id=<?= $recordId ?>&lang=<?= $recordData['lang'] ?>";
            }, 5000);
        })();
    </script>
<?php

    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
