<?php

/**
 * The Administration Import Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class ImportController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route(path: '/import', name: 'admin.import', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        return $this->render('@admin/import-export/import.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderImport' => Translation::get(key: 'msgImportRecords'),
            'adminHeaderCSVImport' => Translation::get(key: 'msgImportCSVFile'),
            'adminBodyCSVImport' => Translation::get(key: 'msgImportCSVFileBody'),
            'adminImportLabel' => Translation::get(key: 'ad_csv_file'),
            'adminCSVImport' => Translation::get(key: 'msgImport'),
            'adminHeaderCSVImportColumns' => Translation::get(key: 'msgColumnStructure'),
            'categoryId' => Translation::get(key: 'ad_categ_categ'),
            'question' => Translation::get(key: 'ad_entry_topic'),
            'languageCode' => Translation::get(key: 'msgLanguageCode'),
            'msgImportRecordsColumnStructure' => Translation::get(key: 'msgImportRecordsColumnStructure'),
            'csrfToken' => Token::getInstance($this->container->get(id: 'session'))->getTokenString('importfaqs'),
            'is_active' => Translation::get(key: 'ad_entry_active'),
            'trueFalse' => Translation::get(key: 'msgCSVImportTrueOrFalse'),
        ]);
    }
}
