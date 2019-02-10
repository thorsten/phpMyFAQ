<?php
/**
 * Delete open questions.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-pencil"></i> <?php echo $PMF_LANG['msgOpenQuestions'] ?>
                </h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'delquestion')) {
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $date = new PMF_Date($faqConfig);
    $questionId = PMF_Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    $toggle = PMF_Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_STRING);
    if ($toggle == 'toggle') {
        $is_visible = $faq->getVisibilityOfQuestion($questionId);
        if (!is_null($is_visible)) {
            $faq->setVisibilityOfQuestion($questionId, ($is_visible == 'N' ? 'Y' : 'N'));
        }
    }

    echo '<div id="returnMessage"></div>';

    $openquestions = $faq->getAllOpenQuestions();

    if (count($openquestions) > 0) {
        ?>
            <form id="questionSelection" name="questionSelection" method="post" accept-charset="utf-8">
                <input type="hidden" name="csrf" value="<?php print $user->getCsrfTokenFromSession() ?>">
                <table class="table table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th><?php echo $PMF_LANG['ad_entry_author'] ?></th>
                        <th><?php echo $PMF_LANG['ad_entry_theme'] ?></th>
                        <th colspan="2"><?php echo $PMF_LANG['ad_entry_visibility'] ?>?</th>
                    </tr>
                </thead>
                <tbody>
<?php
        foreach ($openquestions as $question) {
            ?>
                    <tr>
                        <td>
                            <label>
                            <input id="questions[]"
                                   name="questions[]"
                                   value="<?php echo $question['id'] ?>" type="checkbox">
                            </label>
                        </td>
                        <td>
                            <?php echo $date->format(PMF_Date::createIsoDate($question['created'])) ?>
                            <br>
                            <a href="mailto:<?php echo $question['email'] ?>">
                                <?php echo $question['username'] ?>
                            </a>
                        </td>
                        <td>
                            <strong><?php echo $category->categoryName[$question['category_id']]['name'] ?></strong>
                            <br>
                            <?php echo $question['question'] ?>
                        </td>
                        <td>
                            <a href="?action=question&amp;id=<?php echo $question['id'] ?>&amp;is_visible=toggle"
                               class="btn btn-info">
                                <?php echo ('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_yes'] : $PMF_LANG['ad_gen_no'] ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($faqConfig->get('records.enableCloseQuestion') && $question['answer_id']) { ?>
                            <a href="?action=editentry&amp;id=<?php echo $question['answer_id'] ?>&amp;lang=<?php echo $LANGCODE ?>"
                               class="btn btn-success">
                                <?php echo $PMF_LANG['msg2answerFAQ'] ?>
                            </a>
                            <?php 
} else {
    ?>
                            <a href="?action=takequestion&amp;id=<?php echo $question['id'] ?>" class="btn btn-success">
                                <?php echo $PMF_LANG['ad_ques_take'] ?>
                            </a>
                            <?php 
}
            ?>

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
                        <?php echo $PMF_LANG['ad_entry_delete'] ?>
                    </button>
                </p>

                <script src="assets/js/record.js"></script>
<?php

    } else {
        echo $PMF_LANG['msgNoQuestionsAvailable'];
    }
} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>
