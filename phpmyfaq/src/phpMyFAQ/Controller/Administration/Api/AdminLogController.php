<?php

/**
 * The Admin Log API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use Exception;
use JsonException;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminLogController extends AbstractAdministrationApiController
{
    /**
     * @throws \phpMyFAQ\Core\Exception|JsonException
     * @throws Exception
     */
    #[Route(path: 'statistics/admin-log', name: 'admin.api.statistics.admin-log.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_VIEWLOGS);

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->session)->verifyToken('delete-adminlog', $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        if ($this->container->get(id: 'phpmyfaq.admin.admin-log')->delete()) {
            return $this->json(['success' => Translation::get(key: 'ad_adminlog_delete_success')], Response::HTTP_OK);
        }

        return $this->json(['error' => Translation::get(
            key: 'ad_adminlog_delete_failure',
        )], Response::HTTP_BAD_REQUEST);
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'statistics/admin-log/export', name: 'admin.api.statistics.admin-log.export', methods: ['POST'])]
    public function export(Request $request): Response|JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_ADMINLOG);

        $data = json_decode($request->getContent(), associative: false, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (!Token::getInstance($this->session)->verifyToken('export-adminlog', $data->csrf)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $loggingData = $this->adminLog->getAll();

        $handle = fopen('php://temp', 'r+');
        fputcsv(
            $handle,
            ['ID', 'Date/Time', 'User ID', 'Username', 'IP Address', 'Action'],
            separator: ',',
            enclosure: '"',
            eol: PHP_EOL,
        );

        foreach ($loggingData as $log) {
            $user = new User($this->configuration);
            $user->getUserById($log->getUserId());
            $username = $user->getLogin();

            fputcsv(
                $handle,
                [
                    $log->getId(),
                    date('Y-m-d H:i:s', $log->getTime()),
                    $log->getUserId(),
                    $username,
                    $log->getIp(),
                    $log->getText(),
                ],
                separator: ',',
                enclosure: '"',
                eol: PHP_EOL,
            );
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        $this->adminLog->log($this->currentUser, AdminLogType::DATA_EXPORT_LOGS->value);

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            'attachment; filename="admin-log-export-' . date('Y-m-d-His') . '.csv"',
        );

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route(path: 'statistics/admin-log/verify', name: 'admin.api.statistics.admin-log.verify', methods: ['GET'])]
    public function verify(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::STATISTICS_ADMINLOG);

        $csrfToken = Filter::filterVar($request->query->get('csrf'), FILTER_SANITIZE_SPECIAL_CHARS);
        if (!Token::getInstance($this->session)->verifyToken('admin-log-verify', $csrfToken)) {
            return $this->json(['error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $this->adminLog->verifyChainIntegrity();

            return $this->json(
                [
                    'success' => true,
                    'verification' => [
                        'valid' => $result['valid'],
                        'total' => $result['total'],
                        'verified' => $result['verified'],
                        'failed' => $result['total'] - $result['verified'],
                        'errors' => $result['errors'],
                    ],
                    'message' => $result['valid']
                        ? 'Admin log integrity verified successfully'
                        : 'Admin log integrity check failed - tampering detected',
                ],
                $result['valid'] ? Response::HTTP_OK : Response::HTTP_CONFLICT,
            );
        } catch (Exception $exception) {
            return $this->json([
                'success' => false,
                'error' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
