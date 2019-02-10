<?php

/**
 * Export of a generated report.
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

    $text = [];
    $text[0] = [];
    ($useCategory)     ? $text[0][] = $PMF_LANG['ad_stat_report_category'] : '';
    ($useSubcategory)  ? $text[0][] = $PMF_LANG['ad_stat_report_sub_category'] : '';
    ($useTranslation)  ? $text[0][] = $PMF_LANG['ad_stat_report_translations'] : '';
    ($useLanguage)     ? $text[0][] = $PMF_LANG['ad_stat_report_language'] : '';
    ($useId)           ? $text[0][] = $PMF_LANG['ad_stat_report_id'] : '';
    ($useSticky)       ? $text[0][] = $PMF_LANG['ad_stat_report_sticky'] : '';
    ($useTitle)        ? $text[0][] = $PMF_LANG['ad_stat_report_title'] : '';
    ($useCreationDate) ? $text[0][] = $PMF_LANG['ad_stat_report_creation_date'] : '';
    ($useOwner)        ? $text[0][] = $PMF_LANG['ad_stat_report_owner'] : '';
    ($useLastModified) ? $text[0][] = $PMF_LANG['ad_stat_report_last_modified_person'] : '';
    ($useUrl)          ? $text[0][] = $PMF_LANG['ad_stat_report_url'] : '';
    ($useVisits)       ? $text[0][] = $PMF_LANG['ad_stat_report_visits'] : '';

    $report = new PMF_Report($faqConfig);

    foreach ($report->getReportingData() as $data) {
        $i = $data['faq_id'];
        if ($useCategory) {
            if (0 != $data['category_parent']) {
                $text[$i][] = $data['category_parent'];
            } else {
                $text[$i][] = $report->convertEncoding($data['category_name']);
            }
        }
        if ($useSubcategory) {
            if (0 != $data['category_parent']) {
                $text[$i][] = $report->convertEncoding($data['category_name']);
            } else {
                $text[$i][] = 'n/a';
            }
        }
        if ($useTranslation) {
            $text[$i][] = $data['faq_translations'];
        }
        if ($useLanguage) {
            $text[$i][] = $report->convertEncoding($languageCodes[strtoupper($data['faq_language'])]);
        }
        if ($useId) {
            $text[$i][] = $data['faq_id'];
        }
        if ($useSticky) {
            $text[$i][] = $data['faq_sticky'];
        }
        if ($useTitle) {
            $text[$i][] = $report->convertEncoding($data['faq_question']);
        }
        if ($useCreationDate) {
            $text[$i][] = $data['faq_updated'];
        }
        if ($useOwner) {
            $text[$i][] = $report->convertEncoding($data['faq_org_author']);
        }
        if ($useLastModified) {
            $text[$i][] = $report->convertEncoding($data['faq_last_author']);
        }
        if ($useUrl) {
            $text[$i][] = $report->convertEncoding(
                sprintf('%sindex.php?action=artikel&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $faqConfig->getDefaultUrl(),
                    $data['category_id'],
                    $data['faq_id'],
                    $data['faq_language']
                )
            );
        }
        if ($useVisits) {
            $text[$i][] = $data['faq_visits'];
        }
    }

    $content = '';
    foreach ($text as $row) {
        $content .= implode(';', $row);
        $content .= "\r\n";
    }

    $oHttpStreamer = new PMF_HttpStreamer('csv', $content);
    $oHttpStreamer->send(PMF_HttpStreamer::HTTP_CONTENT_DISPOSITION_ATTACHMENT);
} else {
    echo $PMF_LANG['err_noArticles'];
}
