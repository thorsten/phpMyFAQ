<?php

/**
 * The Sess Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-01-13
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Session;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;

class SessionController extends AbstractController
{

    #[Route('./admin/api/session/export')]
    public function export(Request $request): BinaryFileResponse|JsonResponse
    {
        $config = Configuration::getConfigurationInstance();
        $requestData = json_decode($request->getContent());
        
        if (!Token::getInstance()->verifyToken('export-sessions', $requestData->csrf)) {
            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $session = new Session($config);
        $data = $session->getSessionsByDate($requestData->firstHour, $requestData->lastHour);
        $filePath = tempnam(sys_get_temp_dir(), 'csv_');
        $file = fopen($filePath, 'w');
        if (file) {
            foreach ($data as $row) {
                fputcsv($file, array($row['ip'], $row['time']));
            }
            fclose($file);
            $response = new BinaryFileResponse($filePath);
            $response->setContentDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'sessions_' . date(DATE_RSS, $requestData->firstHour) . '-' . date(DATE_RSS, $requestData->lastHour) . '.csv'
            );
            $response->headers->set('Content-Type', 'application/octet-stream');
        }
        else {
            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_BAD_REQUEST);
            $response->setData(['error' => 'Unable to open file.']);
        }

        return $response;
    }

}
