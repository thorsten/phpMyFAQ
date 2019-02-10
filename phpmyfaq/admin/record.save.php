<?php

/**
 * Save an existing FAQ record.
 *
 * PHP Version 5.5
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

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

$category = new PMF_Category($faqConfig, [], false);
$category->setUser($currentAdminUser);
$category->setGroups($currentAdminGroups);

if ($user->perm->checkRight($user->getUserId(), 'editbt')) {

    // Get submit action
    $submit = PMF_Filter::filterInputArray(
        INPUT_POST,
        array(
            'submit' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ),
        )
    );

    // FAQ data
    $dateStart = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories = PMF_Filter::filterInputArray(
        INPUT_POST,
        array(
            'rubrik' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags' => FILTER_REQUIRE_ARRAY,
            ),
        )
    );
    $recordLang = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active = 'yes' == PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING) && $user->perm->checkRight($user->getUserId(), 'approverec') ? 'yes' : 'no';
    $sticky = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    if ($faqConfig->get('main.enableMarkdownEditor')) {
        $content = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_UNSAFE_RAW);
    } else {
        $content = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    }
    $keywords = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $recordId = PMF_Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solutionId = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision = PMF_Filter::filterInput(INPUT_POST, 'revision', FILTER_SANITIZE_STRING);
    $revisionId = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    $date = PMF_Filter::filterInput(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    $notes = PMF_Filter::filterInput(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

    // Permissions
    $permissions = [];
    if ('all' === PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_user' => array(
                -1,
            ),
        );
    } else {
        $permissions += array(
            'restricted_user' => array(
                PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT),
            ),
        );
    }

    if ('all' === PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING)) {
        $permissions += array(
            'restricted_groups' => array(
                -1,
            ),
        );
    } else {
        $permissions += PMF_Filter::filterInputArray(
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
        $logging = new PMF_Logging($faqConfig);
        $logging->logAdmin($user, 'Beitragsave '.$recordId);

        printf(
            '<header class="row"><div class="col-lg-12"><h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> %s</h2></div></header>',
            $PMF_LANG['ad_entry_aor']
        );

        $tagging = new PMF_Tags($faqConfig);

        if ('yes' == $revision) {
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
            'thema' => PMF_Filter::removeAttributes(html_entity_decode($question)),
            'content' => PMF_Filter::removeAttributes(html_entity_decode($content)),
            'keywords' => $keywords,
            'author' => $author,
            'email' => $email,
            'comment' => (!is_null($comment) ? 'y' : 'n'),
            'date' => empty($date) ? date('YmdHis') : str_replace(array('-', ':', ' '), '', $date),
            'dateStart' => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart).'000000'),
            'dateEnd' => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd).'235959'),
            'linkState' => '',
            'linkDateCheck' => 0,
            'notes' => PMF_Filter::removeAttributes($notes)
        );

        // Create ChangeLog entry
        $faq->createChangeEntry($recordId, $user->getUserId(), nl2br($changed), $recordLang, $revisionId);

        // Create the visit entry
        $visits = new PMF_Visits($faqConfig);
        $visits->logViews($recordId);

        // save or update the FAQ record
        if ($faq->isAlreadyTranslated($recordId, $recordLang)) {
            $faq->updateRecord($recordData);
        } else {
            $recordId = $faq->addRecord($recordData, false);
        }

        if ($recordId) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);
            PMF_Helper_Linkverifier::linkOndemandJavascript($recordId, $recordLang);
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
            $esInstance = new PMF_Instance_Elasticsearch($faqConfig);
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
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
        ?>
    <script>
        (function() {
            setTimeout(function() {
                window.location = "index.php?action=editentry&id=<?php echo $recordId ?>&lang=<?php echo $recordData['lang'] ?>";
            }, 5000);
        })();
    </script>
<?php

    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
