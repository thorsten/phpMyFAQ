<?php
/**
 * The reporting page
 *
 * PHP Version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   Administration
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-01-12
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    header('Location: http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']));
    exit();
}

if ($permission['reports']) {
?>
    <h2><?php print $PMF_LANG['ad_menu_reports']; ?></h2>

    <form action="?action=reportview" method="post">
    <fieldset>
        <legend><?php print $PMF_LANG["ad_stat_report_fields"]; ?></legend>

        <p>
            <label><?php print $PMF_LANG["ad_stat_report_category"]; ?>:</label>
            <input type="checkbox" name="report_category" id="report_category" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_sub_category"]; ?>:</label>
            <input type="checkbox" name="report_sub_category" id="report_sub_category" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_translations"]; ?>:</label>
            <input type="checkbox" name="report_translations" id="report_translations" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_language"]; ?>:</label>
            <input type="checkbox" name="report_language" id="report_language" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_id"]; ?>:</label>
            <input type="checkbox" name="report_id" id="report_id" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_sticky"]; ?>:</label>
            <input type="checkbox" name="report_sticky" id="report_sticky" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_title"]; ?>:</label>
            <input type="checkbox" name="report_title" id="report_title" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_creation_date"]; ?>:</label>
            <input type="checkbox" name="report_creation_date" id="report_creation_date" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_owner"]; ?>:</label>
            <input type="checkbox" name="report_owner" id="report_owner" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_last_modified_person"]; ?>:</label>
            <input type="checkbox" name="report_last_modified_person" id="report_last_modified_person" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_url"]; ?>:</label>
            <input type="checkbox" name="report_url" id="report_url" class="radio" checked="checked" value="1" />
        </p>
        <p>
            <label><?php print $PMF_LANG["ad_stat_report_visits"]; ?>:</label>
            <input type="checkbox" name="report_visits" id="report_visits" class="radio" checked="checked" value="1" />
        </p>

        <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["ad_stat_report_make_report"]; ?>" />

    </fieldset>
    </form>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
