<?php

/**
 * Orchestrates the phpMyFAQ installation process as discrete, testable steps.
 *
 * Mirrors the UpdateRunner pattern: accepts validated InstallationInput and
 * runs discrete steps in order. Supports dry-run for database operations.
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
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use Composer\Autoload\ClassLoader;
use Elastic\Elasticsearch\ClientBuilder;
use OpenSearch\SymfonyClientFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Forms;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Database as InstanceDatabase;
use phpMyFAQ\Instance\Database\Stopwords;
use phpMyFAQ\Instance\Main;
use phpMyFAQ\Instance\Search\Elasticsearch;
use phpMyFAQ\Instance\Search\OpenSearch;
use phpMyFAQ\Instance\Setup;
use phpMyFAQ\Ldap;
use phpMyFAQ\Link;
use phpMyFAQ\Setup\Installation\DefaultDataSeeder;
use phpMyFAQ\System;
use phpMyFAQ\User;
use Symfony\Component\HttpFoundation\Request;

class InstallationRunner
{
    private ?Configuration $configuration = null;

    private ?DatabaseDriver $db = null;

    public function __construct(
        private readonly System $system,
    ) {
    }

    /**
     * Runs the full installation using validated input.
     *
     * @throws Exception|\Exception
     */
    public function run(InstallationInput $input): void
    {
        $this->stepValidateConnectivity($input);
        $this->stepCreateConfigFiles($input);
        $this->stepEstablishDbConnection($input);
        $this->stepCreateDatabaseTables($input);
        $this->stepInsertStopwords($input);
        $this->stepSeedConfiguration($input);
        $this->stepCreateAdminUser($input);
        $this->stepGrantPermissions($input);
        $this->stepInsertFormInputs();
        $this->stepCreateAnonymousUser($input);
        $this->stepCreateInstance();
        $this->stepInitializeSearchEngine($input);
        $this->stepAdjustHtaccess($input);
    }

    /**
     * Step 1: Validate database, LDAP, ES, and OpenSearch connectivity.
     *
     * @throws Exception
     */
    private function stepValidateConnectivity(InstallationInput $input): void
    {
        Database::setTablePrefix($input->dbSetup['dbPrefix'] ?? '');
        $db = Database::factory($input->dbSetup['dbType']);
        $db->connect(
            $input->dbSetup['dbServer'],
            $input->dbSetup['dbUser'],
            $input->dbSetup['dbPassword'],
            $input->dbSetup['dbDatabaseName'],
            $input->dbSetup['dbPort'],
        );

        $configuration = new Configuration($db);

        // Validate LDAP connection if enabled
        if ($input->ldapEnabled && $input->ldapSetup !== []) {
            $seeder = new DefaultDataSeeder();
            foreach ($seeder->getMainConfig() as $configKey => $configValue) {
                if (!str_contains($configKey, 'ldap.')) {
                    continue;
                }

                $configuration->set($configKey, $configValue);
            }

            $ldap = new Ldap($configuration);
            $ldapConnection = $ldap->connect(
                $input->ldapSetup['ldapServer'],
                $input->ldapSetup['ldapPort'],
                $input->ldapSetup['ldapBase'],
                $input->ldapSetup['ldapUser'],
                $input->ldapSetup['ldapPassword'],
            );

            if (!$ldapConnection) {
                throw new Exception(sprintf('LDAP Installation Error: %s.', $ldap->error()));
            }
        }

        // Validate Elasticsearch connection if enabled
        if ($input->esEnabled && $input->esSetup !== []) {
            $classLoader = new ClassLoader();
            $classLoader->addPsr4('Elasticsearch\\', PMF_SRC_DIR . '/libs/elasticsearch/src/Elasticsearch');
            $classLoader->addPsr4('Monolog\\', PMF_SRC_DIR . '/libs/monolog/src/Monolog');
            $classLoader->addPsr4('Psr\\', PMF_SRC_DIR . '/libs/psr/log/Psr');
            $classLoader->addPsr4('React\\Promise\\', PMF_SRC_DIR . '/libs/react/promise/src');
            $classLoader->register();

            try {
                $esHosts = array_values($input->esSetup['hosts']);
                $esClient = ClientBuilder::create()->setHosts($esHosts)->build();
                $esClient->ping();
            } catch (\Throwable $e) {
                throw new Exception(sprintf(
                    'Elasticsearch Installation Error: Could not connect to Elasticsearch: %s',
                    $e->getMessage(),
                ));
            }
        }

        // Validate OpenSearch connection if enabled
        if ($input->osEnabled && $input->osSetup !== []) {
            try {
                $osHosts = array_values($input->osSetup['hosts']);
                $osClient = new SymfonyClientFactory()->create([
                    'base_uri' => $osHosts[0],
                    'verify_peer' => false,
                ]);

                if (!$osClient->ping()) {
                    throw new Exception('OpenSearch Installation Error: Server did not respond to ping.');
                }
            } catch (Exception $e) {
                throw $e;
            } catch (\Throwable $e) {
                throw new Exception(sprintf(
                    'OpenSearch Installation Error: Could not connect to OpenSearch: %s',
                    $e->getMessage(),
                ));
            }
        }
    }

    /**
     * Step 2: Write config files (database.php, ldap.php, elasticsearch.php, opensearch.php).
     *
     * @throws Exception
     */
    private function stepCreateConfigFiles(InstallationInput $input): void
    {
        $instanceSetup = new Setup();
        $instanceSetup->setRootDir($input->rootDir);

        if (!$instanceSetup->createDatabaseFile($input->dbSetup)) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception('Installation Error: Setup cannot write to ./content/core/config/database.php.');
        }

        if ($input->ldapEnabled && $input->ldapSetup !== [] && !$instanceSetup->createLdapFile($input->ldapSetup, '')) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception('LDAP Installation Error: Setup cannot write to ./content/core/config/ldap.php.');
        }

        if (
            $input->esEnabled
            && $input->esSetup !== []
            && !$instanceSetup->createElasticsearchFile($input->esSetup, '')
        ) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(
                'Elasticsearch Installation Error: Setup cannot write to ./content/core/config/elasticsearch.php.',
            );
        }

        if ($input->osEnabled && $input->osSetup !== [] && !$instanceSetup->createOpenSearchFile($input->osSetup, '')) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(
                'OpenSearch Installation Error: Setup cannot write to ./content/core/config/opensearch.php.',
            );
        }
    }

    /**
     * Step 3: Connect to the database using the freshly-written config file.
     *
     * @throws Exception
     */
    private function stepEstablishDbConnection(InstallationInput $input): void
    {
        $databaseConfiguration = new DatabaseConfiguration($input->rootDir . '/content/core/config/database.php');
        try {
            $this->db = Database::factory($input->dbSetup['dbType']);
        } catch (Exception $exception) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $exception->getMessage()));
        }

        $this->db->connect(
            $databaseConfiguration->getServer(),
            $databaseConfiguration->getUser(),
            $databaseConfiguration->getPassword(),
            $databaseConfiguration->getDatabase(),
            $databaseConfiguration->getPort(),
        );

        if (!$this->db instanceof DatabaseDriver) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $this->db->error()));
        }

        $this->configuration = new Configuration($this->db);
    }

    /**
     * Step 4: Create all database tables via SchemaInstaller.
     *
     * @throws Exception
     */
    private function stepCreateDatabaseTables(InstallationInput $input): void
    {
        try {
            $databaseInstaller = InstanceDatabase::factory($this->configuration, $input->dbSetup['dbType']);
            $databaseInstaller->createTables($input->dbSetup['dbPrefix'] ?? '');
        } catch (Exception $exception) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Database Installation Error: %s', $exception->getMessage()));
        }
    }

    /**
     * Step 5: Insert stopwords into the database.
     */
    private function stepInsertStopwords(InstallationInput $input): void
    {
        $stopWords = new Stopwords($this->configuration);
        $stopWords->executeInsertQueries($input->dbSetup['dbPrefix'] ?? '');

        $this->system->setDatabase($this->db);
    }

    /**
     * Step 6: Seed default configuration.
     */
    private function stepSeedConfiguration(InstallationInput $input): void
    {
        $seeder = new DefaultDataSeeder();
        $seeder->applyPersonalSettings($input->realname, $input->getEmail(), $input->language, $input->permLevel);
        $seeder->seedConfig($this->configuration);

        $link = new Link('', $this->configuration);
        $this->configuration->update(['main.referenceURL' => $link->getSystemUri('/setup/index.php')]);
        $this->configuration->add('security.salt', md5($this->configuration->getDefaultUrl()));
    }

    /**
     * Step 7: Create admin user (user_id = 1).
     *
     * @throws Exception
     */
    private function stepCreateAdminUser(InstallationInput $input): void
    {
        $user = new User($this->configuration);
        if (!$user->createUser($input->getLoginName(), $input->getPassword(), '', 1)) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf(
                'Fatal Installation Error: Could not create the admin user: %s',
                $user->error(),
            ));
        }

        if (!$user->setStatus('protected')) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf(
                'Fatal Installation Error: Could not set admin user status: %s',
                $user->error(),
            ));
        }

        $adminData = [
            'display_name' => $input->realname,
            'email' => $input->getEmail(),
        ];
        if (!$user->setUserData($adminData)) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf('Fatal Installation Error: Could not set admin user data: %s', $user->error()));
        }

        if (!$user->setSuperAdmin(true)) {
            Installer::cleanFailedInstallationFiles();
            throw new Exception(sprintf(
                'Fatal Installation Error: Could not set admin as super admin: %s',
                $user->error(),
            ));
        }
    }

    /**
     * Step 8: Grant all permissions to admin user.
     */
    private function stepGrantPermissions(InstallationInput $input): void
    {
        $user = new User($this->configuration);
        $user->getUserById(1, true);

        $seeder = new DefaultDataSeeder();
        foreach ($seeder->getMainRights() as $mainRight) {
            $user->perm->grantUserRight(1, $user->perm->addRight($mainRight));
        }
    }

    /**
     * Step 9: Insert form inputs.
     */
    private function stepInsertFormInputs(): void
    {
        $forms = new Forms($this->configuration);
        $seeder = new DefaultDataSeeder();
        foreach ($seeder->getFormInputs() as $formInput) {
            $forms->insertInputIntoDatabase($formInput);
        }
    }

    /**
     * Step 10: Create anonymous user (user_id = -1).
     *
     * @throws Exception
     */
    private function stepCreateAnonymousUser(InstallationInput $input): void
    {
        $instanceSetup = new Setup();
        $instanceSetup->setRootDir($input->rootDir);
        $instanceSetup->createAnonymousUser($this->configuration);
    }

    /**
     * Step 11: Create primary instance.
     */
    private function stepCreateInstance(): void
    {
        $link = new Link('', $this->configuration);
        $instanceEntity = new InstanceEntity();
        $instanceEntity
            ->setUrl($link->getSystemUri(Request::createFromGlobals()->getScriptName()))
            ->setInstance($link->getSystemRelativeUri('setup/index.php'))
            ->setComment('phpMyFAQ ' . System::getVersion());

        $faqInstance = new Instance($this->configuration);
        $faqInstance->create($instanceEntity);

        $main = new Main($this->configuration);
        $main->createMain($faqInstance);
    }

    /**
     * Step 12: Initialize Elasticsearch/OpenSearch indices.
     */
    private function stepInitializeSearchEngine(InstallationInput $input): void
    {
        if ($input->esEnabled && is_file($input->rootDir . '/content/core/config/elasticsearch.php')) {
            $elasticsearchConfiguration = new ElasticsearchConfiguration($input->rootDir
            . '/content/core/config/elasticsearch.php');
            $this->configuration->setElasticsearchConfig($elasticsearchConfiguration);

            $esClient = ClientBuilder::create()->setHosts($elasticsearchConfiguration->getHosts())->build();
            $this->configuration->setElasticsearch($esClient);

            $elasticsearch = new Elasticsearch($this->configuration);
            $elasticsearch->createIndex();
        }

        if ($input->osEnabled && is_file($input->rootDir . '/content/core/config/opensearch.php')) {
            $openSearchConfiguration = new OpenSearchConfiguration($input->rootDir
            . '/content/core/config/opensearch.php');
            $this->configuration->setOpenSearchConfig($openSearchConfiguration);

            $osClient = new SymfonyClientFactory()->create([
                'base_uri' => $openSearchConfiguration->getHosts()[0],
                'verify_peer' => false,
            ]);
            $this->configuration->setOpenSearch($osClient);

            $openSearch = new OpenSearch($this->configuration);
            $openSearch->createIndex();
        }
    }

    /**
     * Step 13: Adjust .htaccess RewriteBase.
     *
     * Skips when the installation rootDir differs from the application's root path
     * (e.g. in test environments) to avoid modifying the real .htaccess file.
     */
    private function stepAdjustHtaccess(InstallationInput $input): void
    {
        if (realpath($input->rootDir) !== realpath($this->configuration->getRootPath())) {
            return;
        }

        $environmentConfigurator = new EnvironmentConfigurator($this->configuration);
        $environmentConfigurator->adjustRewriteBaseHtaccess();
    }
}
