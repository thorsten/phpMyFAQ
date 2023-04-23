<?php

/**
 * Delete open questions.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

use phpMyFAQ\Category;
use phpMyFAQ\Date;
use phpMyFAQ\Filter;
use phpMyFAQ\Question;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-question-circle-o"></i>
      <?= Translation::get('msgOpenQuestions') ?>
  </h1>
</div>

<div class="row">
  <div class="col-lg-12">
      <?php
      if ($user->perm->hasPermission($user->getUserId(), 'delquestion')) {
          $category = new Category($faqConfig, [], false);
          $question = new Question($faqConfig);
          $category->setUser($currentAdminUser);
          $category->setGroups($currentAdminGroups);
          $date = new Date($faqConfig);
          $questionId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
          $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

          if ($csrfToken && Token::getInstance()->verifyToken('toggle-question-visibility', $csrfToken)) {
              $csrfChecked = true;
          } else {
              $csrfChecked = false;
          }

          $toggle = Filter::filterInput(INPUT_GET, 'is_visible', FILTER_SANITIZE_SPECIAL_CHARS);
          if ($csrfChecked && $toggle === 'toggle') {
              $isVisible = $question->getVisibility($questionId);
              $question->setVisibility($questionId, ($isVisible == 'N' ? 'Y' : 'N'));
          }

            echo '<div id="returnMessage"></div>';

            $openQuestions = $question->getAllOpenQuestions();

            if ((is_countable($openQuestions) ? count($openQuestions) : 0) > 0) {
                ?>
            <form id="phpmyfaq-open-questions" name="phpmyfaq-open-questions" method="post" accept-charset="utf-8">
              <?= Token::getInstance()->getTokenInput('delete-questions') ?>
              <table class="table table-striped align-middle">
                <thead>
                <tr>
                  <th></th>
                  <th><?= Translation::get('ad_entry_author') ?></th>
                  <th><?= Translation::get('ad_entry_theme') ?></th>
                  <th colspan="2"><?= Translation::get('ad_entry_visibility') ?>?</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($openQuestions as $openQuestion) {
                    ?>
                  <tr>
                    <td>
                      <label>
                        <input id="questions[]" name="questions[]" value="<?= $openQuestion->getId() ?>" type="checkbox">
                      </label>
                    </td>
                    <td>
                        <?= $date->format(Date::createIsoDate($openQuestion->getCreated())) ?>
                      <br>
                      <a href="mailto:<?= Strings::htmlentities($openQuestion->getEmail()) ?>">
                          <?= Strings::htmlentities($openQuestion->getUsername()) ?>
                      </a>
                    </td>
                    <td>
                      <strong>
                          <?= Strings::htmlentities($category->categoryName[$openQuestion->getCategoryId()]['name']) ?>
                      </strong>
                      <br>
                        <?= Strings::htmlentities($openQuestion->getQuestion()) ?>
                    </td>
                    <td>
                      <a href="?action=question&amp;id=<?= $openQuestion->getId() ?>&amp;is_visible=toggle&csrf=<?= Token::getInstance()->getTokenString('toggle-question-visibility') ?>"
                         class="btn btn-info">
                          <?= ('Y' === $openQuestion->isVisible()) ? Translation::get('ad_gen_yes') : Translation::get('ad_gen_no') ?>
                      </a>
                    </td>
                    <td>
                        <?php if ($faqConfig->get('records.enableCloseQuestion') && $openQuestion->getAnswerId()) { ?>
                        <a href="?action=editentry&amp;id=<?= $openQuestion->getAnswerId() ?>&amp;lang=<?= $faqLangCode ?>"
                           class="btn btn-success">
                            <?= Translation::get('msg2answerFAQ') ?>
                        </a>
                        <?php } else { ?>
                        <a href="?action=takequestion&amp;id=<?= $openQuestion->getId() ?>" class="btn btn-success">
                            <?= Translation::get('ad_ques_take') ?>
                        </a>
                        <?php } ?>
                    </td>
                  </tr>
                <?php } ?>
                </tbody>
              </table>

              <div class="text-end">
                <button class="btn btn-danger" id="pmf-delete-questions" type="button">
                    <?= Translation::get('ad_entry_delete') ?>
                </button>
              </div>

            </form>
                <?php
            } else {
                echo Translation::get('msgNoQuestionsAvailable');
            }
        } else {
            echo Translation::get('err_NotAuth');
        }
        ?>
  </div>
</div>
