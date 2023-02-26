<?php

/**
 * The page with the ratings.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-02-24
 */

use phpMyFAQ\Category;
use phpMyFAQ\Filter;
use phpMyFAQ\Rating;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $ratings = new Rating($faqConfig);
    $ratingdata = $ratings->getAllRatings();
    $numratings = count($ratingdata);
    $oldcategory = 0;
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i aria-hidden="true" class="fa fa-tasks"></i> <?= Translation::get('ad_rs') ?>
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group mr-2">
            <a class="btn btn-sm btn-danger"
               href="?action=clear-statistics&csrf=<?= Token::getInstance()->getTokenString('clear-statistics') ?>">
                <i aria-hidden="true" class="fa fa-trash"></i> <?= Translation::get('ad_delete_all_votings') ?>
            </a>
        </div>
    </div>
</div>

    <?php
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

    if (!Token::getInstance()->verifyToken('clear-statistics', $csrfToken)) {
        $clearStatistics = false;
    } else {
        $clearStatistics = true;
    }

    if ('clear-statistics' === $action && $clearStatistics) {
        if ($ratings->deleteAll()) {
            echo '<p class="alert alert-success">Statistics successfully deleted.</p>';
        } else {
            echo '<p class="alert alert-danger">Statistics not deleted.</p>';
        }
    }
    ?>

        <div class="row">
            <div class="col-lg-12">
                <table class="table table-striped align-middle">
                    <tbody>
    <?php
    foreach ($ratingdata as $data) {
        if ($data['category_id'] != $oldcategory) {
            ?>
                    <tr>
                        <th colspan="6" style="text-align: left;">
                            <h4><?= $category->categoryName[$data['category_id']]['name'] ?></h4>
                        </th>
                    </tr>
            <?php
        }

        $question = Strings::htmlspecialchars(trim($data['question']));
        $url = sprintf(
            '../index.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
            $data['category_id'],
            $data['id'],
            $data['lang']
        );
        ?>
                    <tr>
                        <td><?= $data['id'];
                        ?></td>
                        <td><?= $data['lang'];
                        ?></td>
                        <td>
                            <a href="<?= $url ?>" title="<?= $question;
                            ?>">
                                <?= Utils::makeShorterText($question, 14);
                                ?>
                            </a>
                        </td>
                        <td><?= $data['usr'];
                        ?></td>
                        <td>
                            <?php
                            if (round($data['num'] * 20) > 75) {
                                $progressBar = 'success';
                            } elseif (round($data['num'] * 20) < 25) {
                                $progressBar = 'danger';
                            } else {
                                $progressBar = 'info';
                            }
                            ?>
                            <meter value="<?= round($data['num'] * 20);
                            ?>" max="100" min="0" low="25" optimum="75"></meter>
                        </td>
                        <td><?= round($data['num'] * 20);
                        ?>%</td>
                    </tr>
        <?php
        $oldcategory = $data['category_id'];
    }
    ?>
                    </tbody>
    <?php if ($numratings > 0) { ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <small>
                                <span style="color: green; font-weight: bold;">
                                    <?= Translation::get('ad_rs_green') ?>
                                </span>
                                <?= Translation::get('ad_rs_ahtf') ?>,
                                <span style="color: red; font-weight: bold;">
                                    <?= Translation::get('ad_rs_red') ?>
                                </span>
                                <?= Translation::get('ad_rs_altt') ?>
                                </small>
                            </td>
                        </tr>
                    </tfoot>
    <?php } else { ?>
                    <tfoot>
                        <tr>
                            <td colspan="6"><?= Translation::get('ad_rs_no') ?></td>
                        </tr>
                    </tfoot>
    <?php } ?>
                </table>
            </div>
        </div>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
