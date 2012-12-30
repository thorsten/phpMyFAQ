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
 * @copyright 2003-2013 phpMyFAQ Team
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

    $category = new PMF_Category($faqConfig, array(), false);
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
        $selectedCategory = PMF_Filter::filterInputArray(
            INPUT_POST,
            array(
                'rubrik' => array(
                    'filter' => FILTER_VALIDATE_INT,
                    'flags'  => FILTER_REQUIRE_ARRAY
                )
            )
        );
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
                            <textarea name="question" id="question" class="admin-question span11" rows="2"
                                      placeholder="<?php echo $PMF_LANG['ad_entry_theme']; ?>"><?php
                                echo $faqData['title'] ?></textarea>
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
                    <div class="control-group">
                        <label class="control-label" for="lang"><?php echo $PMF_LANG["ad_entry_locale"]; ?>:</label>
                        <div class="controls">
                            <?php echo PMF_Language::selectLanguages($faqData['lang'], false, array(), 'lang'); ?>
                        </div>
                     </div>
                </fieldset>
                <fieldset class="form-horizontal">
                    <!-- Attachments -->
                    <?php if ($permission['addattachment']): ?>
                    <div class="control-group">
                        <label class="control-label"><?php echo $PMF_LANG['ad_menu_attachments'] ?>:</label>
                        <div class="controls">
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
                                if (0 === $faqData['id']) {
                                    $faqData['id'] = $faqConfig->getDb()->nextId(
                                        PMF_Db::getTablePrefix() . 'faqdata',
                                        'id'
                                    );
                                }
                                printf(
                                    '<a class="btn btn-success" onclick="addAttachment(\'attachment.php?record_id=%d&amp;record_lang=%s\', \'Attachment\');">%s</a>',
                                    $faqData['id'],
                                    $faqData['lang'],
                                    $PMF_LANG['ad_att_add']
                                );
                            ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Tags -->
                    <div class="control-group">
                        <label class="control-label" for="tags"><?php echo $PMF_LANG['ad_entry_tags']; ?>:</label>
                        <div class="controls">
                            <input type="text" name="tags" id="tags" value="<?php echo $faqData['tags'] ?>"
                                   data-provide="typeahead" data-mode="multiple" />
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
                            <select name="rubrik[]" id="phpmyfaq-categories" size="5" multiple="multiple"
                                    class="input-medium">
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
                                <input type="radio" id="allgroups" name="grouppermission" value="all" <?php echo ($allGroups ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_all_groups']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" id="restrictedgroups" name="grouppermission" value="restricted" <?php echo ($restrictedGroups ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_restricted_groups']; ?>
                                <select name="restricted_groups[]" size="3" class="input-medium selected-groups" multiple>
                                    <?php echo $user->perm->getAllGroupsOptions($groupPermission); ?>
                                </select>
                            </label>
                        </div>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="grouppermission" value="all" />
                    <?php endif; ?>
                    <div class="control-group">
                        <label class="control-label" for="userpermission"><?php echo $PMF_LANG['ad_entry_userpermission']; ?></label>
                        <div class="controls">
                            <label class="radio">
                                <input type="radio" id="allusers" name="userpermission" value="all" <?php echo ($allUsers ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_all_users']; ?>
                            </label>
                            <label class="radio">
                                <input type="radio" id="restrictedusers" name="userpermission" value="restricted" <?php echo ($restrictedUsers ? 'checked="checked"' : ''); ?>/>
                                <?php echo $PMF_LANG['ad_entry_restricted_users']; ?>
                                <select name="restricted_users" size="1" class="input-medium selected-groups">
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


    
    <script type="text/javascript">
    /* <![CDATA[ */

    function extractor(query) {
        var result = /([^,]+)$/.exec(query);
        if(result && result[1])
            return result[1].trim();
        return '';
    }

    $('#tags').typeahead({
        source: function (query, process) {
            return $.get("index.php?action=ajax&ajax=tags_list", { q: query }, function (data) {
                return process(data.tags);
            });
        },
        updater: function(item) {
            return this.$element.val().replace(/[^,]*$/,'')+item+',';
        },
        matcher: function (item) {
            var tquery = extractor(this.query);
            if(!tquery) return false;
            return ~item.toLowerCase().indexOf(tquery)
        },
        highlighter: function (item) {
            var query = extractor(this.query).replace(/[\-\[\]{}()*+?.,\\\^$|#\s]/g, '\\$&')
            return item.replace(new RegExp('(' + query + ')', 'ig'), function ($1, match) {
                return '<strong>' + match + '</strong>'
            })
        }
    });

    $(function() {
        // DatePicker
        $('.date-pick').datePicker();
        $('#date').datePicker({startDate: '1900-01-01'});
        $('#date').bind('dateSelected', function (e, date, $td, status) {
            if (status) {
                var dt = new Date();
                var hours   = dt.getHours();
                var minutes = dt.getMinutes();
                
                $('#date').val(
                    date.asString() + ' ' + (hours < 10 ? '0' : '') + hours + ':' + (minutes < 10 ? '0' : '') + minutes
                );
            }
        });

        // Show help for keywords and users
        $('#keywords').focus(function() { showHelp('keywords'); });
        $('#tags').focus(function() { showHelp('tags'); });

        // Override FAQ permissions with Category permission to avoid confused users
        $('#phpmyfaq-categories').click(function() {
            var categories = $('#phpmyfaq-categories option:selected').map(function() {
                return $(this).val();
            }).get();

            $.ajax({
                type: 'POST',
                url:  'index.php?action=ajax&ajax=categories&ajaxaction=getpermissions',
                data: "categories=" + categories,
                success: function(permissions) {
                    var perms = jQuery.parseJSON(permissions);

                    if ("-1" === perms.user[0]) {
                        $('#restrictedusers').removeAttr("checked").attr("disabled", "disabled");
                        $('#allusers').attr("checked","checked");
                    } else {
                        $('#allusers').removeAttr("checked").attr("disabled", "disabled");
                        $('#restrictedusers').attr("checked","checked");
                        $.each(perms.user, function(key, value) {
                                $(".selected-users option[value='" + value + "']").attr('selected',true);
                        });
                    }
                    if ("-1" === perms.group[0]) {
                        $('#restrictedgroups').removeAttr("checked").attr("disabled", "disabled");
                        $('#allgroups').attr("checked","checked");
                    } else {
                        $('#allgroups').removeAttr("checked").attr("disabled", "disabled");
                        $('#restrictedgroups').attr("checked","checked");
                        $.each(perms.group, function(key, value) {
                            $(".selected-groups option[value='" + value + "']").attr('selected',true);
                        });
                    }
                }
            });
        });
    });


    function toggleFieldset(fieldset) {
        if ($('#edit' + fieldset).css('display') == 'none') {
            $('#edit' + fieldset).fadeIn('fast');
        } else {
            $('#edit' + fieldset).fadeOut('fast');
        }
    }

    function showIDContainer() {
        var display = 0 == arguments.length || !!arguments[0] ? 'block' : 'none';
        $('#recordDateInputContainer').attr('style', 'display: ' + display);
    }

    function setRecordDate(how) {
        if ('dateActualize' === how) {
            showIDContainer(false);
            $('#date').val('');
        } else if ('dateKeep' === how) {
            showIDContainer(false);
            $('#date').val('<?php echo $faqData['date']; ?>');
        } else if ('dateCustomize' === how) {
            showIDContainer(true);
            $('#date').val('');
        }
    }

    function showHelp(option) {
        $('#' + option + 'Help').fadeIn(500);
        $('#' + option + 'Help').fadeOut(2500);
    }
    /* ]]> */
    </script>
<?php
} elseif ($permission["editbt"] != 1 && !PMF_Db::checkOnEmptyTable('faqcategories')) {
    echo $PMF_LANG["err_NotAuth"];
} elseif ($permission["editbt"] && PMF_Db::checkOnEmptyTable('faqcategories')) {
    echo $PMF_LANG["no_cats"];
}
