<?php

/**
 * Shows all comments in the categories and provides a link to delete comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-04
 */

use phpMyFAQ\Category;
use phpMyFAQ\Comments;
use phpMyFAQ\Date;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Faq;
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
      <i aria-hidden="true" class="fa fa-comments-o"></i>
        <?= Translation::get('ad_comment_administration') ?>
    </h1>
  </div>
<?php

echo '<div id="returnMessage"></div>';

if ($user->perm->hasPermission($user->getUserId(), 'delcomment')) {
    $comment = new Comments($faqConfig);
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $faq = new Faq($faqConfig);
    $date = new Date($faqConfig);

    $category->buildCategoryTree();
    $faqComments = $comment->getAllComments(CommentType::FAQ);

    if (count($faqComments)) {
?>
      <div class="row mt-2">
          <h4><?= Translation::get('ad_comment_faqs') ?></h4>
          <form id="pmf-comments-selected-faq" name="pmf-comments-selected-faq" method="post" accept-charset="utf-8">
            <input type="hidden" name="ajax" value="comment">
            <input type="hidden" name="ajaxaction" value="delete">
            <?= Token::getInstance()->getTokenInput('delete-comment') ?>
            <table class="table table-striped align-middle">
                <?php
                $lastCommentId = 0;
                foreach ($faqComments as $faqComment) {
                    if ($faqComment->getId() === $lastCommentId) {
                        continue;
                    }
                    ?>
                  <tr id="comments_<?= $faqComment->getId() ?>">
                    <td>
                      <label>
                        <input type="checkbox" class="form-check-input" id="comments[]" name="comments[]"
                               value="<?= $faqComment->getId() ?>">
                      </label>
                    </td>
                    <td>
                    <span style="font-weight: bold;">
                        <a href="mailto:<?= $faqComment->getEmail() ?>">
                            <?= Strings::htmlentities($faqComment->getUsername()) ?>
                        </a> |
                        <?= $date->format(date('Y-m-d H:i', $faqComment->getDate())) ?> |
                        <a href="<?php printf(
                            '../?action=faq&cat=%d&id=%d&artlang=%s',
                            $faqComment->getCategoryId(),
                            $faqComment->getRecordId(),
                            $faqLangCode
                                 ) ?>">
                            <?= $faq->getRecordTitle($faqComment->getRecordId()) ?>
                        </a>
                    </span><br>
                        <?= Strings::htmlentities($faqComment->getComment()) ?>
                    </td>
                  </tr>
                    <?php
                    $lastCommentId = $faqComment->getId();
                }
                ?>
            </table>
            <div class="text-end">
              <button class="btn btn-danger" id="pmf-button-delete-faq-comments" type="button">
                  <?= Translation::get('ad_entry_delete') ?>
              </button>
            </div>
          </form>
      </div>
        <?php
    } else {
        echo '<p><strong>n/a</strong></p>';
    }

    $newsComments = $comment->getAllComments(CommentType::NEWS);
    if (count($newsComments)) {
?>
        <div class="row mt-2">
            <h4><?= Translation::get('ad_comment_news') ?></h4>
          <form id="pmf-comments-selected-news" name="pmf-comments-selected-news" method="post" accept-charset="utf-8">
            <input type="hidden" name="ajax" value="comment">
            <input type="hidden" name="ajaxaction" value="delete">
            <?= Token::getInstance()->getTokenInput('delete-comment') ?>
            <table class="table table-striped align-middle">
                <?php
                foreach ($newsComments as $newsComment) { ?>
                  <tr id="comments_<?= $newsComment->getId() ?>">
                    <td>
                      <label>
                        <input type="checkbox" class="form-check-input" id="comments[]" name="comments[]"
                               value="<?= $newsComment->getId() ?>">
                      </label>
                    </td>
                    <td>
                    <span style="font-weight: bold;">
                        <a href="mailto:<?= $newsComment->getEmail() ?>">
                            <?= Strings::htmlentities($newsComment->getUsername()) ?>
                        </a> |
                        <?= $date->format(date('Y-m-d H:i', $newsComment->getDate())) ?> |
                        <a href="<?php printf('../?action=news&newsid=%d&artlang=%s', $newsComment->getRecordId(), $faqLangCode) ?>">
                            <i class="fa fa-newspaper-o" aria-hidden="true"></i>
                        </a>
                    </span><br/>
                        <?= $newsComment->getComment() ?>
                    </td>
                  </tr>
                    <?php
                }
                ?>
            </table>
            <div class="text-end">
              <button class="btn btn-danger" id="pmf-button-delete-news-comments" type="button">
                  <?= Translation::get('ad_entry_delete') ?>
              </button>
            </div>
          </form>
        </div>
        <?php
    } else {
        echo '<p><strong>n/a</strong></p>';
    }
} else {
    echo Translation::get('err_NotAuth');
}
