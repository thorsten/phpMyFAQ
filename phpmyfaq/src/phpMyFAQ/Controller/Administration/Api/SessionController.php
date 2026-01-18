<?php

/**
 * The Session Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class SessionController extends AbstractAdministrationApiController
{
    /**
     * @throws Exception
     */
    #[Route(path: './admin/api/session/export', name: 'admin.api.session.export', methods: ['POST'])]
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $requestData = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('export-sessions', $requestData->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->container->get(id: 'phpmyfaq.admin.session');
        $data = $session->getSessionsByDate(
            strtotime((string) $requestData->firstHour),
            strtotime((string) $requestData->lastHour),
        );
        $filePath = tempnam(sys_get_temp_dir(), prefix: 'csv_');
        $file = fopen($filePath, mode: 'w');
        if ($file) {
            foreach ($data as $row) {
                fputcsv($file, [$row['ip'], $row['time']], separator: ',', enclosure: '"', eol: PHP_EOL);
            }

            fclose($file);

            $this->adminLog->log($this->currentUser, AdminLogType::DATA_EXPORT_SESSIONS->value);

            $binaryFileResponse = new BinaryFileResponse($filePath);
            $binaryFileResponse->setContentDisposition(
                ResponseHeaderBag::DISPOSITION_INLINE,
                'sessions_' . $requestData->firstHour . '-' . $requestData->lastHour . '.csv',
            );
            $binaryFileResponse->headers->set('Content-Type', 'text/csv');
            return $binaryFileResponse;
        }

        return $this->json(['error' => 'Unable to open file.'], Response::HTTP_BAD_REQUEST);
    }
}
