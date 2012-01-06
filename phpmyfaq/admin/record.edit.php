<?php
/**
 * The FAQ record editor.
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
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-23
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

// Re-evaluate $user
$user = PMF_User_CurrentUser::getFromSession($faqconfig->get('security.ipCheck'));

if ($permission["editbt"] && !PMF_Db::checkOnEmptyTable('faqcategories')) {

    $category = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $category->buildTree();
    
    $helper = PMF_Helper_Category::getInstance();
    $helper->setCategory($category);

    $selectedCategory = '';
    $categories       = array();
    $faqData          = array(
        'id'          => 0,
        'lang'        => $LANGCODE,
        'revision_id' => 0,
        'title'       => '',
        'dateStart'   => '',
        'dateEnd'     => '');

    $tagging = new PMF_Tags($db, $Language);

    if ($action == 'takequestion') {
        $questionId       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $question         = $faq->getQuestion($questionId);
        $selectedCategory = $question['category_id'];
        $faqData['title'] = $question['question'];
        $categories       = array(
            'category_id'   => $selectedCategory,
            'category_lang' => $faqData['lang']);
    }

    if ($action == 'editpreview') {

        $faqData['id'] = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!is_null($faqData['id'])) {
            $queryString = 'saveentry&amp;id='.$faqData['id'];
        } else {
            $queryString = 'insertentry';
        }
        
        $faqData['lang']  = PMF_Filter::filterInput(INPUT_POST, 'lang', FILTER_SANITIZE_STRING);
        $selectedCategory = isset($_POST['rubrik']) ? $_POST['rubrik'] : null;
        if (is_array($selectedCategory)) {
            foreach ($selectedCategory as $cats) {
                $categories[] = array('category_id' => $cats, 'category_lang' => $faqData['lang']);
            }
        }
        $faqData['active']      = PMF_Filter::filterInput(INPUT_POST, 'active', FILTER_SANITIZE_STRING);
        $faqData['keywords']    = PMF_Filter::filterInput(INPUT_POST, 'keywords', FILTER_SANITIZE_STRING);
        $faqData['title']       = PMF_Filter::filterInput(INPUT_POST, 'thema', FILTER_SANITIZE_STRING);
        $faqData['content']     = PMF_Filter::filterInput(INPUT_POST, 'content', FILTER_SANITIZE_SPECIAL_CHARS);
        $faqData['author']      = PMF_Filter::filterInput(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
        $faqData['email']       = PMF_Filter::filterInput(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $faqData['comment']     = PMF_Filter::filterInput(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);
        $faqData['solution_id'] = PMF_Filter::filterInput(INPUT_POST, 'solution_id', FILTER_VALIDATE_INT);
        $faqData['revision_id'] = PMF_Filter::filterInput(INPUT_POST, 'revision_id', FILTER_VALIDATE_INT, 0);
        $faqData['sticky']      = PMF_Filter::filterInput(INPUT_POST, 'sticky', FILTER_VALIDATE_INT);
        $tags                   = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
        $changed                = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
        $faqData['dateStart']   = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
        $faqData['dateEnd']     = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
        $faqData['content']     = html_entity_decode($faqData['content']);
        
    } elseif ($action == 'editentry') {

        $id   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if ((!isset($selectedCategory) && !isset($faqData['title'])) || !is_null($id)) {
            $logging = new PMF_Logging();
            $logging->logAdmin($user, 'Beitragedit, ' . $id);
            $faqData['id']   = $id;
            $faqData['lang'] = $lang;
            
            $faq->setLanguage($faqData['lang']);
            $categories = $category->getCategoryRelationsFromArticle($faqData['id'], $faqData['lang']);

            $faq->getRecord($faqData['id'], null, true);
            $faqData       = $faq->faqRecord;
            $tags          = implode(',', $tagging->getAllTagsById($faqData['id']));
            $queryString = 'saveentry&amp;id='.$faqData['id'];
        } else {
            $queryString = 'insertentry';
        }

    } elseif ($action == 'copyentry') {

        $faqData['id']   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $faqData['lang'] = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        $faq->language   = $faqData['lang'];
        $categories      = $category->getCategoryRelationsFromArticle($faqData['id'], $faqData['lang']);

        $faq->getRecord($faqData['id'], null, true);

        $faqData       = $faq->faqRecord;
        $queryString = 'insertentry';

    } else {
        $logging = new PMF_Logging();
        $logging->logAdmin($user, 'Beitragcreate');
        $queryString = 'insertentry';
        if (!is_array($categories)) {
            $categories = array();
        }
    }
    
    // Revisions
    $selectedRevisionId = PMF_Filter::filterInput(INPUT_POST, 'revisionid_selected', FILTER_VALIDATE_INT);
    if (is_null($selectedRevisionId)) {
        $selectedRevisionId = $faqData['revision_id'];
    }

    // Permissions
    $userPermission = $faq->getPermission('user', $faqData['id']);
    if (count($userPermission) == 0 || $userPermission[0] == -1) {
        $allUsers          = true;
        $restrictedUsers   = false;
        $userPermission[0] = -1;
    } else {
        $allUsers        = false;
        $restrictedUsers = true;
    }

    $groupPermission = $faq->getPermission('group', $faqData['id']);
    if (count($groupPermission) == 0 || $groupPermission[0] == -1) {
        $allGroups          = true;
        $restrictedGroups   = false;
        $groupPermission[0] = -1;
    } else {
        $allGroups        = false;
        $restrictedGroups = true;
    }

    print '<header><h2>'.$PMF_LANG["ad_entry_edit_1"];
    if ($faqData['id'] != 0 && $action != 'copyentry') {
        printf(' <span style="color: Red;">%d (%s 1.%d) </span> ',
            $faqData['id'],
            $PMF_LANG['ad_entry_revision'],
            $selectedRevisionId);
    }
    print ' '.$PMF_LANG["ad_entry_edit_2"].'</h2></header>';

    if ($permission["changebtrevs"]){

        $revisions = $faq->getRevisionIds($faqData['id'], $faqData['lang']);
        if (count($revisions)) {
?>

        <form id="selectRevision" name="selectRevision" action="?action=editentry&amp;id=<?php print $faqData['id']; ?>&amp;lang=<?php print $faqData['lang']; ?>" method="post">
        <fieldset>
            <legend><?php print $PMF_LANG['ad_changerev']; ?></legend>
            <p>
                <select name="revisionid_selected" onchange="selectRevision.submit();">
                    <option value="<?php print $faqData['revision_id']; ?>">
                        <?php print $PMF_LANG['ad_changerev']; ?>
                    </option>
<?php foreach ($revisions as $_revision_id => $_revision_data) { ?>
                    <option value="<?php print $_revision_data['revision_id']; ?>" <?php if ($selectedRevisionId == $_revision_data['revision_id']) { print 'selected="selected"'; } ?> >
                        <?php print $PMF_LANG['ad_entry_revision'].' 1.'.$_revision_data['revision_id'].': '.PMF_Date::createIsoDate($_revision_data['datum'])." - ".$_revision_data['author']; ?>
                    </option>
<?php } ?>
                </select>
            </p>
        </fieldset>
        </form>
<?php
        }

        if (isset($selectedRevisionId) &&
            isset($faqData['revision_id']) &&
            $selectedRevisionId != $faqData['revision_id']) {

            $faq->language = $faqData['lang'];
            $faq->getRecord($faqData['id'], $selectedRevisionId, true);
            $faqData = $faq->faqRecord;
            $tags    = implode(',', $tagging->getAllTagsById($faqData['id']));
        }
    }
?>

        <form id="faqEditor" action="?action=<?php print $queryString; ?>" method="post">
            <input type="hidden" name="revision_id" id="revision_id" value="<?php print $faqData['revision_id']; ?>" />
            <input type="hidden" name="record_id" id="record_id" value="<?php print $faqData['id']; ?>" />
            <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession(); ?>" />

            <fieldset>
                <legend><?php print $PMF_LANG['ad_entry_faq_record']; ?></legend>

                <p>
                    <label for="rubrik"><?php print $PMF_LANG["ad_entry_category"]; ?></label>
                    <select name="rubrik[]" id="rubrik" size="5" multiple="multiple">
                    <?php print $helper->renderCategoryOptions($categories); ?>
                    </select>
                </p>

                <p>
                    <label for="question"><?php print $PMF_LANG["ad_entry_theme"]; ?></label>
                    <input type="text" name="question" id="question" maxlength="255"
                           style="width: 536px; height: 30px; font-size: 24px;" autofocus="autofocus"
                           value="<?php if (isset($faqData['title'])) { print PMF_String::htmlspecialchars($faqData['title']); } ?>" />
                </p>

                <p>
                    <label for="answer"><?php print $PMF_LANG["ad_entry_content"]; ?></label>
                </p>
                <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                <textarea id="answer" name="answer" cols="72" rows="16" style="width: 640px; height: 480px;">
                <?php if (isset($faqData['content'])) { print trim(PMF_String::htmlentities($faqData['content'])); } ?>
                </textarea>
<?php
    if ($action == 'copyentry') {
        $faqData['lang'] = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
    }

    if ($permission['addattachment']) {
?>
                <p>
                    <label><?php print $PMF_LANG['ad_menu_attachments'] ?>:</label>
<?php
        if (isset($faqData['id']) && $faqData['id'] != "") {
?>
                    <ul class="adminAttachments">
<?php
            $attList = PMF_Attachment_Factory::fetchByRecordId($faqData['id']);
            foreach ($attList as $att) {
                printf('<li><a href="../%s">%s</a> ',
                    $att->buildUrl(),
                    $att->getFilename());
                if ($permission['delattachment']) {
                    printf('[ <a href="?action=delatt&amp;record_id=%d&amp;id=%d&amp;lang=%s">%s</a> ]',
                        $faqData['id'],
                        $att->getId(),
                        $faqData['lang'],
                        $PMF_LANG['ad_att_del']);
                }
                print "</li>\n";
            }
            printf('<li><a href="#;" onclick="addAttachment(\'attachment.php?record_id=%d&amp;record_lang=%s&amp;rubrik=%d\', \'Attachment\', 550, 130); return false;">%s</a></li>',
                $faqData['id'],
                $faqData['lang'],
                $selectedCategory,
                $PMF_LANG['ad_att_add']
                );
?>
                    </ul>
<?php
        } else {
            print $PMF_LANG['ad_att_nope'];
        }
?>
                </p>
<?php
    }
?>
                <p>
                    <label for="keywords"><?php print $PMF_LANG["ad_entry_keywords"]; ?></label>
                    <input type="text" name="keywords" id="keywords" style="width: 300px;" maxlength="255"
                           value="<?php if (isset($faqData['keywords'])) { print PMF_String::htmlspecialchars($faqData['keywords']); } ?>" />
                    <span id="keywordsHelp" style="display: none;"><?php print $PMF_LANG['msgShowHelp']; ?></span>
                </p>

                <p>
                    <label for="tags"><?php print $PMF_LANG['ad_entry_tags']; ?>:</label>
                    <input type="text" name="tags" id="tags" style="width: 300px;" maxlength="255"
                           value="<?php if (isset($tags)) { print PMF_String::htmlspecialchars($tags); } ?>" />
                    <img style="display: none; margin-bottom: -5px;" id="tags_autocomplete_wait" src="images/indicator.gif" alt="waiting..." />
                    <script type="text/javascript">
                        $('#tags').autocomplete("index.php?action=ajax&ajax=tags_list", { width: 260, selectFirst: false, multiple: true } );
                    </script>
                    <span id="tagsHelp" style="display: none;"><?php print $PMF_LANG['msgShowHelp']; ?></span>
                </p>

                <p>
                    <label for="author"><?php print $PMF_LANG["ad_entry_author"]; ?></label>
                    <input type="text" name="author" id="author" style="width: 300px;"
                           value="<?php if (isset($faqData['author'])) { print PMF_String::htmlspecialchars($faqData['author']); } else { print $user->getUserData('display_name'); } ?>" />
                </p>

                <p>
                    <label for="email"><?php print $PMF_LANG["ad_entry_email"]; ?></label>
                    <input type="email" name="email" id="email" style="width: 300px;"
                           value="<?php if (isset($faqData['email'])) { print PMF_String::htmlspecialchars($faqData['email']); } else { print $user->getUserData('email'); } ?>" />
                </p>

            </fieldset>

            <fieldset>
                <legend><?php print $PMF_LANG['ad_entry_record_administration']; ?></legend>

<?php if ($faqconfig->get('main.enableGoogleTranslation') === true): ?>
                <input type="hidden" id="lang" name="lang" value="<?php print $faqData['lang']; ?>" />
<?php else: ?>
                <p>
                    <label for="lang"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
                    <?php print PMF_Language::selectLanguages($faqData['lang'], false, array(), 'lang'); ?>
                </p>
<?php endif; ?>
                <p>
                    <label for="solution_id"><?php print $PMF_LANG['ad_entry_solution_id']; ?>:</label>
                    <input name="solution_id" id="solution_id" style="width: 50px; text-align: right;" size="5"
                           readonly="readonly" value="<?php print (isset($faqData['solution_id']) ? $faqData['solution_id'] : $faq->getSolutionId()); ?>"  />
                </p>
                
                <p>
                    <label for="active"><?php print $PMF_LANG["ad_entry_active"]; ?></label>
                    <?php if($permission['approverec']):
                        if (isset($faqData['active']) && $faqData['active'] == 'yes') {
                            $suf = ' checked="checked"';
                            $sul = null;
                        } elseif ($faqconfig->get('records.defaultActivation')) {
                            $suf = ' checked="checked"';
                            $sul = null;
                        } else {
                            $suf = null;
                            $sul = ' checked="checked"';
                        }
                    ?>
                    <input type="radio" id="active" name="active" class="active" value="yes"<?php if (isset($suf)) { print $suf; } ?> />
                    <?php print $PMF_LANG['ad_gen_yes']; ?>
                    <input type="radio" name="active" class="active" value="no"<?php if (isset($sul)) { print $sul; } ?> />
                    <?php print $PMF_LANG['ad_gen_no']; ?>
<?php else: ?>
                    <input type="radio" name="active" class="active" value="no" checked="checked" />
                    <?php print $PMF_LANG['ad_gen_no']; ?>
<?php endif; ?>
                </p>

                <p>
                    <label for="sticky"><?php print $PMF_LANG['ad_entry_sticky']; ?>:</label>
                    <input type="checkbox" id="sticky" name="sticky" <?php print (isset($faqData['sticky']) && $faqData['sticky'] ? 'checked="checked"' : '') ?> />
                </p>
                <?php
                    if (isset($faqData['comment']) && $faqData['comment'] == 'y') {
                        $suf = ' checked="checked"';
                    } elseif ($faqconfig->get('records.defaultAllowComments')) {
                        $suf = ' checked="checked"';
                    } else {
                        $suf = null;
                    }
                ?>
                <p>
                    <label for="comment"><?php print $PMF_LANG["ad_entry_allowComments"]; ?></label>
                    <input type="checkbox" name="comment" id="comment" value="y"<?php if (isset($suf)) { print $suf; } ?> />
                    <?php print $PMF_LANG['ad_gen_yes']; ?>
                </p>
<?php
    if ($queryString != 'insertentry') {
        $rev_yes = ' checked="checked"';
        $rev_no  = null;
    }
    if (isset($faqData['active']) && $faqData['active'] == 'no') {
        $rev_no  = ' checked="checked"';
        $rev_yes = null;
    }
    if ($queryString != 'insertentry'):
?>
                <p>
                    <label for="revision"><?php print $PMF_LANG['ad_entry_new_revision']; ?></label>
                    <input type="radio" name="revision" id="revision" class="active" value="yes"<?php print isset($rev_yes) ? $rev_yes : ''; ?>/>
                    <?php print $PMF_LANG["ad_gen_yes"]; ?>
                    <input type="radio" name="revision" class="active" value="no"<?php print isset($rev_no) ? $rev_no : ''; ?>/>
                    <?php print $PMF_LANG["ad_gen_no"]; ?>
                </p>

<?php
    endif;
    if ($faqconfig->get('security.permLevel') != 'basic'): ?>

                <p>
                    <label for="grouppermission"><?php print $PMF_LANG['ad_entry_grouppermission']; ?></label>
                    <input type="radio" id="grouppermission" name="grouppermission" class="active" value="all" <?php print ($allGroups ? 'checked="checked"' : ''); ?>/>
                    <?php print $PMF_LANG['ad_entry_all_groups']; ?>
                    <br />
                    <input type="radio" name="grouppermission" class="active" value="restricted" <?php print ($restrictedGroups ? 'checked="checked"' : ''); ?>/>
                    <?php print $PMF_LANG['ad_entry_restricted_groups']; ?>
                    <select name="restricted_groups" size="1">
                        <?php print $user->perm->getAllGroupsOptions($groupPermission[0]); ?>
                    </select>
                </p>

<?php else: ?>
                    <input type="hidden" name="grouppermission" class="active" value="all" />
<?php endif;
    
    if ('00000000000000' == $faqData['dateStart']) {
        $dateStart = '';
    } else {
        $dateStart = preg_replace("/(\d{4})(\d{2})(\d{2}).*/", "$1-$2-$3", $faqData['dateStart']);
    }
    
    if ('99991231235959' == $faqData['dateEnd']) {
        $dateEnd = '';
    } else {
        $dateEnd = preg_replace("/(\d{4})(\d{2})(\d{2}).*/", "$1-$2-$3", $faqData['dateEnd']);
    }
    
    if (!isset($faqData['date'])) { 
    	$faqData['date'] = PMF_Date::createIsoDate(date('YmdHis'));
    }
?>
                <p>
                    <label for="userpermission"><?php print $PMF_LANG['ad_entry_userpermission']; ?></label>
                    <input type="radio" id="userpermission" name="userpermission" class="active" value="all" <?php print ($allUsers ? 'checked="checked"' : ''); ?>/>
                    <?php print $PMF_LANG['ad_entry_all_users']; ?>
                    <br />
                    <input type="radio" name="userpermission" class="active" value="restricted" <?php print ($restrictedUsers ? 'checked="checked"' : ''); ?>/>
                    <?php print $PMF_LANG['ad_entry_restricted_users']; ?>
                    <select name="restricted_users" size="1">
                        <?php print $user->getAllUserOptions($userPermission[0]); ?>
                    </select>
                </p>

                <p>
                    <label for="dateActualize"><?php echo $PMF_LANG["ad_entry_date"]; ?></label>
                    <input type="radio" id="dateActualize" checked="checked" name="recordDateHandling" onchange="setRecordDate(this.id);" /> <?php print $PMF_LANG['msgUpdateFaqDate']; ?>
                    <input type="radio" id="dateKeep" name="recordDateHandling" onchange="setRecordDate(this.id);" /> <?php print $PMF_LANG['msgKeepFaqDate']; ?>
                    <input type="radio" id="dateCustomize" name="recordDateHandling" onchange="setRecordDate(this.id);" /> <?php print $PMF_LANG['msgEditFaqDat']; ?>
                </p>

                <div id="recordDateInputContainer" style="display: none;">
                <p>
                    <label>&nbsp;</label>
                    <input type="text" name="date" id="date" maxlength="16" value="" />
                </p>
                </div>
            </fieldset>
<?php
    if ($faqconfig->get('main.enableGoogleTranslation') === true) {
?>    
            <fieldset>
                <legend><a href="javascript:void(0);" onclick="javascript:toggleFieldset('Translations');"><?php print $PMF_LANG["ad_menu_translations"]; ?></a></legend>

                <div id="editTranslations" style="display: none;">
                    <?php
                    if ($faqconfig->get('main.googleTranslationKey') == '') {
                        print $PMF_LANG["msgNoGoogleApiKeyFound"];
                    } else {
                    ?>
                    <p>
                        <label for="langTo"><?php print $PMF_LANG["ad_entry_locale"]; ?>:</label>
                        <?php print PMF_Language::selectLanguages($faqData['lang'], false, array(), 'langTo'); ?>
                    </p>

                        <input type="hidden" name="used_translated_languages" id="used_translated_languages" value="" />
                        <div id="getedTranslations">
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </fieldset>
<?php
    }
?>
            <fieldset>
                <legend><a href="javascript:void(0);" onclick="javascript:toggleFieldset('Expiration');"><?php print $PMF_LANG['ad_record_expiration_window']; ?></a></legend>
    
                <div id="editExpiration" style="display: none;">
                    <p>
                        <label for="dateStart"><?php print $PMF_LANG['ad_news_from']; ?></label>
                        <input name="dateStart" id="dateStart" class="date-pick" value="<?php print $dateStart; ?>" maxlength="10" />
                    </p>
                    <p>
                        <label for="dateEnd"><?php print $PMF_LANG['ad_news_to']; ?></label>
                        <input name="dateEnd" id="dateEnd" class="date-pick" value="<?php print $dateEnd; ?>" maxlength="10" />
                    </p>
                </div>
            </fieldset>

            <fieldset>
                <legend><a href="javascript:void(0);" onclick="javascript:toggleFieldset('Changelog');"><?php print $PMF_LANG['ad_entry_changelog']; ?></a></legend>

                <div id="editChangelog" style="display: none;">
                    </p>
                    <p>
                        <label><?php print $PMF_LANG["ad_entry_date"]; ?></label>
                        <?php
                        if (isset($faqData['date'])) {
                            print PMF_Date::format($faqData['date']);
                        } else {
                            print PMF_Date::format(date('Y-m-d H:i'));
                        }
                        ?>
                    </p>
                    <p>
                        <label for="changed"><?php print $PMF_LANG["ad_entry_changed"]; ?></label>
                        <textarea name="changed" id="changed" style="width: 390px; height: 50px;" cols="40" rows="4"><?php if (isset($changed)) { print $changed; } ?></textarea>
                    </p>
                    <p>
                </div>

            </fieldset>

<?php
    if ($selectedRevisionId == $faqData['revision_id']) {
?>
            <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_save"]; ?>" name="submit[1]" />
            <input class="submit" type="reset" value="<?php print $PMF_LANG["ad_gen_reset"]; ?>" />
<?php
    }
    if ($queryString != "insertentry") {
?>
            <input class="submit" type="submit" value="<?php print $PMF_LANG["ad_entry_delete"]; ?>" name="submit[0]" />
<?php
    }
?>
    </p>
<?php
    if (is_numeric($faqData['id'])) {
?>
            <fieldset>
                <legend>
                    <a href="javascript:void(0);" onclick="javascript:toggleFieldset('ChangelogHistory');">
                        <?php print $PMF_LANG['ad_entry_changelog_history']; ?>
                    </a>
                </legend>
                
                <div id="editChangelogHistory" style="display: none;">
<?php
        $changeEntries = $faq->getChangeEntries($faqData['id']);
        foreach ($changeEntries as $entry) {
            $user->getUserById($entry['user']);
?>
                    <p style="font-size: 10px;">
                        <label>
                            <?php printf('%s  1.%d<br/>%s<br/>%s: %s',
                                $PMF_LANG['ad_entry_revision'],
                                $entry['revision_id'],
                                PMF_Date::format(date('Y-m-d H:i', $entry['date'])),
                                $PMF_LANG['ad_entry_author'],
                                $user->getUserData('display_name')); ?>
                        </label>
                        <?php print $entry['changelog']; ?>
                    </p>
<?php
        }
?>
                </div>
            </fieldset>
    
    </form>
    
    <script type="text/javascript">
    /* <![CDATA[ */

    $(function()
    {
        $('.date-pick').datePicker();

        $('#date').datePicker({startDate: '1900-01-01'});
        $('#date').bind('dateSelected', function (e, date, $td, status)
        {
            if(status) {
                var dt = new Date();

                var hours   = dt.getHours();
                var minutes = dt.getMinutes();
                
                $('#date').val(date.asString() +
                               ' ' + (hours < 10 ? '0' : '') + hours +
                               ':' + (minutes < 10 ? '0' : '') + minutes);
            }
        });

        $('#keywords').focus(function() { showHelp('keywords'); });
        $('#tags').focus(function() { showHelp('tags'); });
        
    });

    /**
     * Toggle fieldsets
     *
     * @param string fieldset ID of the fieldset
     *
     * @return void
     */
    function toggleFieldset(fieldset)
    {
        if ($('#edit' + fieldset).css('display') == 'none') {
            $('#edit' + fieldset).fadeIn('fast');
        } else {
            $('#edit' + fieldset).fadeOut('fast');
        }
    }
    
    /**
     * Toggle input date container show
     *
     * @param boolean show show or hide (optional)
     *
     * @return void
     */
    function showIDContainer()
    {
        var display = 0 == arguments.length || !!arguments[0] ? 'block' : 'none';
        
        $('#recordDateInputContainer').attr('style', 'display: ' + display);
    }


    function setRecordDate(how)
    {
        if('dateActualize' == how) {
            showIDContainer(false);
            $('#date').val('');
        } else if ('dateKeep' == how) {
            showIDContainer(false);
            $('#date').val('<?php print $faqData['date']; ?>');
        } else if('dateCustomize' == how) {
            showIDContainer(true);
            $('#date').val('');
        }
    }
        
    /**
     * Shows help for keywords and tags input fields
     * 
     * @param string 
     * 
     * @return void
     */
    function showHelp(option)
    {
        $('#' + option + 'Help').fadeIn(500);
        $('#' + option + 'Help').fadeOut(2500);
    }
    /* ]]> */
    </script>
<?php    
        if ($faqconfig->get('main.enableGoogleTranslation') === true) {
?>        
    <script src="https://www.google.com/jsapi?key=<?php echo $faqconfig->get('main.googleTranslationKey')?>" type="text/javascript"></script>
    <script type="text/javascript">
    /* <![CDATA[ */
    google.load("language", "1");

    var langFromSelect = $("#lang");
    var langToSelect   = $("#langTo");
        
    // Add a onChange to the faq language select
    langFromSelect.change(
        function() {
            $("#langTo").val($(this).val());
        }
    );
    
    // Add a onChange to the translation select
    langToSelect.change(
        function() {
            var langTo = $(this).val();

            if (! $('#question_translated_' + langTo).val()) {

                // Add language value
                var languages = $('#used_translated_languages').val();
                if (languages == '') {
                    $('#used_translated_languages').val(langTo);
                } else {
                    $('#used_translated_languages').val(languages + ',' + langTo);
                }
               
                var fieldset = $('<fieldset></fieldset>')
                    .append($('<legend></legend>').html($("#langTo option:selected").text()));

                // Text for question
                fieldset
                    .append('<p>' +
                            '<label for="question_translated_' + langTo + '">' +
                            '<?php print $PMF_LANG["ad_entry_theme"]; ?>' +
                            '</label>' +
                            '<input type="text" id="question_translated_' + langTo + '" name="question_translated_' + langTo + '" maxlength="255" style="width: 300px;">' +
                            '</p>');


                // Textarea for answer
                fieldset
                    .append('<p>' +
                            '<label for="answer_translated_' + langTo + '">' +
                            '<?php print $PMF_LANG["ad_entry_content"]; ?>' +
                            '</label>' +
                            '<textarea id="answer_translated_' + langTo + '" name="answer_translated_' + langTo + '" cols="80" rows="3" style="width: 300px;"></textarea>' +
                            '</p>');


                // Textarea for keywords
                fieldset
                    .append('<p>' +
                            '<label for="keywords_translated_' + langTo + '">' +
                            '<?php print $PMF_LANG["ad_entry_keywords"]; ?>' +
                            '</label>' +
                            '<textarea id="keywords_translated_' + langTo + '" name="keywords_translated_' + langTo + '" cols="80" rows="3" style="width: 300px;"></textarea>' +
                            '</p>');

                $('#getedTranslations').append(fieldset);
                
                // Call the init for a new tinyMCE
                createTinyMCE('answer_translated_' + langTo);
            }

            var langFrom = $('#lang').val();
            
            // Set the translated text
            getGoogleTranslation('#question_translated_' + langTo, $('#question').val(), langFrom, langTo);
            getGoogleTranslation('answer_translated_' + langTo, tinymce.get('answer').getContent(), langFrom, langTo, 'answer');

            // Keywords must be translated separately
            $('#keywords_translated_' + langTo).val('');
            var words = new String($('#keywords').val()).split(',');
            for (var i = 0; i < words.length; i++) {
                var word = $.trim(words[i]);
                getGoogleTranslation('#keywords_translated_' + langTo, word, langFrom, langTo, 'keywords');
            }
        }
    );

    /**
     * Call the init for a new tinyMCE
     *
     * @param string field  id of the input to fill.
     *
     * @return void
     */
    function createTinyMCE(field)
    {
        tinyMCE.init({
            // General options
            mode     : "exact",
            language : "<?php print (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en'); ?>",
            elements : field,
            width    : "720",
            height   : "480",
            theme    : "advanced",
            plugins  : "spellchecker,pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,inlinepopups,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template,syntaxhl,phpmyfaq",
            theme_advanced_blockformats : "p,div,h1,h2,h3,h4,h5,h6,blockquote,dt,dd,code,samp",
                
            // Theme options
            theme_advanced_buttons1 : "bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,styleselect,formatselect,fontselect,fontsizeselect",
            theme_advanced_buttons2 : "cut,copy,paste,pastetext,pasteword,|,search,replace,|,phpmyfaq,|,bullist,numlist,|,outdent,indent,blockquote,|,undo,redo,|,link,unlink,anchor,image,cleanup,code,syntaxhl,|,insertdate,inserttime,preview,|,forecolor,backcolor",
            theme_advanced_buttons3 : "tablecontrols,|,hr,removeformat,visualaid,|,sub,sup,|,charmap,emotions,iespell,media,advhr,|,print,|,ltr,rtl,|,fullscreen,help",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_statusbar_location : "bottom",
            relative_urls           : false,
            convert_urls            : false,
            remove_linebreaks       : false, 
            use_native_selects      : true,
            extended_valid_elements : "code",
                
            // Ajax-based file manager
            file_browser_callback : "ajaxfilemanager",
                
            // Example content CSS (should be your site CSS)
            content_css : "../template/<?php print PMF_Template::getTplSetName(); ?>/css/style.css",
                
            // Drop lists for link/image/media/template dialogs
            template_external_list_url : "js/template_list.js",
                
            // Replace values for the template plugin
            template_replace_values : {
                username : "<?php print $user->userdata->get('display_name'); ?>",
                user_id  : "<?php print $user->userdata->get('user_id'); ?>"
            }
        });
    }    
    /* ]]> */
    </script>
<?php
        }
    }
} elseif ($permission["editbt"] != 1 && !PMF_Db::checkOnEmptyTable('faqcategories')) {
    print $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && PMF_Db::checkOnEmptyTable('faqcategories')) {
    print $PMF_LANG["no_cats"];
}
