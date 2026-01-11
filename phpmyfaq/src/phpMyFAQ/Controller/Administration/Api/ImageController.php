<?php

/**
 * The Admin Image Controller
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
 * @since     2023-10-26
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use DateTime;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ImageController extends AbstractController
{
    /**
     * @throws Exception|\Exception
     */
    #[Route(path: 'admin/api/content/images')]
    public function upload(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $uploadDir = PMF_CONTENT_DIR . '/user/images/';
        $validFileExtensions = ['gif', 'jpg', 'jpeg', 'png', 'webp', 'svg', 'mov', 'mp4', 'webm'];
        $timestamp = time();

        if (!Token::getInstance($this->session)->verifyToken('pmf-csrf-token', $request->query->get('csrf'))) {
            return $this->json([
                'success' => false,
                'data' => ['code' => Response::HTTP_UNAUTHORIZED],
                'messages' => [Translation::get(key: 'msgNoPermission')],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $files = $request->files->get('files');

        $uploadedFiles = [];
        $headers = [];
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            if (!$file->isValid()) {
                continue;
            }
            if (
                $request->server->get('HTTP_ORIGIN') !== null
                && $request->server->get('HTTP_ORIGIN') . '/' === $this->configuration->getDefaultUrl()
            ) {
                $headers = ['Access-Control-Allow-Origin', $request->server->get('HTTP_ORIGIN')];
            }

            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", (string) $file->getClientOriginalName())) {
                return $this->json(
                    [
                        'success' => false,
                        'data' => ['code' => Response::HTTP_BAD_REQUEST],
                        'messages' => ['Data contains invalid characters'],
                    ],
                    Response::HTTP_BAD_REQUEST,
                    $headers,
                );
            }

            // Verify extension
            if (!in_array(
                strtolower((string) $file->getClientOriginalExtension()),
                $validFileExtensions,
                strict: true,
            )) {
                return $this->json(
                    [
                        'success' => false,
                        'data' => ['code' => Response::HTTP_BAD_REQUEST],
                        'messages' => ['File extension not allowed'],
                    ],
                    Response::HTTP_BAD_REQUEST,
                    $headers,
                );
            }

            // Accept upload if there was no origin or if it is an accepted origin
            $fileName = $timestamp . '_' . $file->getClientOriginalName();
            $fileName = str_replace(' ', replace: '_', subject: $fileName);
            $file->move($uploadDir, $fileName);

            // Add to the list of uploaded files
            $uploadedFiles[] = $fileName;
        }

        // Build full URLs for Jodit editor
        $fileUrls = array_map(
            fn($file) => $this->configuration->getDefaultUrl() . 'content/user/images/' . $file,
            $uploadedFiles,
        );

        $response = [
            'success' => true,
            'time' => new DateTime()->format('Y-m-d H:i:s'),
            'data' => [
                'messages' => ['Files uploaded successfully'],
                'files' => $fileUrls, // For Jodit uploader
                'isImages' => array_map(
                    fn($file) => !in_array(pathinfo($file, PATHINFO_EXTENSION), ['mov', 'mp4', 'webm']),
                    $uploadedFiles,
                ),
                'sources' => [
                    [
                        'baseurl' => $this->configuration->getDefaultUrl(),
                        'path' => 'content/user/images/',
                        'files' => $uploadedFiles,
                        'name' => 'default',
                    ],
                ],
                'code' => 220,
            ],
        ];

        return $this->json($response, Response::HTTP_OK);
    }
}
