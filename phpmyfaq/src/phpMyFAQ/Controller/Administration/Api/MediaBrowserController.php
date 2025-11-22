<?php

/**
 * The Administration Media Browser Controller
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
 * @since     2024-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use DateTime;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Translation;
use phpMyFAQ\Utils;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class MediaBrowserController extends AbstractController
{
    /**
     * @throws LoaderError
     * @throws Exception
     */
    #[Route(path: 'admin/api/media-browser', name: 'admin.media.browser', methods: ['GET'])]
    public function index(Request $request): JsonResponse|Response
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $allowedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'mov', 'mpg', 'mp4', 'ogg', 'wmv', 'avi', 'webm'];

        if (!is_dir(PMF_CONTENT_DIR . '/user/images')) {
            return $this->json(['error' => sprintf(
                Translation::get(languageKey: 'ad_dir_missing'),
                '/images',
            )], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent());
        $action = Filter::filterVar($data->action, FILTER_SANITIZE_SPECIAL_CHARS);

        if ($action === 'fileRemove') {
            $file = Filter::filterVar($data->name, FILTER_SANITIZE_SPECIAL_CHARS);
            $file = PMF_CONTENT_DIR . '/user/images/' . $file;

            if (file_exists($file)) {
                unlink($file);
            }

            $response = [
                'success' => true,
                'data' => [
                    'code' => 220,
                ],
            ];

            return $this->json($response, Response::HTTP_OK);
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PMF_CONTENT_DIR . '/user/images'));
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                continue;
            }

            if (!in_array(strtolower((string) $file->getExtension()), $allowedExtensions)) {
                continue;
            }

            $files[] = [
                'file' => $file->getFilename(),
                'size' => Utils::formatBytes($file->getSize()),
                'isImage' => true,
                'thumb' => $file->getFilename(),
                'changed' => date(
                    format: 'Y-m-d H:i:s',
                    timestamp: $file->getMTime(),
                ),
            ];
        }

        $response = [
            'success' => true,
            'time' => (new DateTime())->format('Y-m-d H:i:s'),
            'data' => [
                'sources' => [
                    [
                        'baseurl' => $this->configuration->getDefaultUrl(),
                        'path' => 'content/user/images/',
                        'files' => $files,
                        'name' => 'default',
                    ],
                ],
                'code' => 220,
            ],
        ];

        return $this->json($response, Response::HTTP_OK);
    }
}
