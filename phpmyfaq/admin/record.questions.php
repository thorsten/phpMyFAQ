<?php
/**
 * Delete open questions
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
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf("<header><h2>%s</h2></header>", $PMF_LANG['msgOpenQuestions']);

if ($permission['delquestion']) {

    $category = new PMF_Category($faqConfig, false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $date       = new PMF_Date($faqConfig);
    $questionId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    $toggle = PMF_Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_STRING);
    if ($toggle == 'toggle') {
        $is_visible = $faq->getVisibilityOfQuestion($questionId);
        if (!is_null($is_visible)) {
            $faq->setVisibilityOfQuestion($questionId, ($is_visible == 'N' ? 'Y' : 'N'));
        }
    }

    print '<div id="returnMessage"></div>';

    $openquestions = $faq->getAllOpenQuestions();

    if (count($openquestions) > 0) {
?>
    <form id="questionSelection" name="questionSelection" method="post">
    <table class="table table-striped">
    <thead>
        <tr>
            <th></th>
            <th><?php print $PMF_LANG['ad_entry_author']; ?></th>
            <th><?php print $PMF_LANG['ad_entry_theme']; ?></th>
            <th colspan="2"><?php print $PMF_LANG['ad_entry_visibility']; ?>?</th>
        </tr>
    </thead>
    <tbody>
<?php
        foreach ($openquestions as $question) {
?>
        <tr>
            <td>
                <input id="questions[]"
                       name="questions[]"
                       value="<?php print $question['id']; ?>" type="checkbox" />
            </td>
            <td>
                <?php print $date->format(PMF_Date::createIsoDate($question['created'])); ?>
                <br />
                <a href="mailto:<?php print $question['email']; ?>">
                    <?php print $question['username']; ?>
                </a>
            </td>
            <td>
                <strong><?php print $category->categoryName[$question['category_id']]['name'] ?></strong>
                <br />
                <?php print $question['question'] ?>
            </td>
            <td>
                <a href="?action=question&amp;id=<?php print $question['id']; ?>&amp;is_visible=toggle"
                   class="btn btn-info">
                    <?php print ('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_no'] : $PMF_LANG['ad_gen_yes']; ?>
                </a>
            </td>
            <td>
                <?php if ($faqConfig->get('records.enableCloseQuestion') && $question['answer_id']) { ?>
                <a href="?action=editentry&amp;id=<?php print $question['answer_id']; ?>&amp;lang=<?php print $LANGCODE; ?>"
                   class="btn btn-success">
                    <?php print $PMF_LANG['msg2answerFAQ']; ?>
                </a>
                <?php } else { ?>
                <a href="?action=takequestion&amp;id=<?php print $question['id']; ?>" class="btn btn-success">
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
    </form>

    <p>
        <button class="btn btn-danger" id="submitDeleteQuestions" type="submit">
            <?php print $PMF_LANG["ad_entry_delete"]; ?>
        </button>
    </p>

    <script type="text/javascript">
        /* <![CDATA[ */
        $('#submitDeleteQuestions').click(function() { deleteQuestions(); return false; });

        function deleteQuestions()
        {
            var questions = $('#questionSelection').serialize();

            $('#returnMessage').empty();
            $.ajax({
                type: 'POST',
                url:  'index.php?action=ajax&ajax=records&ajaxaction=delete_question',
                data: questions,
                success: function(msg) {
                    $('#saving_data_indicator').html('<img src="images/indicator.gif" /> deleting ...');
                    $('tr td input:checked').parent().parent().fadeOut('slow');
                    $('#saving_data_indicator').fadeOut('slow');
                    $('#returnMessage').
                            html('<p class="alert alert-success">' + msg + '</p>');
                }
            });
            return false;
        }

        /* ]]> */
    </script>
<?php
    } else {
        print $PMF_LANG['msgNoQuestionsAvailable'];
    }
} else {
    print $PMF_LANG['err_NotAuth'];
}