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
    $ratingData = $ratings->getAllRatings();
    $numberOfRatings = is_countable($ratingData) ? count($ratingData) : 0;
    $currentCategory = 0;
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
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($csrfToken && !Token::getInstance()->verifyToken('clear-statistics', $csrfToken)) {
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
                <table class="table">
                    <tbody>
    <?php
    foreach ($ratingData as $data) {
        if ($data['category_id'] != $currentCategory) {
            ?>
                    <tr>
                        <th colspan="6" class="bg-secondary-subtle">
                            <h4 class="mt-2"><?= $category->categoryName[$data['category_id']]['name'] ?></h4>
                        </th>
                    </tr>
            <?php
        }

        $question = Strings::htmlspecialchars(trim((string) $data['question']));
        $url = sprintf(
            '../index.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
            $data['category_id'],
            $data['id'],
            $data['lang']
        );
        ?>
                    <tr>
                        <td><?= $data['id'] ?></td>
                        <td><?= $data['lang'] ?></td>
                        <td>
                            <a href="<?= $url ?>" title="<?= $question ?>">
                                <?= Utils::makeShorterText($question, 14) ?>
                            </a>
                        </td>
                        <td><?= $data['usr'] ?>x</td>
                        <td class="w-25">
                            <?php
                            if (round($data['num'] * 20) > 75) {
                                $progressBar = 'success';
                            } elseif (round($data['num'] * 20) < 25) {
                                $progressBar = 'danger';
                            } else {
                                $progressBar = 'primary';
                            }
                            ?>
                            <div class="progress" role="progressbar" aria-label="Success example"
                                 aria-valuenow="<?= round($data['num'] * 20) ?>" aria-valuemin="0" aria-valuemax="100">
                                <div class="progress-bar bg-<?= $progressBar ?>"
                                     style="width: <?= round($data['num'] * 20) ?>%"></div>
                            </div>

                        </td>
                        <td><?= round($data['num'] * 20);
                        ?>%</td>
                    </tr>
        <?php
        $currentCategory = $data['category_id'];
    }
    ?>
                    </tbody>
    <?php if ($numberOfRatings > 0) { ?>
                    <tfoot>
                      <tr>
                        <td colspan="6">
                          <small>
                            <span class="bg-success text-white fw-bold"><?= Translation::get('ad_rs_green') ?></span>
                            <?= Translation::get('ad_rs_ahtf') ?>,
                            <span class="bg-danger text-white fw-bold"><?= Translation::get('ad_rs_red') ?></span>
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
