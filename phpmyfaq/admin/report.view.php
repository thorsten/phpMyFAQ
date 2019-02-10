<?php
/**
 * View a generated report.
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
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-tasks"></i>  <?php echo $PMF_LANG['ad_menu_reports']; ?></h2>
            </div>
        </header>


        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->checkRight($user->getUserId(), 'reports')) {
    $useCategory = PMF_Filter::filterInput(INPUT_POST, 'report_category', FILTER_VALIDATE_INT);
    $useSubcategory = PMF_Filter::filterInput(INPUT_POST, 'report_sub_category', FILTER_VALIDATE_INT);
    $useTranslation = PMF_Filter::filterInput(INPUT_POST, 'report_translations', FILTER_VALIDATE_INT);
    $useLanguage = PMF_Filter::filterInput(INPUT_POST, 'report_language', FILTER_VALIDATE_INT);
    $useId = PMF_Filter::filterInput(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $useSticky = PMF_Filter::filterInput(INPUT_POST, 'report_sticky', FILTER_VALIDATE_INT);
    $useTitle = PMF_Filter::filterInput(INPUT_POST, 'report_title', FILTER_VALIDATE_INT);
    $useCreationDate = PMF_Filter::filterInput(INPUT_POST, 'report_creation_date', FILTER_VALIDATE_INT);
    $useOwner = PMF_Filter::filterInput(INPUT_POST, 'report_owner', FILTER_VALIDATE_INT);
    $useLastModified = PMF_Filter::filterInput(INPUT_POST, 'report_last_modified_person', FILTER_VALIDATE_INT);
    $useUrl = PMF_Filter::filterInput(INPUT_POST, 'report_url', FILTER_VALIDATE_INT);
    $useVisits = PMF_Filter::filterInput(INPUT_POST, 'report_visits', FILTER_VALIDATE_INT);
    ?>
                <table class="table table-striped">
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
        echo '<tr>';
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
                echo '<td>n/a</td>';
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
            printf('<td>%s</td>', $data['faq_updated']);
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
        echo '</tr>';
    }
    ?>
                    </tbody>
                </table>
                <form action="?action=reportexport" method="post" accept-charset="utf-8">
                    <input type="hidden" name="report_category" id="report_category" value="<?php echo $useCategory;
    ?>"></td>
                    <input type="hidden" name="report_sub_category" id="report_sub_category" value="<?php echo $useSubcategory;
    ?>"></td>
                    <input type="hidden" name="report_translations" id="report_translations" value="<?php echo $useTranslation;
    ?>"></td>
                    <input type="hidden" name="report_language" id="report_language" value="<?php echo $useLanguage;
    ?>"></td>
                    <input type="hidden" name="report_id" id="report_id" value="<?php echo $useId;
    ?>"></td>
                    <input type="hidden" name="report_sticky" id="report_sticky" value="<?php echo $useSticky;
    ?>"></td>
                    <input type="hidden" name="report_title" id="report_title" value="<?php echo $useTitle;
    ?>"></td>
                    <input type="hidden" name="report_creation_date" id="report_creation_date" value="<?php echo $useCreationDate;
    ?>"></td>
                    <input type="hidden" name="report_owner" id="report_owner" value="<?php echo $useOwner;
    ?>"></td>
                    <input type="hidden" name="report_last_modified_person" id="report_last_modified_person" class="radio" value="<?php echo $useLastModified;
    ?>">
                    <input type="hidden" name="report_url" id="report_url" value="<?php echo $useUrl;
    ?>"></td>
                    <input type="hidden" name="report_visits" id="report_visits" value="<?php echo $useVisits;
    ?>"></td>
                    <div class="form-group">
                        <button class="btn btn-primary" type="submit">
                            <?php echo $PMF_LANG['ad_stat_report_make_csv'];
    ?>
                        </button>
                    </div>
                </form>
<?php

} else {
    echo $PMF_LANG['err_NotAuth'];
}
?>
            </div>
        </div>
