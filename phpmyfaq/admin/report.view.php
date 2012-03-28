<?php
/**
 * View a generated report
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

    printf('<h2>%s</h2>', $PMF_LANG['ad_menu_reports']);

    $useCategory     = PMF_Filter::filterInput(INPUT_POST, 'report_category', FILTER_VALIDATE_INT);
    $useSubcategory  = PMF_Filter::filterInput(INPUT_POST, 'report_sub_category', FILTER_VALIDATE_INT);
    $useTranslation  = PMF_Filter::filterInput(INPUT_POST, 'report_translations', FILTER_VALIDATE_INT);
    $useLanguage     = PMF_Filter::filterInput(INPUT_POST, 'report_language', FILTER_VALIDATE_INT);
    $useId           = PMF_Filter::filterInput(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $useSticky       = PMF_Filter::filterInput(INPUT_POST, 'report_sticky', FILTER_VALIDATE_INT);
    $useTitle        = PMF_Filter::filterInput(INPUT_POST, 'report_title', FILTER_VALIDATE_INT);
    $useCreationDate = PMF_Filter::filterInput(INPUT_POST, 'report_creation_date', FILTER_VALIDATE_INT);
    $useOwner        = PMF_Filter::filterInput(INPUT_POST, 'report_owner', FILTER_VALIDATE_INT);
    $useLastModified = PMF_Filter::filterInput(INPUT_POST, 'report_last_modified_person', FILTER_VALIDATE_INT);
    $useUrl          = PMF_Filter::filterInput(INPUT_POST, 'report_url', FILTER_VALIDATE_INT);
    $useVisits       = PMF_Filter::filterInput(INPUT_POST, 'report_visits', FILTER_VALIDATE_INT);
?>
    <table class="list">
        <thead>
            <tr>
<?php
    ($useCategory)     ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_category']) : '';
    ($useSubcategory)  ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_sub_category']) : '';
    ($useTranslation)  ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_translations']) : '';
    ($useLanguage)     ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_language']) : '';
    ($useId)           ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_id']) : '';
    ($useSticky)       ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_sticky']) : '';
    ($useTitle)        ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_title']) : '';
    ($useCreationDate) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_creation_date']) : '';
    ($useOwner)        ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_owner']) : '';
    ($useLastModified) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_last_modified_person']) : '';
    ($useUrl)          ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_url']) : '';
    ($useVisits)       ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_visits']) : '';
?>
            </tr>
        </thead>
        <tbody>
<?php

    $report = new PMF_Report($faqConfig);

    foreach ($report->getReportingData() as $data) {
        print '<tr>';
        if ($useCategory) {
            if (0 != $data['category_parent']) {
                printf('<td>%s</td>', $data['category_parent']);
            } else {
                printf('<td>%s</td>', $data['category_name']);
            }
        }
        if ($useSubcategory) {
            if (0 != $data['category_parent']) {
                printf('<td>%s</td>', $data['category_name']);
            } else {
                print '<td>n/a</td>';
            }
        }
        if ($useTranslation) {
            printf('<td>%d</td>', $data['faq_translations']);
        }
        if ($useLanguage) {
            printf('<td>%s</td>', $languageCodes[strtoupper($data['faq_language'])]);
        }
        if ($useId) {
            printf('<td>%d</td>', $data['faq_id']);
        }
        if ($useSticky) {
            printf('<td>%s</td>', $data['faq_sticky']);
        }
        if ($useTitle) {
            printf('<td>%s</td>', $data['faq_question']);
        }
        if ($useCreationDate) {
            printf('<td>%s</td>', $data['faq_creation']);
        }
        if ($useOwner) {
            printf('<td>%s</td>', $data['faq_org_author']);
        }
        if ($useLastModified) {
            printf('<td>%s</td>', $data['faq_last_author']);
        }
        if ($useUrl) {
            $url = sprintf('<a href="../index.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s">Link</a>',
                $data['category_id'],
                $data['faq_id'],
                $data['faq_language']
            );
            printf('<td>%s</td>', $url);
        }
        if ($useVisits) {
            printf('<td>%d</td>', $data['faq_visits']);
        }
        print '</tr>';
    }
?>
        </tbody>
    </table>
    <br />
    <form action="?action=reportexport" method="post" style="display: inline;">
        <input type="hidden" name="report_category" id="report_category" value="<?php print $useCategory; ?>" /></td>
        <input type="hidden" name="report_sub_category" id="report_sub_category" value="<?php print $useSubcategory; ?>" /></td>
        <input type="hidden" name="report_translations" id="report_translations" value="<?php print $useTranslation; ?>" /></td>
        <input type="hidden" name="report_language" id="report_language" value="<?php print $useLanguage; ?>" /></td>
        <input type="hidden" name="report_id" id="report_id" value="<?php print $useId; ?>" /></td>
        <input type="hidden" name="report_sticky" id="report_sticky" value="<?php print $useSticky; ?>" /></td>
        <input type="hidden" name="report_title" id="report_title" value="<?php print $useTitle; ?>" /></td>
        <input type="hidden" name="report_creation_date" id="report_creation_date" value="<?php print $useCreationDate; ?>" /></td>
        <input type="hidden" name="report_owner" id="report_owner" value="<?php print $useOwner; ?>" /></td>
        <input type="hidden" name="report_last_modified_person" id="report_last_modified_person" class="radio" value="<?php print $useLastModified; ?>">
        <input type="hidden" name="report_url" id="report_url" value="<?php print $useUrl; ?>" /></td>
        <input type="hidden" name="report_visits" id="report_visits" value="<?php print $useVisits; ?>" /></td>
        <input class="btn-primary" type="submit" value="<?php print $PMF_LANG["ad_stat_report_make_csv"]; ?>" />
    </form>
<?php
} else {
    print $PMF_LANG['err_NotAuth'];
}
