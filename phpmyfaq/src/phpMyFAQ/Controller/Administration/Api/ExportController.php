<?php

declare(strict_types=1);

/**
 * The File Export Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-23
 */

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
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('admin/api/export/file', name: 'admin.api.export.file', methods: ['GET'])]
    public function exportFile(Request $request): void
    {
        $this->userHasPermission(PermissionType::EXPORT);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
        $downwards = Filter::filterVar($request->get('downwards'), FILTER_VALIDATE_BOOLEAN, false);
        $inlineDisposition = Filter::filterVar($request->get('disposition'), FILTER_SANITIZE_SPECIAL_CHARS);
        $type = Filter::filterVar($request->get('export-type'), FILTER_SANITIZE_SPECIAL_CHARS, 'none');

        $faq = $this->container->get('phpmyfaq.faq');
        $category = new Category($this->configuration, [], false);
        $category->buildCategoryTree($categoryId);

        try {
            $export = Export::create($faq, $category, $this->configuration, $type);
            $content = $export->generate($categoryId, $downwards, $this->configuration->getLanguage()->getLanguage());

            // Stream the file content
            $httpStreamer = new HttpStreamer($type, $content);
            if ('inline' === $inlineDisposition) {
                $httpStreamer->send(HeaderUtils::DISPOSITION_INLINE);
            } else {
                $httpStreamer->send(HeaderUtils::DISPOSITION_ATTACHMENT);
            }
        } catch (Exception|JsonException|CommonMarkException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @throws Exception|\Exception
     */
    #[Route('admin/api/export/report', name: 'admin.api.export.report', methods: ['POST'])]
    public function exportReport(Request $request): Response
    {
        $this->userHasPermission(PermissionType::REPORTS);

        $data = json_decode($request->getContent())->data;
        if (!Token::getInstance($this->container->get('session'))->verifyToken(
            'create-report',
            $data->{'pmf-csrf-token'},
        )) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $text = [];
        $text[0] = [];
        if (isset($data->category)) {
            $text[0][] = Translation::get('ad_stat_report_category');
        }

        if (isset($data->sub_category)) {
            $text[0][] = Translation::get('ad_stat_report_sub_category');
        }

        if (isset($data->translations)) {
            $text[0][] = Translation::get('ad_stat_report_translations');
        }

        if (isset($data->language)) {
            $text[0][] = Translation::get('ad_stat_report_language');
        }

        if (isset($data->id)) {
            $text[0][] = Translation::get('ad_stat_report_id');
        }

        if (isset($data->sticky)) {
            $text[0][] = Translation::get('ad_stat_report_sticky');
        }

        if (isset($data->title)) {
            $text[0][] = Translation::get('ad_stat_report_title');
        }

        if (isset($data->creation_date)) {
            $text[0][] = Translation::get('ad_stat_report_creation_date');
        }

        if (isset($data->owner)) {
            $text[0][] = Translation::get('ad_stat_report_owner');
        }

        if (isset($data->last_modified_person)) {
            $text[0][] = Translation::get('ad_stat_report_last_modified_person');
        }

        if (isset($data->url)) {
            $text[0][] = Translation::get('ad_stat_report_url');
        }

        if (isset($data->visits)) {
            $text[0][] = Translation::get('ad_stat_report_visits');
        }

        $report = new Report($this->configuration);
        foreach ($report->getReportingData() as $reportData) {
            $i = $reportData['faq_id'];
            if (isset($data->category, $reportData['category_name'])) {
                if (0 !== $reportData['category_parent']) {
                    $text[$i][] = Report::sanitize($reportData['category_parent']);
                } else {
                    $text[$i][] = Report::sanitize($report->convertEncoding($reportData['category_name']));
                }
            }

            if (isset($data->sub_category)) {
                if (0 != $reportData['category_parent']) {
                    $text[$i][] = Report::sanitize($report->convertEncoding($reportData['category_name']));
                } else {
                    $text[$i][] = 'n/a';
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

            if (isset($data->last_modified_person, $reportData['faq_last_author'])) {
                $text[$i][] = Report::sanitize($report->convertEncoding($reportData['faq_last_author']));
            } else {
                $text[$i][] = '';
            }

            if (isset($data->url)) {
                $text[$i][] = Report::sanitize($report->convertEncoding(sprintf(
                    '%sindex.php?action=faq&amp;cat=%d&amp;id=%d&amp;artlang=%s',
                    $this->configuration->getDefaultUrl(),
                    $reportData['category_id'],
                    $reportData['faq_id'],
                    $reportData['faq_language'],
                )));
            }

            if (isset($data->visits)) {
                $text[$i][] = $reportData['faq_visits'];
            }
        }

        $handle = fopen('php://temp', 'r+');
        foreach ($text as $row) {
            fputcsv($handle, $row, ',', '"', '\\', PHP_EOL);
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
