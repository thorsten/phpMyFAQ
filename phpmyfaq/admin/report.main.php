<?php
/**
 * The reporting page
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2014 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-01-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    $protocol = 'http';
    if (isset($_SERVER['HTTPS']) && strtoupper($_SERVER['HTTPS']) === 'ON'){
        $protocol = 'https';
    }
    header('Location: ' . $protocol . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['reports']) {
?>
    <header>
        <h2><i class="icon-tasks"></i>  <?php echo $PMF_LANG['ad_menu_reports']; ?></h2>
    </header>

    <form class="form-horizontal" action="?action=reportview" method="post" accept-charset="utf-8">
    <fieldset>
        <legend><?php echo $PMF_LANG["ad_stat_report_fields"]; ?></legend>

        <div class="control-group">
            <label class="checkbox inline" for="report_category">
                <input type="checkbox" name="report_category" id="report_category" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_category"]; ?>
            </label>
            <label class="checkbox inline" for="report_sub_category">
                <input type="checkbox" name="report_sub_category" id="report_sub_category" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_sub_category"]; ?>
            </label>
            <label class="checkbox inline" for="report_translations">
                <input type="checkbox" name="report_translations" id="report_translations" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_translations"]; ?>
            </label>
            <label class="checkbox inline" for="report_translations">
                <input type="checkbox" name="report_language" id="report_language" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_language"]; ?>
            </label>
            <label class="checkbox inline" for="report_id">
                <input type="checkbox" name="report_id" id="report_id" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_id"]; ?>:
            </label>
            <label class="checkbox inline" for="report_sticky">
                <input type="checkbox" name="report_sticky" id="report_sticky" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_sticky"]; ?>
            </label>
        </div>

        <div class="control-group">
            <label class="checkbox inline" for="report_title">
                <input type="checkbox" name="report_title" id="report_title" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_title"]; ?>
            </label>
            <label class="checkbox inline" for="report_creation_date">
                <input type="checkbox" name="report_creation_date" id="report_creation_date" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_creation_date"]; ?>
            </label>
            <label class="checkbox inline" for="report_owner">
                <input type="checkbox" name="report_owner" id="report_owner" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_owner"]; ?>
            </label>
            <label class="checkbox inline" for="report_last_modified_person">
                <input type="checkbox" name="report_last_modified_person" id="report_last_modified_person" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_last_modified_person"]; ?>
            </label>
            <label class="checkbox inline" for="report_url">
                <input type="checkbox" name="report_url" id="report_url" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_url"]; ?>
            </label>
            <label class="checkbox inline" for="report_visits">
                <input type="checkbox" name="report_visits" id="report_visits" checked="checked" value="1" />
                <?php echo $PMF_LANG["ad_stat_report_visits"]; ?>
            </label>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <?php echo $PMF_LANG["ad_stat_report_make_report"]; ?>
            </button>
        </div>
    </fieldset>
    </form>
<?php
} else {
    echo $PMF_LANG['err_NotAuth'];
}
