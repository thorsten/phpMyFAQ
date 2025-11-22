<?php

declare(strict_types=1);

/**
 * The Administration Elasticsearch Controller
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
 * @since     2025-05-09
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class OpenSearchController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     */
    #[Route(path: '/opensearch', name: 'admin.opensearch', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        if (!$this->configuration->get(item: 'search.enableOpenSearch')) {
            throw new UnauthorizedHttpException('You are not allowed to access this page.');
        }

        return $this->render('@admin/configuration/opensearch.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
        ]);
    }
}
