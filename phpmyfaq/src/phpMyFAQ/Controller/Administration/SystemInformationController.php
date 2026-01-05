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
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use Elastic\Elasticsearch\Exception\ClientResponseException;
use Elastic\Elasticsearch\Exception\ServerResponseException;
use phpMyFAQ\Administration\TranslationStatistics;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Translation;
use phpMyFAQ\Twig\Extensions\LanguageCodeTwigExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Extension\AttributeExtension;

final class SystemInformationController extends AbstractAdministrationController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: '/system', name: 'admin.system', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->userHasPermission(PermissionType::CONFIGURATION_EDIT);

        $faqSystem = $this->container->get(id: 'phpmyfaq.system');

        if ($this->configuration->get(item: 'search.enableElasticsearch')) {
            try {
                $esFullInformation = $this->configuration->getElasticsearch()->info();
                $esInformation = $esFullInformation['version']['number'];
            } catch (ClientResponseException|ServerResponseException $e) {
                $this->configuration->getLogger()->error('Error while fetching Elasticsearch information', [$e->getMessage()]);
                $esInformation = 'n/a';
            }
        }

        if (!$this->configuration->get(item: 'search.enableElasticsearch')) {
            $esInformation = 'n/a';
        }

        $openSearchInformation = '';
        if ($this->configuration->get(item: 'search.enableOpenSearch')) {
            $openSearchFullInformation = $this->configuration->getOpenSearch()->info();
            $openSearchInformation = $openSearchFullInformation['version']['number'];
        }

        if (!$this->configuration->get(item: 'search.enableOpenSearch')) {
            $openSearchInformation = 'n/a';
        }

        $translationInformation = new TranslationStatistics();
        $translationStatistics = $translationInformation->getStatistics();

        $this->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));
        return $this->render('@admin/configuration/system.twig', [
            ...$this->getHeader($request),
            ...$this->getFooter(),
            'adminHeaderSystemInfo' => Translation::get(key: 'ad_system_info'),
            'systemInformation' => [
                'phpMyFAQ Version' => $faqSystem->getVersion(),
                'phpMyFAQ API Version' => $faqSystem->getApiVersion(),
                'phpMyFAQ Plugin API Version' => $faqSystem->getPluginVersion(),
                'phpMyFAQ Installation Path' => dirname((string) $request->server->get('SCRIPT_FILENAME'), levels: 2),
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
            'translationInformation' => $translationStatistics,
        ]);
    }
}
