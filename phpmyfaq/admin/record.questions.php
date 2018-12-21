<?php
/**
 * Delete open questions.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Category;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}
?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">
            <i aria-hidden="true" class="fas fa-pen"></i>
              <?= $PMF_LANG['msgOpenQuestions'] ?>
          </h1>
        </div>

        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'delquestion')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $date = new Date($faqConfig);
    $questionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    $toggle = Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_STRING);
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
                <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
                <table class="table table-striped">
                <thead>
                    <tr>
                        <th></th>
                        <th><?= $PMF_LANG['ad_entry_author'] ?></th>
                        <th><?= $PMF_LANG['ad_entry_theme'] ?></th>
                        <th colspan="2"><?= $PMF_LANG['ad_entry_visibility'] ?>?</th>
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
                                   value="<?= $question['id'] ?>" type="checkbox">
                            </label>
                        </td>
                        <td>
                            <?= $date->format(Date::createIsoDate($question['created'])) ?>
                            <br>
                            <a href="mailto:<?= $question['email'] ?>">
                                <?= $question['username'] ?>
                            </a>
                        </td>
                        <td>
                            <strong><?= $category->categoryName[$question['category_id']]['name'] ?></strong>
                            <br>
                            <?= $question['question'] ?>
                        </td>
                        <td>
                            <a href="?action=question&amp;id=<?= $question['id'] ?>&amp;is_visible=toggle"
                               class="btn btn-info">
                                <?= ('Y' == $question['is_visible']) ? $PMF_LANG['ad_gen_yes'] : $PMF_LANG['ad_gen_no'] ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($faqConfig->get('records.enableCloseQuestion') && $question['answer_id']) { ?>
                            <a href="?action=editentry&amp;id=<?= $question['answer_id'] ?>&amp;lang=<?= $LANGCODE ?>"
                               class="btn btn-success">
                                <?= $PMF_LANG['msg2answerFAQ'] ?>
                            </a>
                            <?php 
} else {
    ?>
                            <a href="?action=takequestion&amp;id=<?= $question['id'] ?>" class="btn btn-success">
                                <?= $PMF_LANG['ad_ques_take'] ?>
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
                        <?= $PMF_LANG['ad_entry_delete'] ?>
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
