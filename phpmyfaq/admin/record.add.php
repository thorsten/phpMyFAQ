<?php
/**
 * Adds a record in the database, handles the preview and checks for missing
 * category entries.
 *
 * PHP Version 5.3
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
$user = PMF_User_CurrentUser::getFromSession($faqConfig);

if ($permission['editbt']|| $permission['addbt']) {

    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'question', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
    $tags          = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active        = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky        = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content       = PMF_Filter::filterInput(INPUT_POST, 'answer', FILTER_SANITIZE_SPECIAL_CHARS);
    $keywords      = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
    $author        = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
    $email         = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $comment       = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
    $record_id     = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $solution_id   = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
    $revision_id   = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT);
    $changed       = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
    
    // Permissions
    $user_permission   = PMF_Filter::filterInput(INPUT_POST, 'userpermission', FILTER_SANITIZE_STRING);
    $restricted_users  = ('all' == $user_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_users', FILTER_VALIDATE_INT);
    $group_permission  = PMF_Filter::filterInput(INPUT_POST, 'grouppermission', FILTER_SANITIZE_STRING);
    $restricted_groups = ('all' == $group_permission) ? -1 : PMF_Filter::filterInput(INPUT_POST, 'restricted_groups', FILTER_VALIDATE_INT);
    
    if (!isset($categories['rubrik'])) {
        $categories['rubrik'] = array();
    }
    
    if (!is_null($question) && !is_null($categories['rubrik'])) {
        // new entry
        $logging = new PMF_Logging($faqConfig);
        $logging->logAdmin($user, 'Beitragcreatesave');
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);

        $category = new PMF_Category($faqConfig, array(), false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $tagging  = new PMF_Tags($faqConfig);

        $recordData     = array(
            'lang'          => $record_lang,
            'active'        => $active,
            'sticky'        => (!is_null($sticky) ? 1 : 0),
            'thema'         => html_entity_decode($question),
            'content'       => html_entity_decode($content),
            'keywords'      => $keywords,
            'author'        => $author,
            'email'         => $email,
            'comment'       => (!is_null($comment) ? 'y' : 'n'),
            'date'          => date('YmdHis'),
            'dateStart'     => (empty($dateStart) ? '00000000000000' : str_replace('-', '', $dateStart) . '000000'),
            'dateEnd'       => (empty($dateEnd) ? '99991231235959' : str_replace('-', '', $dateEnd) . '235959'),
            'linkState'     => '',
            'linkDateCheck' => 0);
        
        // Add new record and get that ID
        $record_id = $faq->addRecord($recordData);

        if ($record_id) {
            // Create ChangeLog entry
            $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $recordData['lang']);
            // Create the visit entry

            $visits = new PMF_Visits($faqConfig);
            $visits->add($record_id);

            // Insert the new category relations
            $faq->addCategoryRelations($categories['rubrik'], $record_id, $recordData['lang']);
            // Insert the tags
            if ($tags != '') {
                $tagging->saveTags($record_id, explode(',',$tags));
            }
            
            // Add user permissions
            $faq->addPermission('user', $record_id, $restricted_users);
            $category->addPermission('user', $categories['rubrik'], $restricted_users);
            // Add group permission
            if ($faqConfig->get('security.permLevel') != 'basic') {
                $faq->addPermission('group', $record_id, $restricted_groups);
                $category->addPermission('group', $categories['rubrik'], $restricted_groups);
            }

            printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_savedsuc']);

            // Open question answered
            $openQuestionId = PMF_Filter::filterInput(INPUT_POST, 'openQuestionId', FILTER_VALIDATE_INT);
            if (null !== $openQuestionId) {

                if ($faqConfig->get('records.enableDeleteQuestion')) { // deletes question
                    $faq->deleteQuestion($openQuestionId);
                } else { // adds this faq record id to the related open question
                    $faq->updateQuestionAnswer($openQuestionId, $record_id, $categories['rubrik'][0]);
                }

                $url   = sprintf(
                    '%s?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $faqConfig->get('main.referenceURL'),
                    $categories['rubrik'][0],
                    $record_id,
                    $record_lang
                );
                $oLink = new PMF_Link($url, $faqConfig);

                // notify the user who added the question
                $notifyEmail = PMF_Filter::filterInput(INPUT_POST, 'notifyEmail', FILTER_SANITIZE_EMAIL);
                $notifyUser  = PMF_Filter::filterInput(INPUT_POST, 'notifyUser', FILTER_SANITIZE_STRING);

                $notification = new PMF_Notification($faqConfig);
                $notification->sendOpenQuestionAnswered($notifyEmail, $notifyUser, $oLink->toString());
            }

            // Call Link Verification
            PMF_Helper_Linkverifier::linkOndemandJavascript($record_id, $recordData['lang']);

            // Callback to Twitter if enabled
            if ($faqConfig->get('socialnetworks.enableTwitterSupport')) {
                require '../inc/libs/twitteroauth/twitteroauth.php';
                $connection = new TwitterOAuth($faqConfig->get('socialnetworks.twitterConsumerKey'),
                                               $faqConfig->get('socialnetworks.twitterConsumerSecret'),
                                               $faqConfig->get('socialnetworks.twitterAccessTokenKey'),
                                               $faqConfig->get('socialnetworks.twitterAccessTokenSecret'));

                $link = PMF_Link::getSystemRelativeUri() .
                        sprintf('?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $category,
                            $record_id,
                            $record_lang);
                $link             = $faqConfig->get('main.referenceURL') . str_replace('/admin/','/', $link);
                $oLink            = new PMF_Link($link, $faqConfig);
                $oLink->itemTitle = $question;
                $link             = $oLink->toString();
                
                if ($connection) {
                    $twitter = new PMF_Services_Twitter($connection);
                    $twitter->addPost($question, $tags, $link);
                }
            }

            // All the other translations
            $languages = PMF_Filter::filterInput(INPUT_POST, 'used_translated_languages', FILTER_SANITIZE_STRING);            
            if ($faqConfig->get('main.enableGoogleTranslation') === true && !empty($languages)) {
                
                $linkverifier = new PMF_Linkverifier($faqConfig, $user->getLogin());
    
                $languages = explode(",", $languages);
                foreach ($languages as $translated_lang) {
                    if ($translated_lang == $record_lang) {
                        continue;
                    }
                    $translated_question = PMF_Filter::filterInput(INPUT_POST, 'question_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
                    $translated_answer   = PMF_Filter::filterInput(INPUT_POST, 'answer_translated_' . $translated_lang, FILTER_SANITIZE_SPECIAL_CHARS);
                    $translated_keywords = PMF_Filter::filterInput(INPUT_POST, 'keywords_translated_' . $translated_lang, FILTER_SANITIZE_STRING);
    
                    $recordData = array_merge($recordData, array(
                        'id'            => $record_id,
                        'lang'          => $translated_lang,
                        'thema'         => html_entity_decode($translated_question),
                        'content'       => html_entity_decode($translated_answer),
                        'keywords'      => $translated_keywords,
                        'author'        => 'Google Translate',
                        'email'         => $faqConfig->get('main.administrationMail')));
    
                    // Create ChangeLog entry
                    $faq->createChangeEntry($record_id, $user->getUserId(), nl2br($changed), $translated_lang);
    
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
                    $attachments = PMF_Attachment_Factory::fetchByRecordId($faqConfig, $record_id);
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
        } else {
            printf(
                '<p class="alert alert-error">%s</p>',
                $PMF_LANG['ad_entry_savedfail'] . $faqConfig->getDb()->error()
            );
        }

    } else {
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
        printf('<p class="alert alert-error">%s</p>', $PMF_LANG['ad_entryins_fail']);
?>
    <form action="?action=editpreview" method="post">
    <input type="hidden" name="question"            value="<?php print PMF_String::htmlspecialchars($question); ?>" />
    <input type="hidden" name="content" class="mceNoEditor" value="<?php print PMF_String::htmlspecialchars($content); ?>" />
    <input type="hidden" name="lang"                value="<?php print $record_lang; ?>" />
    <input type="hidden" name="keywords"            value="<?php print $keywords; ?>" />
    <input type="hidden" name="tags"                value="<?php print $tags; ?>" />
    <input type="hidden" name="author"              value="<?php print $author; ?>" />
    <input type="hidden" name="email"               value="<?php print $email; ?>" />
<?php
        if (is_array($categories['rubrik'])) {
            foreach ($categories['rubrik'] as $key => $_categories) {
                print '    <input type="hidden" name="rubrik['.$key.']" value="'.$_categories.'" />';
            }
        }
?>
    <input type="hidden" name="solution_id"         value="<?php print $solution_id; ?>" />
    <input type="hidden" name="revision"            value="<?php print $revision_id; ?>" />
    <input type="hidden" name="active"              value="<?php print $active; ?>" />
    <input type="hidden" name="changed"             value="<?php print $changed; ?>" />
    <input type="hidden" name="comment"             value="<?php print $comment; ?>" />
    <input type="hidden" name="dateStart"           value="<?php print $dateStart; ?>" />
    <input type="hidden" name="dateEnd"             value="<?php print $dateEnd; ?>" />
    <input type="hidden" name="userpermission"      value="<?php print $user_permission; ?>" />
    <input type="hidden" name="restricted_users"    value="<?php print $restricted_users; ?>" />
    <input type="hidden" name="grouppermission"     value="<?php print $group_permission; ?>" />
    <input type="hidden" name="restricted_group"    value="<?php print $restricted_groups; ?>" />
    <p align="center">
        <button class="btn btn-primary" type="submit" name="submit">
            <?php print $PMF_LANG['ad_entry_back']; ?>
        </button>
    </p>
    </form>
<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
