<?php

declare(strict_types=1);

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
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('./admin/api/session/export')]
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $requestData = json_decode($request->getContent());

        if (!Token::getInstance($this->container->get('session'))->verifyToken('export-sessions', $requestData->csrf)) {
            return $this->json(['error' => Translation::get('msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $session = $this->container->get('phpmyfaq.admin.session');
        $data = $session->getSessionsByDate(
            strtotime((string) $requestData->firstHour),
            strtotime((string) $requestData->lastHour),
        );
        $filePath = tempnam(sys_get_temp_dir(), 'csv_');
        $file = fopen($filePath, 'w');
        if ($file) {
            foreach ($data as $row) {
                fputcsv($file, [$row['ip'], $row['time']], ',', '"', '\\', PHP_EOL);
            }

            fclose($file);
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
