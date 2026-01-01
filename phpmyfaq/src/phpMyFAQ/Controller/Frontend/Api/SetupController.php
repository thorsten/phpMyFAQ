<?php

/**
 * The Setup Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-06-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use Elastic\Elasticsearch\Exception\AuthenticationException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Setup\Installer;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use phpMyFAQ\Twig\TemplateException;
use phpMyFAQ\Twig\TwigWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Error\LoaderError;

final class SetupController
{
    /**
     * @throws TemplateException
     * @throws \Exception
     */
    #[Route(path: '/setup', name: 'public.setup.update')]
    public function index(Request $request): Response
    {
        $system = new System();
        $installer = new Installer($system);

        $checkBasicError = '';
        try {
            $installer->checkBasicStuff();
        } catch (Exception $exception) {
            $checkBasicError = $exception->getMessage();
        }

        try {
            $installer->checkInitialRewriteBasePath($request);
        } catch (Exception $exception) {
            $checkBasicError = $exception->getMessage();
        }

        return $this->render('@setup/index.twig', [
            'newVersion' => System::getVersion(),
            'setupType' => 'Setup',
            'currentYear' => date(format: 'Y'),
            'currentLanguage' => 'en',
            'documentationUrl' => System::getDocumentationUrl(),
            'checkBasicError' => $checkBasicError,
            'nonCriticalSettings' => $installer->checkNoncriticalSettings(),
            'filePermissions' => $installer->checkFilesystemPermissions(),
            'supportedDatabases' => $system->getSupportedSafeDatabases(),
            'currentPath' => dirname(__DIR__, 4),
            'isLdapEnabled' => $installer->hasLdapSupport(),
            'isElasticsearchEnabled' => $installer->hasElasticsearchSupport(),
            'supportedTranslations' => LanguageCodes::getAllSupported(),
        ]);
    }

    /**
     * @throws TemplateException
     * @throws \Exception
     */
    #[Route(path: '/setup/install', name: 'public.setup.install')]
    public function install(): Response
    {
        $system = new System();
        $installer = new Installer($system);

        $installationError = '';

        try {
            $installer->startInstall();
        } catch (Exception|AuthenticationException $exception) {
            $installationError = $exception->getMessage();
        }

        return $this->render('@setup/install.twig', [
            'newVersion' => System::getVersion(),
            'setupType' => 'Setup',
            'currentYear' => date(format: 'Y'),
            'documentationUrl' => System::getDocumentationUrl(),
            'installationError' => $installationError,
        ]);
    }

    /**
     * @throws TemplateException
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/update', name: 'public.setup.update')]
    public function update(Request $request): Response
    {
        $currentStep = (int) Filter::filterVar($request->query->get('step') ?? 1, FILTER_VALIDATE_INT);

        $configuration = Configuration::getConfigurationInstance();

        $update = new Update(new System(), $configuration);

        $checkBasicError = '';
        try {
            $update->checkInitialRewriteBasePath($request);
        } catch (Exception $exception) {
            $checkBasicError = $exception->getMessage();
        }

        return $this->render('@setup/update.twig', [
            'currentStep' => $currentStep,
            'installedVersion' => $configuration->getVersion(),
            'newVersion' => System::getVersion(),
            'checkBasicError' => $checkBasicError,
            'currentYear' => date(format: 'Y'),
            'documentationUrl' => System::getDocumentationUrl(),
            'configTableNotAvailable' => $update->isConfigTableNotAvailable($configuration->getDb()),
        ]);
    }

    /**
     * Returns a Twig-rendered template as a response.
     *
     * @param string[] $templateVars
     * @throws Exception|LoaderError
     */
    public function render(string $pathToTwigFile, array $templateVars = [], ?Response $response = null): Response
    {
        $response ??= new Response();
        $twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates', true);
        $templateWrapper = $twigWrapper->loadTemplate($pathToTwigFile);

        $response->setContent($templateWrapper->render($templateVars));

        return $response;
    }
}
