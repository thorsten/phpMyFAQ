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
use phpMyFAQ\Administration\FileDownloader;
use phpMyFAQ\Administration\Report;
use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
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
    public function __construct(
        private readonly Faq $faq,
    ) {
        parent::__construct();
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'export/file', name: 'admin.api.export.file', methods: ['POST'])]
    public function exportFile(Request $request): Response
    {
        $this->userHasPermission(PermissionType::EXPORT);

        $csrfToken = Filter::filterVar($request->request->get('pmf-csrf-token'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken('export', $csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $categoryId = (int) Filter::filterVar($request->request->get('categoryId'), FILTER_VALIDATE_INT);
        $downwards = Filter::filterVar($request->request->get('downwards'), FILTER_VALIDATE_BOOLEAN, false);
        $inlineDisposition = Filter::filterVar($request->request->get('disposition'), FILTER_SANITIZE_SPECIAL_CHARS);
        $type = Filter::filterVar($request->request->get('export-type'), FILTER_SANITIZE_SPECIAL_CHARS, 'none');

        $category = new Category($this->configuration, [], false);
        $category->buildCategoryTree($categoryId);

        try {
            $export = Export::create($this->faq, $category, $this->configuration, $type);
            $content = $export->generate($categoryId, $downwards, $this->configuration->getLanguage()->getLanguage());

            // Build the streaming response so the HTTP kernel can send it
            $httpStreamer = new FileDownloader($type, $content);
            $disposition = 'inline' === $inlineDisposition
                ? HeaderUtils::DISPOSITION_INLINE
                : HeaderUtils::DISPOSITION_ATTACHMENT;

            return $httpStreamer->getResponse($disposition);
        } catch (Exception|JsonException|CommonMarkException $exception) {
            return new Response($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'export/report', name: 'admin.api.export.report', methods: ['POST'])]
    public function exportReport(Request $request): Response
    {
        $this->userHasPermission(PermissionType::REPORTS);

        $data = $this->getJsonObject($request)->data ?? null;
        if (!$data instanceof \stdClass) {
            return $this->json(['error' => 'The request body must contain a data object.'], Response::HTTP_BAD_REQUEST);
        }

        if (!Token::getInstance($this->session)->verifyToken(
            'create-report',
            (string) ($data->{'pmf-csrf-token'} ?? ''),
        )) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $text = [];
        $text[0] = [];
        if ($this->hasDataField(payload: $data, field: 'category')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_category');
        }

        if ($this->hasDataField(payload: $data, field: 'sub_category')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_sub_category');
        }

        if ($this->hasDataField(payload: $data, field: 'translations')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_translations');
        }

        if ($this->hasDataField(payload: $data, field: 'language')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_language');
        }

        if ($this->hasDataField(payload: $data, field: 'id')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_id');
        }

        if ($this->hasDataField(payload: $data, field: 'sticky')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_sticky');
        }

        if ($this->hasDataField(payload: $data, field: 'title')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_title');
        }

        if ($this->hasDataField(payload: $data, field: 'creation_date')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_creation_date');
        }

        if ($this->hasDataField(payload: $data, field: 'owner')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_owner');
        }

        if ($this->hasDataField(payload: $data, field: 'last_modified_person')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_last_modified_person');
        }

        if ($this->hasDataField(payload: $data, field: 'url')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_url');
        }

        if ($this->hasDataField(payload: $data, field: 'visits')) {
            $text[0][] = Translation::get(key: 'ad_stat_report_visits');
        }

        try {
            return $this->buildReportResponse(new Report($this->configuration), $data, $text);
        } catch (\Throwable $throwable) {
            return $this->json(['error' => $throwable->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Builds the CSV report response from the requested fields.
     *
     * @param array<int, list<mixed>> $text
     */
    private function buildReportResponse(Report $report, \stdClass $data, array $text): Response
    {
        foreach ($report->getReportingData() as $reportData) {
            $i = (int) $reportData['faq_id'];
            // Top-level categories have no parent; normalise the absent/NULL
            // value to 0 so it is treated as "no parent" instead of being
            // passed on as NULL.
            $categoryParent = (int) ($reportData['category_parent'] ?? 0);
            if (
                $this->hasDataField(payload: $data, field: 'category') && array_key_exists('category_name', $reportData)
            ) {
                $text[$i][] = Report::sanitize($report->convertEncoding((string) ($reportData['category_name'] ?? '')));
                if (0 !== $categoryParent) {
                    $text[$i][] = Report::sanitize($categoryParent);
                }
            }

            if ($this->hasDataField(payload: $data, field: 'sub_category')) {
                $text[$i][] = 'n/a';
                if (0 !== $categoryParent) {
                    $text[$i][] = Report::sanitize($report->convertEncoding(
                        (string) ($reportData['category_name'] ?? ''),
                    ));
                }
            }

            if ($this->hasDataField(payload: $data, field: 'translations')) {
                $text[$i][] = $reportData['faq_translations'];
            }

            $faqLanguage = (string) ($reportData['faq_language'] ?? '');
            if ($this->hasDataField(payload: $data, field: 'language') && LanguageCodes::get($faqLanguage) !== null) {
                $text[$i][] = $report->convertEncoding(LanguageCodes::get($faqLanguage) ?? '');
            }

            if ($this->hasDataField(payload: $data, field: 'id')) {
                $text[$i][] = $reportData['faq_id'];
            }

            if ($this->hasDataField(payload: $data, field: 'sticky')) {
                $text[$i][] = $reportData['faq_sticky'];
            }

            if ($this->hasDataField(payload: $data, field: 'title')) {
                $text[$i][] = Report::sanitize($report->convertEncoding((string) ($reportData['faq_question'] ?? '')));
            }

            if ($this->hasDataField(payload: $data, field: 'creation_date')) {
                $text[$i][] = $reportData['faq_updated'];
            }

            if ($this->hasDataField(payload: $data, field: 'owner')) {
                $text[$i][] = Report::sanitize($report->convertEncoding(
                    (string) ($reportData['faq_org_author'] ?? ''),
                ));
            }

            $text[$i][] = '';
            if (
                $this->hasDataField(payload: $data, field: 'last_modified_person')
                && array_key_exists('faq_last_author', $reportData)
            ) {
                $text[$i][] = Report::sanitize($report->convertEncoding(
                    (string) ($reportData['faq_last_author'] ?? ''),
                ));
            }

            if ($this->hasDataField(payload: $data, field: 'url')) {
                $text[$i][] = Report::sanitize($report->convertEncoding(sprintf(
                    '%scontent/%d/%d/%s/%s.html',
                    $this->configuration->getDefaultUrl(),
                    (int) ($reportData['category_id'] ?? 0),
                    (int) $reportData['faq_id'],
                    $faqLanguage,
                    TitleSlugifier::slug((string) ($reportData['faq_question'] ?? '')),
                )));
            }

            if ($this->hasDataField(payload: $data, field: 'visits')) {
                $text[$i][] = $reportData['faq_visits'];
            }
        }

        $handle = fopen('php://temp', mode: 'r+');
        foreach ($text as $row) {
            fputcsv($handle, fields: $row, separator: ',', enclosure: '"', escape: '\\');
        }

        rewind($handle);

        $content = (string) stream_get_contents($handle);

        fclose($handle);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="report.csv"');

        return $response;
    }

    /**
     * Returns true when the report payload requests the given field.
     */
    private function hasDataField(\stdClass $payload, string $field): bool
    {
        return property_exists($payload, $field) && $payload->{$field} !== null;
    }
}
