<?php

/**
 * Administration frontend for Tags.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2003-02-24
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-tags"></i>
      <?= $PMF_LANG['ad_entry_tags'] ?>
  </h1>
</div>

<div class="row">
  <div class="col-lg-12">
    <form action="" method="post" class="tag-form">
      <input type="hidden" name="csrf" value="<?= $user->getCsrfTokenFromSession() ?>">
        <?php
        if ($user->perm->hasPermission($user->getUserId(), 'edit_faq')) {
            $tags = new Tags($faqConfig);

            if ('delete-tag' === $action) {
                $tagId = Filter::filterInput(INPUT_GET, 'id', FILTER_VALIDATE_INT);
                if ($tags->deleteTag($tagId)) {
                    echo '<p class="alert alert-success"><a href="#" class="close" data-dismiss="alert">×</a>';
                    echo $PMF_LANG['ad_tag_delete_success'] . '</p>';
                } else {
                    echo '<p class="alert alert-danger"><a href="#" class="close" data-dismiss="alert">×</a>';
                    echo $PMF_LANG['ad_tag_delete_error'];
                    echo '<br>' . $PMF_LANG['ad_adus_dberr'] . '<br>';
                    echo $faqConfig->getDb()->error() . '</p>';
                }
            }

            $tagData = $tags->getAllTags();

            if (count($tagData) === 0) {
              printf('<p class="alert alert-warning" role="alert">%s</p>', $PMF_LANG['ad_news_nodata']);
            }

            echo '<table class="table table-hover">';
            echo '<tbody>';

            foreach ($tagData as $key => $tag) {
                echo '<tr>';
                echo '<td><span data-tag-id="' . $key . '">' . Strings::htmlentities($tag) . '</span></td>';
                printf(
                    '<td><a class="btn btn-primary btn-edit" data-btn-id="%d" title="%s">' .
                    '<i aria-hidden="true" class="fa fa-edit"></i></a></td>',
                    $key,
                    $PMF_LANG['ad_user_edit']
                );

                printf(
                    '<td><a class="btn btn-danger" onclick="return confirm(\'%s\');" href="%s%d">',
                    $PMF_LANG['ad_user_del_3'],
                    '?action=delete-tag&amp;id=',
                    $key
                );
                printf(
                    '<span title="%s"><i aria-hidden="true" class="fa fa-trash"></i></span></a></td>',
                    $PMF_LANG['ad_entry_delete']
                );

                echo '<tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo $PMF_LANG['err_NotAuth'];
        }
        ?>
    </form>
    <script src="assets/js/tags.js"></script>
  </div>
</div>
