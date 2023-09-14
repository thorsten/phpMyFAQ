<?php

/**
 * Export of a generated report.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Gustavo Solt <gustavo.solt@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-01-12
 */

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Report;
use phpMyFAQ\HttpStreamer;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}

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

    $text = [];
    $text[0] = [];
    ($useCategory) ? $text[0][] = Translation::get('ad_stat_report_category') : '';
    ($useSubcategory) ? $text[0][] = Translation::get('ad_stat_report_sub_category') : '';
    ($useTranslation) ? $text[0][] = Translation::get('ad_stat_report_translations') : '';
    ($useLanguage) ? $text[0][] = Translation::get('ad_stat_report_language') : '';
    ($useId) ? $text[0][] = Translation::get('ad_stat_report_id') : '';
    ($useSticky) ? $text[0][] = Translation::get('ad_stat_report_sticky') : '';
    ($useTitle) ? $text[0][] = Translation::get('ad_stat_report_title') : '';
    ($useCreationDate) ? $text[0][] = Translation::get('ad_stat_report_creation_date') : '';
    ($useOwner) ? $text[0][] = Translation::get('ad_stat_report_owner') : '';
    ($useLastModified) ? $text[0][] = Translation::get('ad_stat_report_last_modified_person') : '';
    ($useUrl) ? $text[0][] = Translation::get('ad_stat_report_url') : '';
    ($useVisits) ? $text[0][] = Translation::get('ad_stat_report_visits') : '';

    $report = new Report($faqConfig);

    foreach ($report->getReportingData() as $data) {
        $i = $data['faq_id'];
        if ($useCategory && isset($data['category_name'])) {
            if (0 !== $data['category_parent']) {
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
        if ($useLanguage && LanguageCodes::get($data['faq_language'])) {
            $text[$i][] = $report->convertEncoding(LanguageCodes::get($data['faq_language']));
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
        if ($useLastModified && isset($data['faq_last_author'])) {
            $text[$i][] = $report->convertEncoding($data['faq_last_author']);
        } else {
            $text[$i][] = '';
        }
        if ($useUrl) {
            $text[$i][] = $report->convertEncoding(
                sprintf(
                    '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
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
        $csvRow = array_map(['phpMyFAQ\Report', 'sanitize'], $row);
        $content .= implode(';', $csvRow);
        $content .= "\r\n";
    }

    $oHttpStreamer = new HttpStreamer('csv', $content);
    try {
        $oHttpStreamer->send(HttpStreamer::HTTP_CONTENT_DISPOSITION_ATTACHMENT);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
} else {
    echo Translation::get('err_noArticles');
}
