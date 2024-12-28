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
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-12-28
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

class MediaBrowserController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     */
    #[Route('/media-browser', name: 'admin.media.browser', methods: ['GET'])]
    public function index(): Response
    {
        $this->userHasPermission(PermissionType::FAQ_EDIT);

        $allowedExtensions = ['png', 'gif', 'jpg', 'jpeg', 'mov', 'mpg', 'mp4', 'ogg', 'wmv', 'avi', 'webm'];

        $images = [];
        if (is_dir(PMF_CONTENT_DIR . '/user/images')) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(PMF_CONTENT_DIR . '/user/images'));
            foreach ($files as $file) {
                if ($file->isDir() || !in_array(strtolower($file->getExtension()), $allowedExtensions)) {
                    continue;
                }

                $path = str_replace(dirname(__DIR__, 4) . '/', '', (string)$file->getPath());
                $images[] = $this->configuration->getDefaultUrl() . $path . '/' . $file->getFilename();
            }
        }

        return $this->render(
            '@admin/content/media.browser.twig',
            [
                'msgNotAuthenticated' => Translation::get('msgNoPermission'),
                'msgMediaSearch' => Translation::get('ad_media_name_search'),
                'isImageDirectoryMissing' => !is_dir(PMF_CONTENT_DIR . '/user/images'),
                'msgImageDirectoryMissing' => sprintf(Translation::get('ad_dir_missing'), '/images'),
                'images' => $images,
            ]
        );
    }
}
