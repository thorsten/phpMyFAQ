<?php

/**
 * Orchestrator for the phpMyFAQ bootstrap sequence
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Bootstrap\ConfigDirectoryResolver;
use phpMyFAQ\Bootstrap\PhpConfigurator;
use phpMyFAQ\Bootstrap\SearchClientFactory;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Database\DatabaseDriver;
use Symfony\Component\HttpFoundation\Request;

class Bootstrapper
{
    private ?Configuration $faqConfig = null;

    private ?DatabaseDriver $db = null;

    private ?Request $request = null;

    /**
     * Executes the full bootstrap sequence.
     *
     * @throws DatabaseConnectionException
     */
    public function run(): self
    {
        // 1. PHP runtime configuration
        PhpConfigurator::fixIncludePath();
        PhpConfigurator::configurePcre();

        // 2. Environment
        Environment::init();

        // 3. Multisite support
        if (file_exists(PMF_ROOT_DIR . '/multisite/multisite.php') && 'cli' !== PHP_SAPI) {
            require PMF_ROOT_DIR . '/multisite/multisite.php';
        }

        // 4. Config directories
        ConfigDirectoryResolver::resolve();

        // 5. Database file detection (may redirect to setup)
        $databaseFile = ConfigDirectoryResolver::resolveDatabaseFile();

        // 6. Config-specific constants
        ConfigDirectoryResolver::loadConfigConstants();

        // 7. Translation directory
        if (!defined('PMF_TRANSLATION_DIR')) {
            define('PMF_TRANSLATION_DIR', PMF_ROOT_DIR . '/translations');
        }

        // 8. Error handlers
        PhpConfigurator::registerErrorHandlers();

        // 9. Request
        $this->request = Request::createFromGlobals();

        // 10. Output buffering
        ob_start();

        // 11. Database connection (only if a database file exists)
        if ($databaseFile !== null) {
            $this->connectDatabase($databaseFile);

            // 12. Session configuration
            PhpConfigurator::configureSession();

            // 13. LDAP
            $this->configureLdap();

            // 14. Elasticsearch
            if (
                $this->faqConfig->get('search.enableElasticsearch')
                && file_exists(PMF_CONFIG_DIR . '/elasticsearch.php')
            ) {
                SearchClientFactory::configureElasticsearch($this->faqConfig, PMF_CONFIG_DIR);
            }

            // 15. OpenSearch
            if ($this->faqConfig->get('search.enableOpenSearch') && file_exists(PMF_CONFIG_DIR . '/opensearch.php')) {
                SearchClientFactory::configureOpenSearch($this->faqConfig, PMF_CONFIG_DIR);
            }

            // 16. Attachments directory
            ConfigDirectoryResolver::resolveAttachmentsDir(
                (string) $this->faqConfig->get('records.attachmentsPath'),
                dirname(__DIR__, levels: 2),
            );

            // 17. Proxy header fix
            $this->fixProxyHeaders();
        }

        return $this;
    }

    public function getFaqConfig(): ?Configuration
    {
        return $this->faqConfig;
    }

    public function getDb(): ?DatabaseDriver
    {
        return $this->db;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * @throws DatabaseConnectionException
     */
    private function connectDatabase(string $databaseFile): void
    {
        try {
            $dbConfig = new DatabaseConfiguration($databaseFile);
            Database::setTablePrefix($dbConfig->getPrefix());
            $this->db = Database::factory($dbConfig->getType());
            $this->db->connect(
                $dbConfig->getServer(),
                $dbConfig->getUser(),
                $dbConfig->getPassword(),
                $dbConfig->getDatabase(),
                $dbConfig->getPort(),
            );
        } catch (Exception $exception) {
            throw new DatabaseConnectionException(
                message: 'Database connection failed: ' . $exception->getMessage(),
                code: 500,
                previous: $exception,
            );
        }

        $this->faqConfig = new Configuration($this->db);
        try {
            $this->faqConfig->getAll();
        } catch (Exception $exception) {
            throw new DatabaseConnectionException(
                message: 'Database tables not found or inaccessible: ' . $exception->getMessage(),
                code: 500,
                previous: $exception,
            );
        }
    }

    private function configureLdap(): void
    {
        if ($this->faqConfig->isLdapActive() && file_exists(PMF_CONFIG_DIR . '/ldap.php') && extension_loaded('ldap')) {
            $ldapConfig = new LdapConfiguration(PMF_CONFIG_DIR . '/ldap.php');
            $this->faqConfig->setLdapConfig($ldapConfig);
        }
    }

    private function fixProxyHeaders(): void
    {
        if ($this->request->server->has('HTTP_HOST')) {
            return;
        }

        if ($this->request->server->has('HTTP_X_FORWARDED_SERVER')) {
            $this->request->server->set('HTTP_HOST', $this->request->server->get('HTTP_X_FORWARDED_SERVER'));
            return;
        }

        $this->request->server->set('HTTP_HOST', $this->request->server->get('HTTP_X_FORWARDED_HOST'));
    }
}
