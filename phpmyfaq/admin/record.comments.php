<?php
/**
 * Shows all comments in the categories and provides a link to delete comments.
 *
 *
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
    $faqcomments = $comment->getAllComments('faq');

    printf("<header><h3>%s</h3></header>\n", $PMF_LANG['ad_comment_faqs']);
    if (count($faqcomments)) {
        ?>
        <form id="faqCommentSelection" name="faqCommentSelection" method="post" accept-charset="utf-8">
        <input type="hidden" name="ajax" value="comment" />
        <input type="hidden" name="ajaxaction" value="delete" />
        <table class="table table-striped">
<?php
        $lastCommentId = 0;
        foreach ($faqcomments as $faqcomment) {
            if ($faqcomment['comment_id'] == $lastCommentId) {
                continue;
            }
            ?>
        <tr id="comments_<?= $faqcomment['comment_id'] ?>">
            <td width="20">
                <label>
                <input id="faq_comments[<?= $faqcomment['comment_id'] ?>]"
                       name="faq_comments[<?= $faqcomment['comment_id'] ?>]"
                       value="<?= $faqcomment['record_id'] ?>" type="checkbox">
                </label>
            </td>
            <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?= $faqcomment['email'] ?>">
                        <?= $faqcomment['username'] ?>
                    </a> |
                    <?= $date->format(date('Y-m-d H:i', $faqcomment['date'])) ?> |
                    <a href="<?php printf('../?action=faq&cat=%d&id=%d&artlang=%s',
                        $faqcomment['category_id'],
                        $faqcomment['record_id'],
                        $LANGCODE) ?>">
                        <?= $faq->getRecordTitle($faqcomment['record_id']) ?>
                    </a>
                </span><br/>
                <?= Strings::htmlspecialchars($faqcomment['content']) ?>
            </td>
        </tr>
<?php
            $lastCommentId = $faqcomment['comment_id'];
        }
        ?>
        </table>
        <p>
            <button class="btn btn-danger" id="submitFaqComments" type="submit" name="submit">
                <?= $PMF_LANG['ad_entry_delete'] ?>
            </button>
        </p>
        </form>
<?php

    } else {
        echo '<p><strong>n/a</strong></p>';
    }

    $newscomments = $comment->getAllComments('news');

    printf("<header><h3>%s</h3></header>\n", $PMF_LANG['ad_comment_news']);
    if (count($newscomments)) {
        ?>
        <form id="newsCommentSelection" name="newsCommentSelection" method="post" accept-charset="utf-8">
        <input type="hidden" name="ajax" value="comment" />
        <input type="hidden" name="ajaxaction" value="delete" />
        <table class="table table-striped">
<?php
        foreach ($newscomments as $newscomment) { ?>
        <tr id="comments_<?= $newscomment['comment_id'] ?>">
            <td width="20">
                <label>
                <input id="news_comments[<?= $newscomment['comment_id'] ?>]"
                       name="news_comments[<?= $newscomment['comment_id'] ?>]"
                       value="<?= $newscomment['record_id'] ?>" type="checkbox">
                </label>
            </td>
            <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?= $newscomment['email'] ?>">
                        <?= $newscomment['username'] ?>
                    </a>
                </span><br/>
                <?= Strings::htmlspecialchars($newscomment['content']) ?>
            </td>
        </tr>
<?php

        }
        ?>
        </table>
        <p>
            <button class="btn btn-danger" id="submitNewsComments" type="submit" name="submit">
                <?= $PMF_LANG['ad_entry_delete'] ?>
            </button>
        </p>
        </form>
<?php

    } else {
        echo '<p><strong>n/a</strong></p>';
    }
    ?>
        </form>

        <script>
        (function() {
            $('#submitFaqComments').click(function() { deleteComments('faq'); return false; });
            $('#submitNewsComments').click(function() { deleteComments('news'); return false; });
        })();

        function deleteComments(type)
        {
            var comments = $('#' + type + 'CommentSelection').serialize();

            $('#returnMessage').empty();
            $.ajax({
                type: 'POST',
                url:  'index.php?action=ajax&ajax=comment',
                data: comments,
                success: function(msg) {
                    if (msg == 1) {
                        $('#saving_data_indicator').html('<img src="../assets/svg/spinning-circles.svg"> Deleting ...');
                        $('tr td input:checked').parent().parent().parent().fadeOut('slow');
                        $('#saving_data_indicator').fadeOut('slow');
                        $('#returnMessage').
                            html('<p class="alert alert-success"><?= $PMF_LANG['ad_entry_commentdelsuc'];
    ?></p>');
                    } else {
                        $('#returnMessage').
                            html('<p class="alert alert-danger"><?= $PMF_LANG['ad_entry_commentdelfail'] ?></p>');
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
