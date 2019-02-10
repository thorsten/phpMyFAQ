<?php
/**
 * Shows all comments in the categories and provides a link to delete comments.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2007-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2007-03-04
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

printf(
    '<header class="row"><h2 class="page-header"><i aria-hidden="true" class="fa fa-pencil"></i> %s</h2></header>',
    $PMF_LANG['ad_comment_administration']
);

echo '<div id="returnMessage"></div>';

if ($user->perm->checkRight($user->getUserId(), 'delcomment')) {
    $comment = new PMF_Comment($faqConfig);
    $category = new PMF_Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $faq = new PMF_Faq($faqConfig);
    $date = new PMF_Date($faqConfig);

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
        <tr id="comments_<?php echo $faqcomment['comment_id'] ?>">
            <td width="20">
                <label>
                <input id="faq_comments[<?php echo $faqcomment['comment_id'] ?>]"
                       name="faq_comments[<?php echo $faqcomment['comment_id'] ?>]"
                       value="<?php echo $faqcomment['record_id'] ?>" type="checkbox">
                </label>
            </td>
            <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?php echo $faqcomment['email'] ?>">
                        <?php echo $faqcomment['username'] ?>
                    </a> |
                    <?php echo $date->format(date('Y-m-d H:i', $faqcomment['date'])) ?> |
                    <a href="<?php printf('../?action=artikel&cat=%d&id=%d&artlang=%s',
                       $faqcomment['category_id'],
                       $faqcomment['record_id'],
                       $LANGCODE) ?>">
                        <?php echo $faq->getRecordTitle($faqcomment['record_id']) ?>
                    </a>
                </span><br/>
                <?php echo PMF_String::htmlspecialchars($faqcomment['content']) ?>
            </td>
        </tr>
<?php
            $lastCommentId = $faqcomment['comment_id'];
        }
        ?>
        </table>
        <p>
            <button class="btn btn-danger" id="submitFaqComments" type="submit" name="submit">
                <?php echo $PMF_LANG['ad_entry_delete'] ?>
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
        <tr id="comments_<?php echo $newscomment['comment_id'] ?>">
            <td width="20">
                <label>
                <input id="news_comments[<?php echo $newscomment['comment_id'] ?>]"
                       name="news_comments[<?php echo $newscomment['comment_id'] ?>]"
                       value="<?php echo $newscomment['record_id'] ?>" type="checkbox">
                </label>
            </td>
            <td>
                <span style="font-weight: bold;">
                    <a href="mailto:<?php echo $newscomment['email'] ?>">
                        <?php echo $newscomment['username'] ?>
                    </a>
                </span><br/>
                <?php echo PMF_String::htmlspecialchars($newscomment['content']) ?>
            </td>
        </tr>
<?php

        }
        ?>
        </table>
        <p>
            <button class="btn btn-danger" id="submitNewsComments" type="submit" name="submit">
                <?php echo $PMF_LANG['ad_entry_delete'] ?>
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
                        $('#saving_data_indicator').html('<i aria-hidden="true" class="fa fa-spinner fa-spin"></i> Deleting ...');
                        $('tr td input:checked').parent().parent().parent().fadeOut('slow');
                        $('#saving_data_indicator').fadeOut('slow');
                        $('#returnMessage').
                            html('<p class="alert alert-success"><?php echo $PMF_LANG['ad_entry_commentdelsuc'];
    ?></p>');
                    } else {
                        $('#returnMessage').
                            html('<p class="alert alert-danger"><?php echo $PMF_LANG['ad_entry_commentdelfail'] ?></p>');
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
