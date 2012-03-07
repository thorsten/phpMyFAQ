<?php
/**
 * The page with the ratings of the votings
 *
 * PHP Version 5.2
 *

 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2003-02-24
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['viewlog']) {
    require_once(PMF_ROOT_DIR.'/inc/Rating.php');

    $category    = new PMF_Category($current_admin_user, $current_admin_groups, false);
    $ratings     = new PMF_Rating($db, $Language);
    $ratingdata  = $ratings->getAllRatings();
    $numratings  = count($ratingdata);
    $oldcategory = 0;
?>
        <header>
            <h2><?php print $PMF_LANG["ad_rs"] ?></h2>
        </header>

        <table class="table table-striped">
        <tbody>
<?php
    foreach ($ratingdata as $data) {
        if ($data['category_id'] != $oldcategory) {
?>
            <tr>
                <th colspan="6" style="text-align: left;">
                    <strong><?php print $category->categoryName[$data['category_id']]['name']; ?></strong>
                </th>
            </tr>
<?php
        }

        $question = PMF_String::htmlspecialchars(trim($data['question']), ENT_QUOTES, 'utf-8');
        $url      = sprintf('../index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
            $data['category_id'],
            $data['id'],
            $data['lang']
        );
?>
            <tr>
                <td><?php print $data['id']; ?></td>
                <td><?php print $data['lang']; ?></td>
                <td>
                    <a href="<?php print $url ?>" title="<?php print $question; ?>">
                        <?php print PMF_Utils::makeShorterText($question, 14); ?>
                    </a>
                </td>
                <td style="width: 60px;"><?php print $data['usr']; ?></td>
                <td style="width: 60px;">
                    <?php
                    if (round($data['num'] * 20) > 75) {
                        $progressBar = 'success';
                    } elseif (round($data['num'] * 20) < 25) {
                        $progressBar = 'danger';
                    } else {
                        $progressBar = 'info';
                    }
                    ?>
                    <div class="progress progress-<?php print $progressBar ?>" style="width: 50px;">
                        <div class="bar" style="width: <?php print round($data['num'] * 20); ?>%;"></div>
                    </div>
                </td>
                <td style="width: 60px;"><?php print round($data['num'] * 20); ?>%</td>
            </tr>
<?php
        $oldcategory = $data['category_id'];
    }
?>
        </tbody>
<?php
    if ($numratings > 0) {
?>
            <tr>
                <td colspan="6">
                    <span style="color: green; font-weight: bold;">
                        <?php print $PMF_LANG["ad_rs_green"] ?>
                    </span>
                    <?php print $PMF_LANG["ad_rs_ahtf"] ?>,
                    <span style="color: red; font-weight: bold;">
                        <?php print $PMF_LANG["ad_rs_red"] ?>
                    </span>
                    <?php print $PMF_LANG["ad_rs_altt"] ?>
                </td>
            </tr>
<?php
    } else {
?>
            <tr>
                <td colspan="5"><?php print $PMF_LANG["ad_rs_no"] ?></td>
            </tr>
<?php
    }
?>
        </table>
<?php
} else {
    print $PMF_LANG["err_NotAuth"];
}