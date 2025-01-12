<?php

/**
 * The Update Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-06
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use DateTime;
use DateTimeInterface;
use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UpdateController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    /**
     * @throws \Exception
     */
    #[OA\Post(path: '/api/v3.1/update', operationId: 'triggerUpdate', tags: ['Endpoints with Authentication'])]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the new and updated phpMyFAQ version number as string.',
        content: new OA\JsonContent(example: '4.0.0'),
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated and/or does not have sufficient permissions.'
    )]
    public function index(): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $upgrade = $this->container->get('phpmyfaq.setup.upgrade');
        $branch = $this->configuration->get('upgrade.releaseEnvironment');

        // Check if the maintenance mode is enabled
        if (!$upgrade->isMaintenanceEnabled()) {
            return $this->json(['error' => Translation::get('msgNotInMaintenanceMode')], Response::HTTP_CONFLICT);
        }

        // Fetch latest version
        try {
            $versions = $this->container->get('phpmyfaq.admin.api')->getVersions();

            if (version_compare($versions['installed'], $versions[$branch], '<')) {
                $versionNumber = $versions[$branch];
            }
        } catch (TransportExceptionInterface | DecodingExceptionInterface $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        // Fetch a package version
        $pathToPackage = $upgrade->downloadPackage($versionNumber);

        if ($pathToPackage === false) {
            return $this->json(['error' => Translation::get('downloadFailure')], Response::HTTP_BAD_GATEWAY);
        }

        if (!$upgrade->isNightly()) {
            $result = $upgrade->verifyPackage($pathToPackage, $versionNumber);
            if ($result === false) {
                return $this->json(['error' => Translation::get('verificationFailure')], Response::HTTP_BAD_GATEWAY);
            }
        }

        $this->configuration->set('upgrade.lastDownloadedPackage', urlencode($pathToPackage));

        // Extract package
        // WORK IN PROGESS

        return $this->json($this->configuration->getVersion());
    }
}
