<?php
/**
 * Adds a record in the database, handles the preview and checks for missing
 * category entries.
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
 * @copyright 2003-2010 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('main.ipCheck'));

if ($permission['editbt']) {
	
	// Get submit action
    $submit        = PMF_Filter::filterInputArray(INPUT_POST, array('submit' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    // FAQ data
    $dateStart     = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
    $dateEnd       = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
    $question      = PMF_Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_STRING);
    $categories    = PMF_Filter::filterInputArray(INPUT_POST, array('rubrik' => array('filter' => FILTER_VALIDATE_INT,
                                                                                      'flags'  => FILTER_REQUIRE_ARRAY)));
    $record_lang   = PMF_Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_STRING);
    $tags          = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
    $active        = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
    $sticky        = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_SANITIZE_STRING);
    $content       = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
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
    
    if (isset($submit['submit'][1]) && !is_null($question) && !is_null($categories['rubrik'])) {
        // new entry
        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Beitragcreatesave');
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
        
        $recordData     = array(
            'id'            => null,
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
        $faqRecord    = new PMF_Faq_Record();
        $faqChangelog = new PMF_Faq_Changelog();
        if ($faqRecord->create($recordData)) {
            
            $recordId = $faqRecord->getRecordId();
            
            // Create ChangeLog entry
            $changelogData = array(
                'record_id'   => $recordId,
                'record_lang' => $recordData['lang'],
                'revision_id' => 0,
                'user_id'     => $user->getUserId(),
                'date'        => $_SERVER['REQUEST_TIME'],
                'changelog'   => nl2br($changed));
            
            $faqChangelog->create($changelogData);
            
            // Create the visit entry
            $visits = PMF_Visits::getInstance();
            $visits->add($recordId, $recordData['lang']);
            
            // Insert the new category relations
            $categoryRelations = new PMF_Category_Relations();

            // Insert the tags
            if ($tags != '') {
                $tagging = new PMF_Tags();
                $tagging->saveTags($recordId, explode(',',$tags));
            }
            
            // Set record permissions
            $faqUser = new PMF_Faq_User();
            $faqUser->create(array('record_id' => $recordId, 'user_id' => $restricted_users));
            if ($faqconfig->get('main.permLevel') != 'basic') {
                $faqGroup = new PMF_Faq_Group();
                $faqGroup->create(array('record_id' => $recordId, 'group_id' => $restricted_groups));
            }
            
            // Loop the categories
            $categoryUser      = new PMF_Category_User();
            $categoryGroup     = new PMF_Category_Group();
            $categoryRelations = new PMF_Category_Relations();
            $categoryRelations->setLanguage($recordData['lang']);
            foreach ($categories['rubrik'] as $categoryId) {
                
                // Insert the new category relations
                $categoryData = array(
                    'category_id'   => $categoryId,
                    'category_lang' => $categoryRelations->getLanguage(),
                    'record_id'     => $recordId,
                    'record_lang'   => $recordData['lang']);
                // save or update the category relations
                $categoryRelations->create($categoryData);
                
                // Add user permissions
                $userPermission = array(
                    'category_id' => $categoryId,
                    'user_id'     => $restricted_users);
                $categoryUser->delete($categoryId);
                $categoryUser->create($userPermission);
                
                // Add group permission
                if ($groupSupport) {
                    $groupPermission = array(
                        'category_id' => $categoryId,
                        'group_id'    => $restricted_groups);
                    $categoryGroup->delete($categoryId);
                    $categoryGroup->create($groupPermission);
                }
            }
            
            print $PMF_LANG['ad_entry_savedsuc'];
            
            // Call Link Verification
            link_ondemand_javascript($recordId, $recordData['lang']);

            // Callback to Twitter if enabled
            if ($faqconfig->get('socialnetworks.enableTwitterSupport')) {
                $connection = new TwitterOAuth($faqconfig->get('socialnetworks.twitterConsumerKey'),
                                               $faqconfig->get('socialnetworks.twitterConsumerSecret'),
                                               $faqconfig->get('socialnetworks.twitterAccessTokenKey'),
                                               $faqconfig->get('socialnetworks.twitterAccessTokenSecret'));

                $link = PMF_Link::getSystemRelativeUri() .
                        sprintf('?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                            $category,
                            $record_id,
                            $record_lang);
                $link             = $faqconfig->get('main.referenceURL') . str_replace('/admin/','/', $link);
                $oLink            = new PMF_Link($link);
                $oLink->itemTitle = $question;
                $link             = $oLink->toString();
                
                if ($connection) {
                    $twitter = new PMF_Services_Twitter($connection);
                    $twitter->addPost($question, $tags, $link);
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
        } else {
            print $PMF_LANG['ad_entry_savedfail'].$db->error();
        }
    } else {
        printf("<h2>%s</h2>\n", $PMF_LANG['ad_entry_aor']);
        printf("<p>%s</p>", $PMF_LANG['ad_entryins_fail']);
?>
    <form action="?action=editpreview" method="post">
    <input type="hidden" name="thema"               value="<?php print PMF_String::htmlspecialchars($question); ?>" />
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
    <p align="center"><input class="submit" type="submit" name="submit" value="<?php print $PMF_LANG['ad_entry_back']; ?>" /></p>
    </form>
<?php
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}
