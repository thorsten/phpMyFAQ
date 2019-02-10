<?php
/**
 * The reporting page.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 *
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2011-01-12
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON') {
        $protocol = 'https';
    }
    header('Location: '.$protocol.'://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($user->perm->checkRight($user->getUserId(), 'reports')) {
    ?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-tasks"></i>  <?php echo $PMF_LANG['ad_menu_reports'];
    ?></h2>
            </div>
        </header>

        <div class="row">
            <div class="col-lg-12">
            <form action="?action=reportview" method="post" accept-charset="utf-8">
                <h4><?php echo $PMF_LANG['ad_stat_report_fields'];
    ?></h4>

                <div class="form-group">
                    <label class="checkbox" for="report_category">
                        <input type="checkbox" name="report_category" id="report_category" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_category'];
    ?>
                    </label>
                    <label class="checkbox" for="report_sub_category">
                        <input type="checkbox" name="report_sub_category" id="report_sub_category" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_sub_category'];
    ?>
                    </label>
                    <label class="checkbox" for="report_translations">
                        <input type="checkbox" name="report_translations" id="report_translations" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_translations'];
    ?>
                    </label>
                    <label class="checkbox" for="report_translations">
                        <input type="checkbox" name="report_language" id="report_language" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_language'];
    ?>
                    </label>
                    <label class="checkbox" for="report_id">
                        <input type="checkbox" name="report_id" id="report_id" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_id'];
    ?>:
                    </label>
                    <label class="checkbox" for="report_sticky">
                        <input type="checkbox" name="report_sticky" id="report_sticky" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_sticky'];
    ?>
                    </label>
                    <label class="checkbox" for="report_title">
                        <input type="checkbox" name="report_title" id="report_title" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_title'];
    ?>
                    </label>
                    <label class="checkbox" for="report_creation_date">
                        <input type="checkbox" name="report_creation_date" id="report_creation_date" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_creation_date'];
    ?>
                    </label>
                    <label class="checkbox" for="report_owner">
                        <input type="checkbox" name="report_owner" id="report_owner" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_owner'];
    ?>
                    </label>
                    <label class="checkbox" for="report_last_modified_person">
                        <input type="checkbox" name="report_last_modified_person" id="report_last_modified_person" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_last_modified_person'];
    ?>
                    </label>
                    <label class="checkbox" for="report_url">
                        <input type="checkbox" name="report_url" id="report_url" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_url'];
    ?>
                    </label>
                    <label class="checkbox" for="report_visits">
                        <input type="checkbox" name="report_visits" id="report_visits" checked value="1">
                        <?php echo $PMF_LANG['ad_stat_report_visits'];
    ?>
                    </label>
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" type="submit">
                        <?php echo $PMF_LANG['ad_stat_report_make_report'];
    ?>
                    </button>
                </div>

            </form>

            </div>
        </div>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
