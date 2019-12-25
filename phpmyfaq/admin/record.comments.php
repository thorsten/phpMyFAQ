<?php
/**
 * Shows all comments in the categories and provides a link to delete comments.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2007-03-04
 */

use phpMyFAQ\Category;
use phpMyFAQ\Comment;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
  <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
      <i aria-hidden="true" class="fa fa-comments-o"></i>
        <?= $PMF_LANG['ad_comment_administration'] ?>
    </h1>
  </div>
<?php

echo '<div id="returnMessage"></div>';

if ($user->perm->checkRight($user->getUserId(), 'delcomment')) {
    $comment = new Comment($faqConfig);
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $faq = new Faq($faqConfig);
    $date = new Date($faqConfig);

    $category->buildTree();
    $faqComments = $comment->getAllComments('faq');

    printf("<header><h3>%s</h3></header>\n", $PMF_LANG['ad_comment_faqs']);
    if (count($faqComments)) {
        ?>
      <form id="faqCommentSelection" name="faqCommentSelection" method="post" accept-charset="utf-8">
        <input type="hidden" name="ajax" value="comment"/>
        <input type="hidden" name="ajaxaction" value="delete"/>
        <table class="table table-striped">
            <?php
            $lastCommentId = 0;
            foreach ($faqComments as $faqComment) {
                if ($faqComment['comment_id'] == $lastCommentId) {
                    continue;
                }
                ?>
              <tr id="comments_<?= $faqComment['comment_id'] ?>">
                <td>
                  <label>
                    <input id="faq_comments[<?= $faqComment['comment_id'] ?>]"
                           name="faq_comments[<?= $faqComment['comment_id'] ?>]"
                           value="<?= $faqComment['record_id'] ?>" type="checkbox">
                  </label>
                </td>
                <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?= $faqComment['email'] ?>">
                        <?= $faqComment['username'] ?>
                    </a> |
                    <?= $date->format(date('Y-m-d H:i', $faqComment['date'])) ?> |
                    <a href="<?php printf('../?action=faq&cat=%d&id=%d&artlang=%s',
                        $faqComment['category_id'],
                        $faqComment['record_id'],
                        $faqLangCode) ?>">
                        <?= $faq->getRecordTitle($faqComment['record_id']) ?>
                    </a>
                </span><br/>
                    <?= Strings::htmlspecialchars($faqComment['content']) ?>
                </td>
              </tr>
                <?php
                $lastCommentId = $faqComment['comment_id'];
            }
            ?>
        </table>
        <div class="text-right">
          <button class="btn btn-danger" id="submitFaqComments" type="submit" name="submit">
              <?= $PMF_LANG['ad_entry_delete'] ?>
          </button>
        </div>
      </form>
        <?php

    } else {
        echo '<p><strong>n/a</strong></p>';
    }

    $newsComments = $comment->getAllComments('news');

    printf("<header><h3>%s</h3></header>\n", $PMF_LANG['ad_comment_news']);
    if (count($newsComments)) {
        ?>
      <form id="newsCommentSelection" name="newsCommentSelection" method="post" accept-charset="utf-8">
        <input type="hidden" name="ajax" value="comment"/>
        <input type="hidden" name="ajaxaction" value="delete"/>
        <table class="table table-striped">
            <?php
            foreach ($newsComments as $newsComment) { ?>
              <tr id="comments_<?= $newsComment['comment_id'] ?>">
                <td>
                  <label>
                    <input id="news_comments[<?= $newsComment['comment_id'] ?>]"
                           name="news_comments[<?= $newsComment['comment_id'] ?>]"
                           value="<?= $newsComment['record_id'] ?>" type="checkbox">
                  </label>
                </td>
                <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?= $newsComment['email'] ?>">
                        <?= $newsComment['username'] ?>
                    </a>
                </span><br/>
                    <?= Strings::htmlspecialchars($newsComment['content']) ?>
                </td>
              </tr>
                <?php

            }
            ?>
        </table>
        <div class="text-right">
          <button class="btn btn-danger" id="submitNewsComments" type="submit" name="submit">
              <?= $PMF_LANG['ad_entry_delete'] ?>
          </button>
        </div>
      </form>
        <?php

    } else {
        echo '<p><strong>n/a</strong></p>';
    }
    ?>

  <script>
    (function () {
      $('#submitFaqComments').on('click', () => {
        deleteComments('faq');
        return false;
      });
      $('#submitNewsComments').on('click', () => {
        deleteComments('news');
        return false;
      });
    })();

    function deleteComments(type) {
      const comments = $('#' + type + 'CommentSelection').serialize();

      $('#returnMessage').empty();
      $.ajax({
        type: 'POST',
        url: 'index.php?action=ajax&ajax=comment',
        data: comments,
        success: function (msg) {
          if (msg === '1') {
            $('#saving_data_indicator').html('<img src="../assets/svg/spinning-circles.svg"> Deleting ...');
            $('tr td input:checked').parent().parent().parent().fadeOut('slow');
            $('#saving_data_indicator').fadeOut('slow');
            $('#returnMessage').html('<p class="alert alert-success"><?= $PMF_LANG['ad_entry_commentdelsuc'] ?></p>');
          } else {
            $('#returnMessage').html('<p class="alert alert-danger"><?= $PMF_LANG['ad_entry_commentdelfail'] ?></p>');
          }
        }
      });
      return false;
    }

  </script>
    <?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
