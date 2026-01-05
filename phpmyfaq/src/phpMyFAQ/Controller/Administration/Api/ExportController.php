<?php

/**
 * The File Export Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use JsonException;
use League\CommonMark\Exception\CommonMarkException;
use phpMyFAQ\Administration\HttpStreamer;
use phpMyFAQ\Administration\Report;
use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Export;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ExportController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/export/file', name: 'admin.api.export.file', methods: ['GET'])]
    public function exportFile(Request $request): void
    {
        $this->userHasPermission(PermissionType::EXPORT);

        $categoryId = (int) Filter::filterVar($request->request->get('categoryId'), FILTER_VALIDATE_INT);
        $downwards = Filter::filterVar($request->request->get('downwards'), FILTER_VALIDATE_BOOLEAN, false);
        $inlineDisposition = Filter::filterVar($request->request->get('disposition'), FILTER_SANITIZE_SPECIAL_CHARS);
        $type = Filter::filterVar($request->request->get('export-type'), FILTER_SANITIZE_SPECIAL_CHARS, 'none');

        $faq = $this->container->get(id: 'phpmyfaq.faq');
        $category = new Category($this->configuration, [], false);
        $category->buildCategoryTree($categoryId);

        try {
            $export = Export::create($faq, $category, $this->configuration, $type);
            $content = $export->generate($categoryId, $downwards, $this->configuration->getLanguage()->getLanguage());

            // Stream the file content
            $httpStreamer = new HttpStreamer($type, $content);
            $disposition = 'inline' === $inlineDisposition
                ? HeaderUtils::DISPOSITION_INLINE
                : HeaderUtils::DISPOSITION_ATTACHMENT;
            $httpStreamer->send($disposition);
        } catch (Exception|JsonException|CommonMarkException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'admin/api/export/report', name: 'admin.api.export.report', methods: ['POST'])]
    public function exportReport(Request $request): Response
    {
        $this->userHasPermission(PermissionType::REPORTS);

        $data = json_decode($request->getContent())->data;
        if (!Token::getInstance($this->session)->verifyToken('create-report', $data->{'pmf-csrf-token'})) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $text = [];
        $text[0] = [];
        if (isset($data->category)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_category');
        }

        if (isset($data->sub_category)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_sub_category');
        }

        if (isset($data->translations)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_translations');
        }

        if (isset($data->language)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_language');
        }

        if (isset($data->id)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_id');
        }

        if (isset($data->sticky)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_sticky');
        }

        if (isset($data->title)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_title');
        }

        if (isset($data->creation_date)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_creation_date');
        }

        if (isset($data->owner)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_owner');
        }

        if (isset($data->last_modified_person)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_last_modified_person');
        }

        if (isset($data->url)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_url');
        }

        if (isset($data->visits)) {
            $text[0][] = Translation::get(key: 'ad_stat_report_visits');
        }

        $report = new Report($this->configuration);
        foreach ($report->getReportingData() as $reportData) {
            $i = $reportData['faq_id'];
            if (isset($data->category, $reportData['category_name'])) {
                $text[$i][] = Report::sanitize($report->convertEncoding($reportData['category_name']));
                if (0 !== $reportData['category_parent']) {
                    $text[$i][] = Report::sanitize($reportData['category_parent']);
                }
            }

            if (isset($data->sub_category)) {
                $text[$i][] = 'n/a';
                if (0 !== $reportData['category_parent']) {
                    $text[$i][] = Report::sanitize($report->convertEncoding($reportData['category_name']));
                }
            }

            if (isset($data->translations)) {
                $text[$i][] = $reportData['faq_translations'];
            }

            if (isset($data->language) && LanguageCodes::get($reportData['faq_language'])) {
                $text[$i][] = $report->convertEncoding(LanguageCodes::get($reportData['faq_language']));
            }

            if (isset($data->id)) {
                $text[$i][] = $reportData['faq_id'];
            }

            if (isset($data->sticky)) {
                $text[$i][] = $reportData['faq_sticky'];
            }

            if (isset($data->title)) {
                $text[$i][] = Report::sanitize($report->convertEncoding($reportData['faq_question']));
            }

            if (isset($data->creation_date)) {
                $text[$i][] = $reportData['faq_updated'];
            }

            if (isset($data->owner)) {
                $text[$i][] = Report::sanitize($report->convertEncoding($reportData['faq_org_author']));
            }

            $text[$i][] = '';
            if (isset($data->last_modified_person, $reportData['faq_last_author'])) {
                $text[$i][] = Report::sanitize($report->convertEncoding($reportData['faq_last_author']));
            }

            if (isset($data->url)) {
                $text[$i][] = Report::sanitize($report->convertEncoding(sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    $reportData['category_id'],
                    $reportData['faq_id'],
                    $reportData['faq_language'],
                    TitleSlugifier::slug($reportData['faq_question']),
                )));
            }

            if (isset($data->visits)) {
                $text[$i][] = $reportData['faq_visits'];
            }
        }

        $handle = fopen('php://temp', mode: 'r+');
        foreach ($text as $row) {
            fputcsv($handle, fields: $row, separator: ',', enclosure: '"', escape: '\\', eol: PHP_EOL);
        }

        rewind($handle);

        $content = stream_get_contents($handle);

        fclose($handle);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="report.csv"');

        return $response;
    }
}
