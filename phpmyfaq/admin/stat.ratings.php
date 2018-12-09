<?php
/**
 * The page with the ratings of the votings.
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
use phpMyFAQ\Filter;
use phpMyFAQ\Rating;
use phpMyFAQ\Strings;
use phpMyFAQ\Utils;

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'viewlog')) {
    $category = new Category($faqConfig, [], false);
    $category->setUser($currentAdminUser);
    $category->setGroups($currentAdminGroups);
    $ratings = new Rating($faqConfig);
    $ratingdata = $ratings->getAllRatings();
    $numratings = count($ratingdata);
    $oldcategory = 0;
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header">
                    <i aria-hidden="true" class="fa fa-tasks"></i> <?= $PMF_LANG['ad_rs'] ?>

                    <div class="float-right">
                        <a class="btn btn-danger" 
                           href="?action=clear-statistics&csrf=<?= $user->getCsrfTokenFromSession() ?>">
                            <i aria-hidden="true" class="fa fa-trash"></i> <?= $PMF_LANG['ad_delete_all_votings'] ?>
                        </a>
                    </div>
                </h2>
            </div>
        </header>

<?php
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_SANITIZE_STRING);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
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
                <table class="table table-striped">
                    <tbody>
<?php
    foreach ($ratingdata as $data) {
        if ($data['category_id'] != $oldcategory) {
            ?>
                    <tr>
                        <th colspan="6" style="text-align: left;">
                            <h4><?= $category->categoryName[$data['category_id']]['name'];
            ?></h4>
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
<?php if ($numratings > 0) {
    ?>
                    <tfoot>
                        <tr>
                            <td colspan="6">
                                <small>
                                <span style="color: green; font-weight: bold;">
                                    <?= $PMF_LANG['ad_rs_green'] ?>
                                </span>
                                <?= $PMF_LANG['ad_rs_ahtf'] ?>,
                                <span style="color: red; font-weight: bold;">
                                    <?= $PMF_LANG['ad_rs_red'] ?>
                                </span>
                                <?= $PMF_LANG['ad_rs_altt'] ?>
                                </small>
                            </td>
                        </tr>
                    </tfoot>
<?php 
} else {
    ?>
                    <tfoot>
                        <tr>
                            <td colspan="6"><?= $PMF_LANG['ad_rs_no'] ?></td>
                        </tr>
                    </tfoot>
<?php 
}
    ?>
                </table>
            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
