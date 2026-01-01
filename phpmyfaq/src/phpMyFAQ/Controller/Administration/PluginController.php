<?php

/**
 * The Plugin Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-01-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Core\Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class PluginController extends AbstractAdministrationController
{
    /**
     * @throws LoaderError
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/plugins')]
    public function index(Request $request): Response
    {
        $pluginManager = $this->container->get(id: 'phpmyfaq.plugin.plugin-manager');
        $pluginManager->loadPlugins();

        return $this->render('@admin/configuration/plugins.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'pluginList' => $pluginManager->getPlugins(),
            'incompatiblePlugins' => $pluginManager->getIncompatiblePlugins(),
        ]);
    }
}
