<?php
/**
 * The FAQ record editor.
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

if (($permission['editbt']|| $permission['addbt']) && !PMF_Db::checkOnEmptyTable('faqcategories')) {

    $category = new PMF_Category($faqConfig, false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $category->buildTree();

    $categoryHelper = new PMF_Helper_Category();
    $categoryHelper->setCategory($category);

    $selectedCategory = '';
    $categories       = array();
    $faqData          = array(
        'id'          => 0,
        'lang'        => $LANGCODE,
        'revision_id' => 0,
        'title'       => '',
        'dateStart'   => '',
        'dateEnd'     => ''
    );

    $tagging = new PMF_Tags($faqConfig);
    $date    = new PMF_Date($faqConfig);

    if ('takequestion' === $action) {
        $questionId       = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $question         = $faq->getQuestion($questionId);
        $selectedCategory = $question['category_id'];
        $faqData['title'] = $question['question'];
        $notifyUser       = $question['username'];
        $notifyEmail      = $question['email'];
        $categories       = array(
            'category_id'   => $selectedCategory,
            'category_lang' => $faqData['lang']
        );
    } else {
        $questionId  = 0;
        $notifyUser  = '';
        $notifyEmail = '';
    }

    if ('editpreview' === $action) {

        $faqData['id'] = PMF_Filter::filterInput(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!is_null($faqData['id'])) {
            $queryString = 'saveentry&amp;id=' . $faqData['id'];
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
        $faqData['tags']        = PMF_Filter::filterInput(INPUT_POST, 'tags', FILTER_SANITIZE_STRING);
        $faqData['changed']     = PMF_Filter::filterInput(INPUT_POST, 'changed', FILTER_SANITIZE_STRING);
        $faqData['dateStart']   = PMF_Filter::filterInput(INPUT_POST, 'dateStart', FILTER_SANITIZE_STRING);
        $faqData['dateEnd']     = PMF_Filter::filterInput(INPUT_POST, 'dateEnd', FILTER_SANITIZE_STRING);
        $faqData['content']     = html_entity_decode($faqData['content']);
        
    } elseif ('editentry' === $action) {

        $id   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $lang = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        if ((!isset($selectedCategory) && !isset($faqData['title'])) || !is_null($id)) {
            $logging = new PMF_Logging($faqConfig);
            $logging->logAdmin($user, 'Beitragedit, ' . $id);

            $categories = $category->getCategoryRelationsFromArticle($id, $lang);

            $faq->getRecord($id, null, true);
            $faqData         = $faq->faqRecord;
            $faqData['tags'] = implode(',', $tagging->getAllTagsById($faqData['id']));
            $queryString     = 'saveentry&amp;id=' . $faqData['id'];
        } else {
            $queryString = 'insertentry';
        }

    } elseif ('copyentry' === $action) {

        $faqData['id']   = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $faqData['lang'] = PMF_Filter::filterInput(INPUT_GET, 'lang', FILTER_SANITIZE_STRING);
        $faq->language   = $faqData['lang'];
        $categories      = $category->getCategoryRelationsFromArticle($faqData['id'], $faqData['lang']);

        $faq->getRecord($faqData['id'], null, true);

        $faqData     = $faq->faqRecord;
        $queryString = 'insertentry';

    } else {
        $logging = new PMF_Logging($faqConfig);
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

    // User permissions
    $userPermission = $faq->getPermission('user', $faqData['id']);
    if (count($userPermission) == 0 || $userPermission[0] == -1) {
        $allUsers          = true;
        $restrictedUsers   = false;
        $userPermission[0] = -1;
    } else {
        $allUsers        = false;
        $restrictedUsers = true;
    }

    // Group permissions
    $groupPermission = $faq->getPermission('group', $faqData['id']);
    if (count($groupPermission) == 0 || $groupPermission[0] == -1) {
        $allGroups          = true;
        $restrictedGroups   = false;
        $groupPermission[0] = -1;
    } else {
        $allGroups        = false;
        $restrictedGroups = true;
    }

    // Set data for forms
    $faqData['title']    = (isset($faqData['title']) ? PMF_String::htmlspecialchars($faqData['title']) : '');
    $faqData['content']  = (isset($faqData['content']) ? trim(PMF_String::htmlentities($faqData['content'])) : '');
    $faqData['tags']     = (isset($faqData['tags']) ? PMF_String::htmlspecialchars($faqData['tags']) : '');
    $faqData['keywords'] = (isset($faqData['keywords']) ? PMF_String::htmlspecialchars($faqData['keywords']) : '');
    $faqData['author']   = (isset($faqData['author']) ? PMF_String::htmlspecialchars($faqData['author']) : $user->getUserData('display_name'));
    $faqData['email']    = (isset($faqData['email']) ? PMF_String::htmlspecialchars($faqData['email']) : $user->getUserData('email'));
    $faqData['date']     = (isset($faqData['date']) ? $date->format($faqData['date']) : $date->format(date('Y-m-d H:i')));
    $faqData['changed']  = (isset($faqData['changed']) ? $faqData['changed'] : '');

    if (isset($faqData['comment']) && $faqData['comment'] == 'y') {
        $faqData['comment'] = ' checked="checked"';
    } elseif ($faqConfig->get('records.defaultAllowComments')) {
        $faqData['comment'] = ' checked="checked"';
    } else {
        $faqData['comment'] = '';
    }

    // Start header
    if (0 !== $faqData['id'] && 'copyentry' !== $action) {
        $currentRevision = sprintf(
            ' <span class="badge badge-important">%s 1.%d</span> ',
            $PMF_LANG['ad_entry_revision'],
            $selectedRevisionId
        );
        printf(
            '<header><h2>%s <span class="text-error">%s</span> %s %s</h2></header>',
            $PMF_LANG['ad_entry_edit_1'],
            (0 === $faqData['id'] ? '' : $faqData['id']),
            $PMF_LANG['ad_entry_edit_2'],
            $currentRevision
        );
    } else {
        printf(
            '<header><h2>%s</h2></header>',
            $PMF_LANG['ad_entry_add']
        );
    }
?>
        <div class="row-fluid">
            <!-- Revisions -->

        </div>

        <div class="row-fluid">

            <form id="faqEditor" action="?action=<?php echo $queryString; ?>" method="post">
            <input type="hidden" name="revision_id" id="revision_id" value="<?php echo $faqData['revision_id']; ?>" />
            <input type="hidden" name="record_id" id="record_id" value="<?php echo $faqData['id']; ?>" />
            <input type="hidden" name="csrf" value="<?php echo $user->getCsrfTokenFromSession(); ?>" />
            <input type="hidden" name="openQuestionId" id="openQuestionId" value="<?php echo $questionId; ?>" />
            <input type="hidden" name="notifyUser" id="notifyUser" value="<?php echo $notifyUser ?>" />
            <input type="hidden" name="notifyEmail" id="notifyEmail" value="<?php echo $notifyEmail ?>" />

            <!-- main editor window -->
            <div class="span8">
                <fieldset class="form-inline">
                    <!-- Question -->
                    <div class="control-group">
                        <div class="controls">
                            <input type="text" name="question" id="question" class="admin-question"
                                   placeholder="<?php echo $PMF_LANG['ad_entry_theme']; ?>" maxlength="255"
                                   value="<?php echo $faqData['title'] ?>" />
                        </div>
                    </div>
                    <!-- Answer -->
                    <div class="control-group">
                        <div class="controls">
                            <noscript>Please enable JavaScript to use the WYSIWYG editor!</noscript>
                            <textarea id="answer" name="answer" class="span8">
                                <?php echo $faqData['content'] ?>
                            </textarea>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="form-horizontal">
                    <!-- Meta data -->
                    <?php if ($faqConfig->get('main.enableGoogleTranslation') === true): ?>
                        <input type="hidden" id="lang" name="lang" value="<?php echo $faqData['lang']; ?>" />
                    <?php else: ?>
                    <div class="control-group">
                        <label class="control-label" for="lang"><?php echo $PMF_LANG["ad_entry_locale"]; ?>:</label>
                        <div class="controls">
                            <?php echo PMF_Language::selectLanguages($faqData['lang'], false, array(), 'lang'); ?>
                        </div>
                     </div>
                     <?php endif; ?>
                </fieldset>
                <fieldset class="form-horizontal">
                    <!-- Attachments -->
                    <?php if ($permission['addattachment']): ?>
                    <div class="control-group">
                        <label class="control-label"><?php echo $PMF_LANG['ad_menu_attachments'] ?>:</label>
                        <div class="controls">
                            <?php
                            if (isset($faqData['id']) && $faqData['id'] != "") {
                            ?>
                            <ul class="adminAttachments">
                                <?php
                                $attList = PMF_Attachment_Factory::fetchByRecordId($faqConfig, $faqData['id']);
                                foreach ($attList as $att) {
                                    printf(
                                        '<li><a href="../%s">%s</a> ',
                                        $att->buildUrl(),
                                        $att->getFilename()
                                    );
                                    if ($permission['delattachment']) {
                                        printf(
                                            '<a class="label label-important" href="?action=delatt&amp;record_id=%d&amp;id=%d&amp;lang=%s"><i class="icon-trash icon-white"></i></a>',
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
                                    '<a class="btn btn-success" onclick="addAttachment(\'attachment.php?record_id=%d&amp;record_lang=%s&amp;rubrik=%d\', \'Attachment\');">%s</a>',
                                    $faqData['id'],
                                    $faqData['lang'],
                                    $selectedCategory,
                                    $PMF_LANG['ad_att_add']
                                );
                            } else {
                                echo $PMF_LANG['ad_att_nope'];
                            }
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Tags -->
                    <div class="control-group">
                        <label class="control-label" for="tags"><?php echo $PMF_LANG['ad_entry_tags']; ?>:</label>
                        <div class="controls">
                            <input type="text" name="tags" id="tags"  maxlength="255"
                                   value="<?php echo $faqData['tags'] ?>" />
                            <img style="display: none; margin-bottom: -5px;" id="tags_autocomplete_wait"
                                 src="images/indicator.gif" alt="waiting..." />
                            <script type="text/javascript">
                                $('#tags').autocomplete(
                                        "index.php?action=ajax&ajax=tags_list",
                                        { width: 260, selectFirst: false, multiple: true }
                                );
                            </script>
                            <span id="tagsHelp" class="hide"><?php echo $PMF_LANG['msgShowHelp']; ?></span>
                        </div>
                    </div>
                    <!-- Keywords -->
                    <div class="control-group">
                        <label class="control-label" for="keywords"><?php echo $PMF_LANG["ad_entry_keywords"]; ?></label>
                        <div class="controls">
                            <input type="text" name="keywords" id="keywords"  maxlength="255"
                                   value="<?php echo $faqData['keywords'] ?>" />
                            <span id="keywordsHelp" class="hide"><?php echo $PMF_LANG['msgShowHelp']; ?></span>
                        </div>
                    </div>
                </fieldset>
                <fieldset class="form-horizontal">
                    <?php
                    if ('00000000000000' == $faqData['dateStart']) {
                        $faqData['dateStart'] = '';
                    } else {
                        $faqData['dateStart'] = preg_replace("/(\d{4})(\d{2})(\d{2}).*/", "$1-$2-$3", $faqData['dateStart']);
                    }

                    if ('99991231235959' == $faqData['dateEnd']) {
                        $faqData['dateEnd'] = '';
                    } else {
                        $faqData['dateEnd'] = preg_replace("/(\d{4})(\d{2})(\d{2}).*/", "$1-$2-$3", $faqData['dateEnd']);
                    }
                    ?>
                    <legend><?php echo $PMF_LANG['ad_record_expiration_window']; ?></legend>
                    <div class="control-group">
                        <label class="control-label" for="dateStart"><?php echo $PMF_LANG['ad_news_from']; ?></label>
                        <div class="controls">
                            <input name="dateStart" id="dateStart" class="date-pick span2"  maxlength="10"
                                   value="<?php echo $faqData['dateStart']; ?>" />
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="dateEnd"><?php echo $PMF_LANG['ad_news_to']; ?></label>
                        <div class="controls">
                            <input name="dateEnd" id="dateEnd" class="date-pick span2" maxlength="10"
                                   value="<?php echo $faqData['dateEnd']; ?>" />
                        </div>
                    </div>
                </fieldset>
                <fieldset class="form-horizontal">
                    <!-- Author -->
                    <div class="control-group">
                        <label class="control-label" for="author"><?php echo $PMF_LANG["ad_entry_author"]; ?></label>
                        <div class="controls">
                            <input type="text" name="author" id="author" value="<?php echo $faqData['author'] ?>" />
                        </div>
                    </div>
                    <!-- E-Mail -->
                    <div class="control-group">
                        <label class="control-label" for="email"><?php echo $PMF_LANG["ad_entry_email"]; ?></label>
                        <div class="controls">
                            <input type="email" name="email" id="email" value="<?php echo $faqData['email'] ?>" />
                        </div>
                    </div>
                </fieldset>
                <fieldset class="form-horizontal">
                    <legend><?php echo $PMF_LANG['ad_entry_changelog']; ?></legend>
                    <div class="control-group" id="editChangelog">
                        <label class="control-label"><?php echo $PMF_LANG["ad_entry_date"]; ?></label>
                        <div class="controls">
                            <span class="input-medium uneditable-input"><?php echo $faqData['date'] ?></span>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="changed"><?php echo $PMF_LANG["ad_entry_changed"]; ?></label>
                        <div class="controls">
                            <textarea name="changed" id="changed" class="span8">
                                <?php echo $faqData['changed'] ?>
                            </textarea>
                        </div>
                    </div>
                </fieldset>
                <!-- Changelog -->
                <?php if (is_numeric($faqData['id'])): ?>
                <fieldset>
                    <legend>
                        <a href="javascript:void(0);" onclick="javascript:toggleFieldset('editChangelogHistory');">
                            <?php echo $PMF_LANG['ad_entry_changelog_history']; ?>
                        </a>
                    </legend>
                    <div id="editChangelogHistory" class="hide">
                        <?php
                        foreach ($faq->getChangeEntries($faqData['id']) as $entry) {
                            $user->getUserById($entry['user']);
                            ?>
                            <p style="font-size: 10px;">
                                <label>
                                    <?php printf('%s  1.%d<br/>%s<br/>%s: %s',
                                    $PMF_LANG['ad_entry_revision'],
                                    $entry['revision_id'],
                                    $date->format(date('Y-m-d H:i', $entry['date'])),
                                    $PMF_LANG['ad_entry_author'],
                                    $user->getUserData('display_name')); ?>
                                </label>
                                <?php echo $entry['changelog']; ?>
                            </p>
                            <?php } ?>
                </fieldset>
                <?php endif; ?>
            </div>

            <!-- sidebar -->
            <div class="well span3">
                <!-- form actions -->
                <fieldset>
                    <div class="control-group">
                        <label class="control-label" for="dateActualize"><?php echo $PMF_LANG["ad_entry_date"]; ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" id="dateActualize" checked="checked" name="recordDateHandling"
                                       onchange="setRecordDate(this.id);" />
                                <?php echo $PMF_LANG['msgUpdateFaqDate']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" id="dateKeep" name="recordDateHandling"
                                       onchange="setRecordDate(this.id);" />
                                <?php echo $PMF_LANG['msgKeepFaqDate']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" id="dateCustomize" name="recordDateHandling"
                                       onchange="setRecordDate(this.id);" />
                                <?php echo $PMF_LANG['msgEditFaqDat']; ?>
                            </label>
                        </div>
                    </div>
                    <div id="recordDateInputContainer" class="control-group hide">
                        <div class="controls">
                            <input type="text" name="date" id="date" maxlength="16" value="" />
                        </div>
                    </div>
                    <?php if ($selectedRevisionId == $faqData['revision_id']): ?>
                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">
                            <?php echo $PMF_LANG["ad_entry_save"]; ?>
                        </button>
                        <button class="btn btn-info" type="reset">
                            <?php echo $PMF_LANG["ad_gen_reset"]; ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </fieldset>
                <!-- categories -->
                <fieldset>
                    <div class="control-group">
                        <label class="control-label"><?php echo $PMF_LANG["ad_entry_category"]; ?></label>
                        <div class="controls">
                            <select name="rubrik[]" id="rubrik" size="5" multiple="multiple" class="input-medium">
                                <?php echo $categoryHelper->renderOptions($categories); ?>
                            </select>
                        </div>
                    </div>
                </fieldset>
                <!-- Activation -->
                <fieldset>
                    <div class="control-group">
                        <label class="control-label" for="active"><?php echo $PMF_LANG["ad_entry_active"]; ?></label>
                        <div class="controls">
                        <?php if($permission['approverec']):
                        if (isset($faqData['active']) && $faqData['active'] == 'yes') {
                            $suf = ' checked="checked"';
                            $sul = null;
                        } elseif ($faqConfig->get('records.defaultActivation')) {
                            $suf = ' checked="checked"';
                            $sul = null;
                        } else {
                            $suf = null;
                            $sul = ' checked="checked"';
                        }
                        ?>
                            <label class="radio">
                                <input type="radio" id="active" name="active"  value="yes"<?php if (isset($suf)) { echo $suf; } ?> />
                                <?php echo $PMF_LANG['ad_gen_yes']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="active"  value="no"<?php if (isset($sul)) { echo $sul; } ?> />
                                <?php echo $PMF_LANG['ad_gen_no']; ?>
                            </label>
                            <?php else: ?>
                            <label class="radio">
                                <input type="radio" name="active"  value="no" checked="checked" />
                                <?php echo $PMF_LANG['ad_gen_no']; ?>
                            </label>
                        <?php endif; ?>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="sticky"><?php echo $PMF_LANG['ad_entry_sticky']; ?>:</label>
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" id="sticky" name="sticky" <?php echo (isset($faqData['sticky']) && $faqData['sticky'] ? 'checked="checked"' : '') ?> />
                                &nbsp;
                            </label>
                        </div>
                    </div>
                    <div class="control-group">
                        <label class="control-label" for="comment">
                            <?php echo $PMF_LANG['ad_entry_allowComments']; ?>
                        </label>
                        <div class="controls">
                            <label class="checkbox">
                                <input type="checkbox" name="comment" id="comment" value="y"<?php echo $faqData['comment'] ?> />
                                <?php echo $PMF_LANG['ad_gen_yes']; ?>
                            </label>
                        </div>
                    </div>
                    <?php if ($queryString != 'insertentry'): ?>
                    <div class="control-group">
                        <label class="control-label" for="revision">
                            <?php echo $PMF_LANG['ad_entry_new_revision']; ?>
                        </label>
                        <?php
                        if ($queryString != 'insertentry') {
                            $rev_yes = ' checked="checked"';
                            $rev_no  = null;
                        }
                        if (isset($faqData['active']) && $faqData['active'] == 'no') {
                            $rev_no  = ' checked="checked"';
                            $rev_yes = null;
                        }
                        ?>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" name="revision" id="revision" value="yes"<?php echo isset($rev_yes) ? $rev_yes : ''; ?>/>
                                <?php echo $PMF_LANG["ad_gen_yes"]; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="revision" value="no"<?php echo isset($rev_no) ? $rev_no : ''; ?>/>
                                <?php echo $PMF_LANG["ad_gen_no"]; ?>
                            </label>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="control-group">
                        <label class="control-label" for="solution_id"><?php echo $PMF_LANG['ad_entry_solution_id']; ?>:</label>
                        <div class="controls">
                            <input name="solution_id" id="solution_id" style="width: 50px; text-align: right;" size="5"
                                   readonly="readonly" value="<?php echo (isset($faqData['solution_id']) ? $faqData['solution_id'] : $faq->getSolutionId()); ?>"  />
                        </div>
                    </div>
                </fieldset>
                <!-- Permissions -->
                <fieldset class="form-inline">
                    <?php if ($faqConfig->get('security.permLevel') != 'basic'): ?>
                    <div class="control-group">
                        <label class="control-label" for="grouppermission"><?php echo $PMF_LANG['ad_entry_grouppermission']; ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" id="grouppermission" name="grouppermission"  value="all" <?php echo ($allGroups ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_all_groups']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="grouppermission"  value="restricted" <?php echo ($restrictedGroups ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_restricted_groups']; ?>
                                <select name="restricted_groups" size="1" class="input-medium">
                                    <?php echo $user->perm->getAllGroupsOptions($groupPermission[0]); ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="grouppermission"  value="all" />
                    <?php endif; ?>
                    <div class="control-group">
                        <label class="control-label" for="userpermission"><?php echo $PMF_LANG['ad_entry_userpermission']; ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" id="userpermission" name="userpermission"  value="all" <?php echo ($allUsers ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_all_users']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" name="userpermission"  value="restricted" <?php echo ($restrictedUsers ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_restricted_users']; ?>
                                <select name="restricted_users" size="1" class="input-medium">
                                    <?php echo $user->getAllUserOptions($userPermission[0]); ?>
                                </select>
                            </label>
                        </div>
                    </div>
                </fieldset>
            </div>
            </form>
        </div>
















<?php

    if ($permission["changebtrevs"]) {

        $revisions = $faq->getRevisionIds($faqData['id'], $faqData['lang']);
        if (count($revisions)) {
?>
            <form id="selectRevision" name="selectRevision" method="post"
                  action="?action=editentry&amp;id=<?php echo $faqData['id'] ?>&amp;lang=<?php echo $faqData['lang'] ?>">
            <fieldset>
                <legend><?php echo $PMF_LANG['ad_changerev']; ?></legend>
                <p>
                    <select name="revisionid_selected" onchange="selectRevision.submit();">
                        <option value="<?php echo $faqData['revision_id']; ?>">
                            <?php echo $PMF_LANG['ad_changerev']; ?>
                        </option>
    <?php foreach ($revisions as $revisionId => $revisionData) { ?>
                        <option value="<?php echo $revisionData['revision_id']; ?>" <?php if ($selectedRevisionId == $revisionData['revision_id']) { echo 'selected="selected"'; } ?> >
                            <?php printf(
                                '%s 1.%d: %s - %s',
                                $PMF_LANG['ad_entry_revision'],
                                $revisionData['revision_id'],
                                PMF_Date::createIsoDate($revisionData['datum']),
                                $revisionData['author']
                            ); ?>
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
            $faqData         = $faq->faqRecord;
            $faqData['tags'] = implode(',', $tagging->getAllTagsById($faqData['id']));
        }
    }
?>
                <?php if ($faqConfig->get('main.enableGoogleTranslation') === true):  ?>
                <fieldset class="form-horizontal">
                    <legend>
                        <a href="javascript:void(0);" onclick="javascript:toggleFieldset('Translations');">
                            <?php echo $PMF_LANG["ad_menu_translations"]; ?>
                        </a>
                    </legend>

                    <div class="control-group hide" id="editTranslations">
                        <?php
                        if ($faqConfig->get('main.googleTranslationKey') == '') {
                            echo $PMF_LANG["msgNoGoogleApiKeyFound"];
                        } else {
                            ?>
                            <label class="control-label" for="langTo"><?php echo $PMF_LANG["ad_entry_locale"]; ?>:</label>
                            <div class="controls">
                                <?php echo PMF_Language::selectLanguages($faqData['lang'], false, array(), 'langTo'); ?>
                            </div>

                            <input type="hidden" name="used_translated_languages" id="used_translated_languages" value="" />
                            <div id="getedTranslations">
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </fieldset>
                <?php endif; ?>


    
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
     * @param fieldset ID of the fieldset
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
            $('#date').val('<?php echo $faqData['date']; ?>');
        } else if('dateCustomize' == how) {
            showIDContainer(true);
            $('#date').val('');
        }
    }
        
    /**
     * Shows help for keywords and tags input fields
     *
     * @param option
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
        if ($faqConfig->get('main.enableGoogleTranslation') === true) {
?>        
    <script src="https://www.google.com/jsapi?key=<?php echo $faqConfig->get('main.googleTranslationKey')?>" type="text/javascript"></script>
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
                    .append(
                        '<div class="control-group">' +
                        '<label class="control-label" for="question_translated_' + langTo + '">' +
                        '<?php echo $PMF_LANG["ad_entry_theme"]; ?>' +
                        '</label>' +
                        '<div class="controls">' +
                        '<input type="text" id="question_translated_' + langTo + '" name="question_translated_' + langTo + '" maxlength="255" >' +
                        '</div>' +
                        '</div>'
                    );


                // Textarea for answer
                fieldset
                    .append(
                        '<div class="control-group">' +
                        '<label class="control-label" for="answer_translated_' + langTo + '">' +
                        '<?php echo $PMF_LANG["ad_entry_content"]; ?>' +
                        '</label>' +
                        '<div class="controls">' +
                        '<textarea id="answer_translated_' + langTo + '" name="answer_translated_' + langTo + '" cols="80" rows="3" ></textarea>' +
                        '</div>' +
                        '</div>'
                    );


                // Textarea for keywords
                fieldset
                    .append(
                        '<div class="control-group">' +
                        '<label class="control-label" for="keywords_translated_' + langTo + '">' +
                        '<?php echo $PMF_LANG["ad_entry_keywords"]; ?>' +
                        '</label>' +
                        '<div class="controls">' +
                        '<textarea id="keywords_translated_' + langTo + '" name="keywords_translated_' + langTo + '" cols="80" rows="3" ></textarea>' +
                        '</div>' +
                        '</div>'
                    );

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
     * @param field  id of the input to fill.
     *
     * @return void
     */
    function createTinyMCE(field)
    {
        tinyMCE.init({
            // General options
            mode     : "exact",
            language : "<?php echo (PMF_Language::isASupportedTinyMCELanguage($LANGCODE) ? $LANGCODE : 'en'); ?>",
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
            content_css : "../assets/template/<?php echo PMF_Template::getTplSetName(); ?>/css/style.css",
                
            // Drop lists for link/image/media/template dialogs
            template_external_list_url : "js/template_list.js",
                
            // Replace values for the template plugin
            template_replace_values : {
                username : "<?php echo $user->userdata->get('display_name'); ?>",
                user_id  : "<?php echo $user->userdata->get('user_id'); ?>"
            }
        });
    }    
    /* ]]> */
    </script>
<?php
    }
} elseif ($permission["editbt"] != 1 && !PMF_Db::checkOnEmptyTable('faqcategories')) {
    echo $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && PMF_Db::checkOnEmptyTable('faqcategories')) {
    echo $PMF_LANG["no_cats"];
}
