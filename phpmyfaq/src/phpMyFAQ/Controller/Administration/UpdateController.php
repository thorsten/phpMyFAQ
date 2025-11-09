<?php

/**
 * The Administration Update Controller
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
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Enums\ReleaseType;
use phpMyFAQ\Session\Token;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class UpdateController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/update', name: 'admin.update', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $session = $this->container->get('session');

        $isOnNightlies = $this->configuration->get(item: 'upgrade.releaseEnvironment') === ReleaseType::NIGHTLY->value;

        return $this->render('@admin/configuration/upgrade.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'csrfActivateMaintenanceMode' => Token::getInstance($session)->getTokenString('activate-maintenance-mode'),
            'isOnNightlies' => $isOnNightlies,
            'releaseEnvironment' => ucfirst((string) $this->configuration->get(item: 'upgrade.releaseEnvironment')),
            'dateLastChecked' => $this->configuration->get(item: 'upgrade.dateLastChecked'),
            'versionCurrent' => $this->configuration->get(item: 'main.currentVersion'),
        ]);
    }
}
