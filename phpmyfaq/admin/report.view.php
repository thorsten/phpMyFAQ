<?php

/**
 * View a generated report.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Gustavo Solt <gustavo.solt@mayflower.de>
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2022 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2011-01-12
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Report;
use phpMyFAQ\Strings;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
        <header class="row">
            <div class="col-lg-12">
                <h2 class="page-header"><i aria-hidden="true" class="fa fa-tasks"></i>  <?= $PMF_LANG['ad_menu_reports']; ?></h2>
            </div>
        </header>


        <div class="row">
            <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'reports')) {
    $useCategory = Filter::filterInput(INPUT_POST, 'report_category', FILTER_VALIDATE_INT);
    $useSubcategory = Filter::filterInput(INPUT_POST, 'report_sub_category', FILTER_VALIDATE_INT);
    $useTranslation = Filter::filterInput(INPUT_POST, 'report_translations', FILTER_VALIDATE_INT);
    $useLanguage = Filter::filterInput(INPUT_POST, 'report_language', FILTER_VALIDATE_INT);
    $useId = Filter::filterInput(INPUT_POST, 'report_id', FILTER_VALIDATE_INT);
    $useSticky = Filter::filterInput(INPUT_POST, 'report_sticky', FILTER_VALIDATE_INT);
    $useTitle = Filter::filterInput(INPUT_POST, 'report_title', FILTER_VALIDATE_INT);
    $useCreationDate = Filter::filterInput(INPUT_POST, 'report_creation_date', FILTER_VALIDATE_INT);
    $useOwner = Filter::filterInput(INPUT_POST, 'report_owner', FILTER_VALIDATE_INT);
    $useLastModified = Filter::filterInput(INPUT_POST, 'report_last_modified_person', FILTER_VALIDATE_INT);
    $useUrl = Filter::filterInput(INPUT_POST, 'report_url', FILTER_VALIDATE_INT);
    $useVisits = Filter::filterInput(INPUT_POST, 'report_visits', FILTER_VALIDATE_INT);
    ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
    <?php
    ($useCategory) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_category']) : '';
    ($useSubcategory) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_sub_category']) : '';
    ($useTranslation) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_translations']) : '';
    ($useLanguage) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_language']) : '';
    ($useId) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_id']) : '';
    ($useSticky) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_sticky']) : '';
    ($useTitle) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_title']) : '';
    ($useCreationDate) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_creation_date']) : '';
    ($useOwner) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_owner']) : '';
    ($useLastModified) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_last_modified_person']) : '';
    ($useUrl) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_url']) : '';
    ($useVisits) ? printf('<th>%s</th>', $PMF_LANG['ad_stat_report_visits']) : '';
    ?>
                        </tr>
                    </thead>
                    <tbody>
    <?php

    $report = new Report($faqConfig);

    foreach ($report->getReportingData() as $data) {
        echo '<tr>';
        if ($useCategory) {
            if (0 != $data['category_parent']) {
                printf('<td>%s</td>', $data['category_parent']);
            } else {
                printf('<td>%s</td>', Strings::htmlentities($data['category_name'] ?? ''));
            }
        }
        if ($useSubcategory) {
            if (0 != $data['category_parent']) {
                printf('<td>%s</td>', Strings::htmlentities($data['category_name']));
            } else {
                echo '<td>n/a</td>';
            }
        }
        if ($useTranslation) {
            printf('<td>%d</td>', $data['faq_translations']);
        }
        if ($useLanguage && isset($languageCodes[strtoupper($data['faq_language'])])) {
            printf('<td>%s</td>', $languageCodes[strtoupper($data['faq_language'])]);
        }
        if ($useId) {
            printf('<td>%d</td>', $data['faq_id']);
        }
        if ($useSticky) {
            printf('<td>%s</td>', $data['faq_sticky']);
        }
        if ($useTitle) {
            printf('<td>%s</td>', Strings::htmlentities($data['faq_question']));
        }
        if ($useCreationDate) {
            printf('<td>%s</td>', $data['faq_updated']);
        }
        if ($useOwner) {
            printf('<td>%s</td>', Strings::htmlentities($data['faq_org_author']));
        }
        if ($useLastModified) {
            printf('<td>%s</td>', Strings::htmlentities($data['faq_last_author'] ?? ''));
        }
        if ($useUrl) {
            $url = sprintf(
                '<a href="../index.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s">Link</a>',
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
                    <input type="hidden" name="report_category" id="report_category" value="<?= $useCategory ?>"></td>
                    <input type="hidden" name="report_sub_category" id="report_sub_category" value="<?= $useSubcategory ?>"></td>
                    <input type="hidden" name="report_translations" id="report_translations" value="<?= $useTranslation ?>"></td>
                    <input type="hidden" name="report_language" id="report_language" value="<?= $useLanguage ?>"></td>
                    <input type="hidden" name="report_id" id="report_id" value="<?= $useId ?>"></td>
                    <input type="hidden" name="report_sticky" id="report_sticky" value="<?= $useSticky ?>"></td>
                    <input type="hidden" name="report_title" id="report_title" value="<?= $useTitle ?>"></td>
                    <input type="hidden" name="report_creation_date" id="report_creation_date" value="<?= $useCreationDate ?>"></td>
                    <input type="hidden" name="report_owner" id="report_owner" value="<?= $useOwner ?>"></td>
                    <input type="hidden" name="report_last_modified_person" id="report_last_modified_person" class="radio" value="<?= $useLastModified ?>">
                    <input type="hidden" name="report_url" id="report_url" value="<?= $useUrl ?>"></td>
                    <input type="hidden" name="report_visits" id="report_visits" value="<?= $useVisits ?>"></td>
                    <div class="form-group row">
                        <button class="btn btn-primary" type="submit">
                            <?= $PMF_LANG['ad_stat_report_make_csv'] ?>
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
