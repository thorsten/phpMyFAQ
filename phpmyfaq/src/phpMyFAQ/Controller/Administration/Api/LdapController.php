<?php

/**
 * The Admin LDAP API Controller
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-03-05
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Ldap;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LdapController extends AbstractController
{
    public function __construct(
        private readonly Ldap $ldap,
    ) {
        parent::__construct();
    }

    #[Route(path: 'ldap/configuration', name: 'admin.api.ldap.configuration', methods: ['GET'])]
    public function configuration(): JsonResponse
    {
        $this->userIsAuthenticated();

        $servers = $this->configuration->getLdapServer();
        $strippedServers = array_map(static function (array $server): array {
            $server['ldap_password'] = '********';
            return $server;
        }, $servers);

        return $this->json([
            'servers' => $strippedServers,
            'mapping' => $this->configuration->getLdapMapping(),
            'options' => $this->configuration->getLdapOptions(),
            'groupConfig' => $this->configuration->getLdapGroupConfig(),
            'generalSettings' => [
                'domainPrefix' => (bool) $this->configuration->get(item: 'ldap.ldap_use_domain_prefix'),
                'sasl' => (bool) $this->configuration->get(item: 'ldap.ldap_use_sasl'),
                'anonymousLogin' => (bool) $this->configuration->get(item: 'ldap.ldap_use_anonymous_login'),
                'dynamicLogin' => (bool) $this->configuration->get(item: 'ldap.ldap_use_dynamic_login'),
                'dynamicLoginAttribute' => $this->configuration->get(item: 'ldap.ldap_dynamic_login_attribute') ?? '',
                'multipleServers' => (bool) $this->configuration->get(item: 'ldap.ldap_use_multiple_servers'),
            ],
        ], Response::HTTP_OK);
    }

    #[Route(path: 'ldap/healthcheck', name: 'admin.api.ldap.healthcheck', methods: ['GET'])]
    public function healthcheck(): JsonResponse
    {
        $this->userIsAuthenticated();

        if (!extension_loaded('ldap')) {
            return $this->json([
                'available' => false,
                'status' => 'unavailable',
                'error' => 'PHP LDAP extension is not loaded.',
                'servers' => [],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $servers = $this->configuration->getLdapServer();

        if (empty($servers)) {
            return $this->json([
                'available' => false,
                'status' => 'unavailable',
                'error' => 'No LDAP server configured.',
                'servers' => [],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $allHealthy = true;
        $serverResults = [];

        foreach ($servers as $index => $server) {
            $ldap = new Ldap($this->configuration);
            $connected = $ldap->connect(
                $server['ldap_server'] ?? '',
                (int) ($server['ldap_port'] ?? 389),
                $server['ldap_base'] ?? '',
                $server['ldap_user'] ?? '',
                $server['ldap_password'] ?? '',
            );

            $serverResults[] = [
                'index' => $index,
                'host' => $server['ldap_server'] ?? '',
                'available' => $connected,
                'error' => $connected ? null : $ldap->error ?? 'Unable to connect.',
            ];

            if (!$connected) {
                $allHealthy = false;
            }
        }

        return $this->json(
            [
                'available' => $allHealthy,
                'status' => $allHealthy ? 'healthy' : 'degraded',
                'servers' => $serverResults,
            ],
            $allHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE,
        );
    }
}
