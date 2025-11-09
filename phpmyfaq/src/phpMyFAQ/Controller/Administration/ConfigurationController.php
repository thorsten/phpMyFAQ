<?php

/**
 * The Administration Configuration Controller
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
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class ConfigurationController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws LoaderError
     * @throws \Exception
     */
    #[Route('/configuration', name: 'admin.instances', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        return $this->render('@admin/configuration/main.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderConfiguration' => Translation::get(languageKey: 'ad_config_edit'),
            'csrfToken' => Token::getInstance($this->container->get('session'))->getTokenString('configuration'),
            'language' => $this->configuration->getLanguage()->getLanguage(),
            'adminConfigurationButtonReset' => Translation::get(languageKey: 'ad_config_reset'),
            'adminConfigurationButtonSave' => Translation::get(languageKey: 'ad_config_save'),
        ]);
    }
}
