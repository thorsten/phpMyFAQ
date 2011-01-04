<?php
/**
 * Save an existing FAQ record.
 * 
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user     = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));
$category = new PMF_Category($current_admin_user, $current_admin_groups, false);    

if ($permission['editbt']) {
    
    // Get submit action
    $submit        = PMF_Filter::filterInputArray(INPUT_POST, array('submit' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'artlang', FILTER_SANITIZE_STRING);
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
    
    if (isset($submit['submit'][1]) && !is_null($question) && !is_null($categories)) {
        // Save entry
        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Beitragsave ' . $record_id);
        print "<h2>".$PMF_LANG["ad_entry_aor"]."</h2>\n";

        $tagging = new PMF_Tags();
        
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

        // save or update the FAQ record
        if ($faq->isAlreadyTranslated($record_id, $record_lang)) {
            $faq->updateRecord($recordData);
        } else {
            $record_id = $faq->addRecord($recordData, false);
        }

        if ($record_id) {
            print $PMF_LANG['ad_entry_savedsuc'];
            link_ondemand_javascript($record_id, $record_lang);
        } else {
            print $PMF_LANG['ad_entry_savedfail'].$db->error();
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
        if ($faqconfig->get('main.permLevel') != 'basic') {
            $faq->deletePermission('group', $record_id);
            $faq->addPermission('group', $record_id, $restricted_groups);
            $category->deletePermission('group', $categories['rubrik']);
            $category->addPermission('group', $categories['rubrik'], $restricted_groups);
        }

        // All the other translations        
        $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);            
        if ($faqconfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {
            $linkverifier = new PMF_Linkverifier($user->getLogin());
    
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);
            $languages = explode(",", $languages);
            foreach ($languages as $translated_lang) {
                if ($translated_lang == $record_lang) {
                    continue;
                }
                $translated_question = PMF_Filter::filterInput(INPUT_POST, 'thema_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
                $translated_content  = PMF_Filter::filterInput(INPUT_POST, 'content_translated_' . $translated_lang, FILTER_SANITIZE_SPECIAL_CHARS);
                $translated_keywords = PMF_Filter::filterInput(INPUT_POST, 'keywords_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
    
                $recordData = array_merge($recordData, array(
                    'lang'          => $translated_lang,
                    'thema'         => utf8_encode(html_entity_decode($translated_question)),
                    'content'       => utf8_encode(html_entity_decode($translated_content)),
                    'keywords'      => utf8_encode($translated_keywords),
                    'author'        => 'Google',
                    'email'         => $faqconfig->get('main.administrationMail')));
    
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
            }
        }
?>
    <script type="text/javascript">
    <!--
    $(document).ready(function(){
        setTimeout(function() {
            window.location = "index.php?action=view";
            }, 5000);
        });
    //-->
    </script>
<?php
    } elseif (isset($submit['submit'][0])) {

        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Beitragdel, ' . $record_id);

        $path = PMF_ROOT_DIR . DIRECTORY_SEPARATOR . PMF_ATTACHMENTS_DIR . DIRECTORY_SEPARATOR . $record_id . '/';
        if (@is_dir($path)) {
            $do = dir($path);
            while ($dat = $do->read()) {
                if ($dat != "." && $dat != "..") {
                    unlink($path . $dat);
                }
            }
            rmdir($path);
        }
        
        $faq->deleteRecord($record_id, $record_lang);
        print $PMF_LANG['ad_entry_delsuc'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}