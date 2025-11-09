<?php

/**
 * The Administration System Information Controller
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

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use Elastic\Transport\Exception\NoNodeAvailableException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SystemInformationController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route('/system', name: 'admin.system', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $faqSystem = $this->container->get('phpmyfaq.system');

        if ($this->configuration->get('search.enableElasticsearch')) {
            try {
                $esFullInformation = $this->configuration->getElasticsearch()->info();
                $esInformation = $esFullInformation['version']['number'];
            } catch (ClientResponseException|ServerResponseException|NoNodeAvailableException $e) {
                $this->configuration->getLogger()->error('Error while fetching Elasticsearch information', [$e->getMessage()]);
                $esInformation = 'n/a';
            }
        } else {
            $esInformation = 'n/a';
        }

        if ($this->configuration->get('search.enableOpenSearch')) {
            try {
                $openSearchFullInformation = $this->configuration->getOpenSearch()->info();
                $openSearchInformation = $openSearchFullInformation['version']['number'];
            } catch (NoNodeAvailableException $e) {
                $this->configuration->getLogger()->error('Error while fetching OpenSearch information', [$e->getMessage()]);
                $openSearchInformation = 'n/a';
            }
        } else {
            $openSearchInformation = 'n/a';
        }

        return $this->render('@admin/configuration/system.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderSystemInfo' => Translation::get(languageKey: 'ad_system_info'),
            'systemInformation' => [
                'phpMyFAQ Version' => $faqSystem->getVersion(),
                'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
                'phpMyFAQ Plugin API Version' => $faqSystem->getPluginVersion(),
                'phpMyFAQ Installation Path' => dirname((string) $request->server->get('SCRIPT_FILENAME'), 2),
                'Web server software' => $request->server->get('SERVER_SOFTWARE'),
                'Web server document root' => $request->server->get('DOCUMENT_ROOT'),
                'Web server Interface' => strtoupper(PHP_SAPI),
                'PHP Version' => PHP_VERSION,
                'PHP Extensions' => implode(', ', get_loaded_extensions()),
                'Database Driver' => Database::getType(),
                'Database Server Version' => $this->configuration->getDb()->serverVersion(),
                'Database Client Version' => $this->configuration->getDb()->clientVersion(),
                'Elasticsearch Version' => $esInformation,
                'OpenSearch Version' => $openSearchInformation,
            ],
        ]);
    }
}
