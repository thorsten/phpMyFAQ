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
 * @copyright 2024 phpMyFAQ Team
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

class ImportController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/import', name: 'admin.import', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::FAQ_ADD);

        return $this->render(
            '@admin/import-export/import.twig',
            [
                ... $this->getHeader($request),
                ... $this->getFooter(),
                'adminHeaderImport' => Translation::get('msgImportRecords'),
                'adminHeaderCSVImport' => Translation::get('msgImportCSVFile'),
                'adminBodyCSVImport' => Translation::get('msgImportCSVFileBody'),
                'adminImportLabel' => Translation::get('ad_csv_file'),
                'adminCSVImport' => Translation::get('msgImport'),
                'adminHeaderCSVImportColumns' => Translation::get('msgColumnStructure'),
                'categoryId' => Translation::get('ad_categ_categ'),
                'question' => Translation::get('ad_entry_topic'),
                'answer' => Translation::get('ad_entry_content'),
                'keywords' => Translation::get('ad_entry_keywords'),
                'author' => Translation::get('ad_entry_author'),
                'email' => Translation::get('msgEmail'),
                'languageCode' => Translation::get('msgLanguageCode'),
                'seperateWithCommas' => Translation::get('msgSeperateWithCommas'),
                'tags' => Translation::get('ad_entry_tags'),
                'msgImportRecordsColumnStructure' => Translation::get('msgImportRecordsColumnStructure'),
                'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('importfaqs'),
                'is_active' => Translation::get('ad_entry_active'),
                'is_sticky' => Translation::get('ad_entry_sticky'),
                'trueFalse' => Translation::get('msgCSVImportTrueOrFalse')
            ]
        );
    }
}
