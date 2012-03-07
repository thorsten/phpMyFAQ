<?php
/**
 * Delete open questions
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
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['delquestion']) {

    $category   = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $questionId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $delete     = PMF_Filter::filterInput(INPUT_GET, 'delete', FILTER_SANITIZE_STRING, 'no');
    
    $toggle = PMF_Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_STRING);
    if ($toggle == 'toggle') {
        $is_visible = $faq->getVisibilityOfQuestion($questionId);
        if (!is_null($is_visible)) {
            $faq->setVisibilityOfQuestion($questionId, ($is_visible == 'N' ? 'Y' : 'N'));
        }
    }

    printf("<header><h2>%s</h2></header>", $PMF_LANG['msgOpenQuestions']);

    if ($delete == 'yes') {
        $faq->deleteQuestion($questionId);
        printf('<p class="alert alert-success">%s</p>', $PMF_LANG['ad_entry_delsuc']);
    }

    $openquestions = $faq->getAllOpenQuestions();

    if (count($openquestions) > 0) {
?>
        <table class="table table-striped">
        <thead>
            <tr>
                <th><?php print $PMF_LANG['ad_entry_author']; ?></th>
                <th><?php print $PMF_LANG['ad_entry_theme']; ?></th>
                <th><?php print $PMF_LANG['ad_entry_visibility']; ?>?</th>
                <th><?php print $PMF_LANG['ad_gen_delete']; ?>?</th>
            </tr>
        </thead>
        <tbody>
<?php
        foreach ($openquestions as $question) {
?>
        <tr>
            <td>
                <?php print PMF_Date::format(PMF_Date::createIsoDate($question['created'])); ?>
                <br />
                <a href="mailto:<?php print $question['email']; ?>">
                    <?php print $question['username']; ?>
                </a>
            </td>
            <td>
                <div id="PMF_openQuestionsCategory"><?php print $category->categoryName[$question['category_id']]['name'] ?></div>
                <br />
                <?php print $question['question'] ?>
            </td>
            <td>
                <a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;is_visible=toggle">
                    <?php print (('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_no'] : $PMF_LANG['ad_gen_yes']); ?>
                </a>
            </td>
            <td>
                <a onclick="return confirm('<?php print $PMF_LANG['ad_user_del_3'] ?>'); return false;" href="?action=question&amp;id=<?php print $question['id']; ?>&amp;delete=yes">
                    <?php print $PMF_LANG['ad_gen_delete']; ?>
                </a>
                <br />
                <?php if ($faqConfig->get('records.enableCloseQuestion') && $question['answer_id']) { ?>
                <a href="?action=editentry&amp;id=<?php print $question['answer_id']; ?>&amp;lang=<?php print $LANGCODE; ?>">
                    <?php print $PMF_LANG['msg2answerFAQ']; ?>
                </a>
                <?php } else { ?>
                <a href="?action=takequestion&amp;id=<?php print $question['id']; ?>">
                    <?php print $PMF_LANG['ad_ques_take']; ?>
                </a>
                <?php } ?>

            </td>
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
} else {
    print $PMF_LANG['err_NotAuth'];
}