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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-12-23
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\HttpStreamer;
use phpMyFAQ\Administration\Report;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Export;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ExportController extends AbstractController
{
    #[Route('admin/api/export/file')]
    public function exportFile(Request $request): void
    {
        $this->userHasPermission(PermissionType::EXPORT);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);
        $downwards = Filter::filterVar($request->get('downwards'), FILTER_VALIDATE_BOOLEAN, false);
        $inlineDisposition = Filter::filterVar($request->get('disposition'), FILTER_SANITIZE_SPECIAL_CHARS);
        $type = Filter::filterVar($request->get('export-type'), FILTER_SANITIZE_SPECIAL_CHARS, 'none');

        $configuration = Configuration::getConfigurationInstance();

        $faq = new Faq($configuration);
        $category = new Category($configuration, [], false);
        $category->buildCategoryTree($categoryId);

        try {
            $export = Export::create($faq, $category, $configuration, $type);
            $content = $export->generate($categoryId, $downwards, $configuration->getLanguage()->getLanguage());

            // Stream the file content
            $httpStreamer = new HttpStreamer($type, $content);
            if ('inline' === $inlineDisposition) {
                $httpStreamer->send(HeaderUtils::DISPOSITION_INLINE);
            } else {
                $httpStreamer->send(HeaderUtils::DISPOSITION_ATTACHMENT);
            }
        } catch (Exception | \JsonException $e) {
            echo $e->getMessage();
        }
    }

    #[Route('admin/api/export/file')]
    public function exportReport(Request $request): void
    {
        $this->userHasPermission(PermissionType::REPORTS);

        $configuration = Configuration::getConfigurationInstance();
        $report = new Report($configuration);
        $columns = $request->request->all();

        $text = [];
        $text[0] = [];

        foreach ($columns as $column => $value) {
            $text[0][] = Translation::get('ad_stat_' . $column);
        }

        var_dump($text);
    }
}
