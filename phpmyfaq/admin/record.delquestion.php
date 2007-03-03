<?php
/**
 * $Id: record.delquestion.php,v 1.18 2007-03-03 11:06:28 thorstenr Exp $
 *
 * Delete open questions
 *
 * @author       Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since        2003-02-24
 * @copyright    (c) 2003-2007 phpMyFAQ Team
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
 */

if (!defined('IS_VALID_PHPMYFAQ_ADMIN')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['delquestion']) {

    $category = new PMF_Category($LANGCODE, $current_admin_user, $current_admin_groups, false);

    if (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
        $question_id = (int)$_GET['id'];
        $faq->deleteQuestion($question_id);
        print $PMF_LANG['ad_entry_delsuc'];
    } else {
        if (isset($_GET['is_visible']) && $_GET['is_visible'] == 'toggle') {
            $is_visible = $faq->getVisibilityOfQuestion($question_id);
            if (!is_null($is_visible)) {
                $faq->setVisibilityOfQuestion($question_id, $is_visible);
            }
        }

        printf("<h2>%s</h2>", $PMF_LANG['msgOpenQuestions']);

        $openquestions = $faq->getAllOpenQuestions();

        if (count($openquestions) > 0) {
?>
    <table class="list">
    <thead>
        <tr>
            <th class="list"><?php print $PMF_LANG['ad_entry_author']; ?></th>
            <th class="list"><?php print $PMF_LANG['ad_entry_theme']; ?></th>
            <th class="list"><?php print $PMF_LANG['ad_entry_visibility']; ?>?</th>
            <th class="list"><?php print $PMF_LANG['ad_gen_delete']; ?>?</th>
        </tr>
    </thead>
    <tbody>
<?php
            foreach ($openquestions as $question) {
?>
        <tr>
            <td class="list"><?php print makeDate($question['ask_date']); ?><br /><a href="mailto:<?php print $question['ask_usermail']; ?>"><?php print $question['ask_username']; ?></a></td>
            <td class="list"><?php print $category->categoryName[$question['ask_rubrik']]['name'].":<br />".$question['ask_content']; ?></td>
            <td class="list"><a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;is_visible=toggle"><?php print (('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_no'] : $PMF_LANG['ad_gen_yes']); ?>!</a><br /></td>
            <td class="list"><a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;delete=yes"><?php print $PMF_LANG['ad_gen_delete']; ?>!</a><br /><a href="?action=takequestion&amp;id=<?php print $question['id']; ?>"><?php print $PMF_LANG['ad_ques_take']; ?></a></td>
        </tr>
<?php
            }
?>
    </tbody>
    </table>
<?php
        } else {
            print $PMF_LANG['msgNoQuestionsAvailable'];
        }
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}