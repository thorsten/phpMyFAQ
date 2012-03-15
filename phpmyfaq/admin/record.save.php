<?php
/**
 * Save an existing FAQ record.
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user     = PMF_User_CurrentUser::getFromSession($faqConfig);
$category = new PMF_Category($faqConfig, false);
$category->setUser($current_admin_user);
$category->setGroups($current_admin_groups);

if ($permission['editbt']) {

    // Get submit action
    $submit = PMF_Filter::filterInputArray(
        INPUT_POST,
        array(
            'submit' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags'  => FILTER_REQUIRE_ARRAY
            )
        )
    );

    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(
        INPUT_POST,
        array(
            'rubrik' => array(
                'filter' => FILTER_VALIDATE_INT,
                'flags'  => FILTER_REQUIRE_ARRAY
            )
        )
    );
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags          = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active        = 'yes' == PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING) && $permission['approverec'] ? 'yes' : 'no';
    $sticky        = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content       = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords      = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author        = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment       = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $record_id     = PMF_Filter::filterInput(INPUT_POST, 'record_id', FILTER_VALIDATE_INT);
    $solution_id   = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision      = PMF_Filter::filterInput(INPUT_POST, 'revision', FILTER_SANITIZE_STRING);
    $revision_id   = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed       = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    $date          = PMF_Filter::filterInput(INPUT_POST, 'date', FILTER_SANITIZE_STRING);
    
    // Permissions
    $user_permission   = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restricted_users  = ('all' == $user_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
    $group_permission  = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restricted_groups = ('all' == $group_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
    
    if (!is_null($question) && !is_null($categories)) {
        // Save entry
        $logging = new PMF_Logging($faqConfig);
        $logging->logAdmin($user, 'Beitragsave ' . $record_id);
        print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";

        $tagging = new PMF_Tags($faqConfig);
        
        if ('yes' == $revision) {
            // Add current version into revision table
            $faq->addNewRevision($record_id, $record_lang);
            $revision_id++;
        }

        $recordData = array(
            'id'            => $record_id,
            'lang'          => $record_lang,
            'revision_id'   => $revision_id,
            'active'        => $active,
            'sticky'        => (!is_null($sticky) ? 1 : 0),
            'thema'         => html_entity_decode($question),
            'content'       => html_entity_decode($content),
            'keywords'      => $keywords,
            'author'        => $author,
            'email'         => $email,
            'comment'       => (!is_null($comment) ? 'y' : 'n'),
            'date'          => empty($date) ? date('YmdHis') : str_replace(array('-', ':', ' '), '', $date),
            'dateStart'     => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd'       => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState'     => '',
            'linkDateCheck' => 0);

        // Create ChangeLog entry
        $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $record_lang, $revision_id);

        // Create the visit entry
        PMF_Visits::getInstance($db, $Language)->add($record_id);

        // save or update the FAQ record
        if ($faq->isAlreadyTranslated($record_id, $record_lang)) {
            $faq->updateRecord($recordData);
        } else {
            $record_id = $faq->addRecord($recordData, false);
        }

        if ($record_id) {
            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);
            link_ondemand_javascript($record_id, $record_lang);
        } else {
            printf('<p class="alert alert-error">%s</p>', print $PMF_LANG['ad_entry_savedfail'] . $db->error());
        }
        
        if (!isset($categories['rubrik'])) {
            $categories['rubrik'] = array();
        }
        
        // delete category relations
        $faq->deleteCategoryRelations($record_id, $record_lang);
        // save or update the category relations
        $faq->addCategoryRelations($categories['rubrik'], $record_id, $record_lang);

        // Insert the tags
        if ($tags != '') {
            $tagging->saveTags($record_id, explode(',', $tags));
        } else {
            $tagging->deleteTagsFromRecordId($record_id);
        }

        // Add user permissions
        $faq->deletePermission('user', $record_id);
        $faq->addPermission('user', $record_id, $restricted_users);
        $category->deletePermission('user', $categories['rubrik']);
        $category->addPermission('user', $categories['rubrik'], $restricted_users);
        // Add group permission
        if ($faqConfig->get('security.permLevel') != 'basic') {
            $faq->deletePermission('group', $record_id);
            $faq->addPermission('group', $record_id, $restricted_groups);
            $category->deletePermission('group', $categories['rubrik']);
            $category->addPermission('group', $categories['rubrik'], $restricted_groups);
        }

        // All the other translations        
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);            
        if ($faqConfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {
            
            $linkverifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
            $visits       = PMF_Visits::getInstance($db, $Language);
    
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            $languages = explode(",", $languages);
            foreach ($languages as $translated_lang) {
                if ($translated_lang == $record_lang) {
                    continue;
                }
                $translated_question = PMF_Filter::filterInput(INPUT_POST, 'question_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
                $translated_answer   = PMF_Filter::filterInput(INPUT_POST, 'answer_translated_' . $translated_lang, FILTER_SANITIZE_SPECIAL_CHARS);
                $translated_keywords = PMF_Filter::filterInput(INPUT_POST, 'keywords_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
    
                $recordData = array_merge($recordData, array(
                    'lang'          => $translated_lang,
                    'thema'         => utf8_encode(html_entity_decode($translated_question)),
                    'content'       => utf8_encode(html_entity_decode($translated_answer)),
                    'keywords'      => utf8_encode($translated_keywords),
                    'author'        => 'Google Translate',
                    'email'         => $faqConfig->get('main.administrationMail')));
    
                // Create ChangeLog entry
                $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $translated_lang, $revision_id);
    
                // save or update the FAQ record
                if ($faq->isAlreadyTranslated($record_id, $translated_lang)) {
                    $faq->updateRecord($recordData);
                } else {
                    $faq->addRecord($recordData, false);
                }
    
                // delete category relations
                $faq->deleteCategoryRelations($record_id, $translated_lang);
    
                // save or update the category relations
                $faq->addCategoryRelations($categories['rubrik'], $record_id, $translated_lang);
    
                // Copy Link Verification
                $linkverifier->markEntry($record_id, $translated_lang);

                // add faqvisit entry
                $visits->add($record_id, $translated_lang);

                // Set attachment relations
                $attachments = PMF_Attachment_Factory::fetchByRecordId($record_id);
                foreach ($attachments as $attachment) {
                    if ($attachment instanceof PMF_Attachment_Abstract) {
                        $attachment->setId(null);
                        $attachment->setRecordLang($translated_lang);
                        $attachment->saveMeta();
                    }
                }
            }
        }
?>
    <script type="text/javascript">
    $(document).ready(function(){
        setTimeout(function() {
            window.location = "index.php?action=editentry&id=<?php print $record_id; ?>&lang=<?php print $recordData['lang'] ?>";
            }, 5000);
        });
    </script>
<?php
    }

    // Clear the article cache and the related stuff
    PMF_Cache::getInstance()->clearArticle($record_id);
} else {
    print $PMF_LANG['err_NotAuth'];
}
