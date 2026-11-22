<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

#[AllowMockObjectsWithoutExpectations]
class InstallationRunnerTest extends TestCase
{
    public function testRunnerCanBeConstructed(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);

        $this->assertInstanceOf(InstallationRunner::class, $runner);
    }

    public function testRunMethodExists(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);

        $this->assertTrue(method_exists($runner, 'run'));
    }

    public function testRunAcceptsInstallationInput(): void
    {
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'run');
        $parameters = $reflectionMethod->getParameters();

        $this->assertCount(1, $parameters);
        $this->assertEquals('input', $parameters[0]->getName());
        $this->assertEquals(InstallationInput::class, $parameters[0]->getType()->getName());
    }

    public function testRunnerHasAllExpectedSteps(): void
    {
        $reflectionClass = new \ReflectionClass(InstallationRunner::class);
        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PRIVATE);
        $stepMethods = array_filter($methods, fn($m) => str_starts_with($m->getName(), 'step'));

        $expectedSteps = [
            'stepValidateConnectivity',
            'stepCreateConfigFiles',
            'stepEstablishDbConnection',
            'stepCreateDatabaseTables',
            'stepInsertStopwords',
            'stepSeedConfiguration',
            'stepCreateAdminUser',
            'stepGrantPermissions',
            'stepInsertFormInputs',
            'stepCreateAnonymousUser',
            'stepCreateInstance',
            'stepInitializeSearchEngine',
            'stepAdjustHtaccess',
        ];

        $actualStepNames = array_map(fn($m) => $m->getName(), $stepMethods);
        sort($actualStepNames);
        sort($expectedSteps);

        $this->assertEquals($expectedSteps, $actualStepNames);
    }

    public function testRunCallsStepsInCorrectOrder(): void
    {
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'run');
        $fileName = $reflectionMethod->getFileName();
        $startLine = $reflectionMethod->getStartLine();
        $endLine = $reflectionMethod->getEndLine();

        $lines = array_slice(file($fileName), $startLine - 1, $endLine - $startLine + 1);
        $body = implode('', $lines);

        // Verify steps are called in the correct order
        $stepOrder = [
            'stepValidateConnectivity',
            'stepCreateConfigFiles',
            'stepEstablishDbConnection',
            'stepCreateDatabaseTables',
            'stepInsertStopwords',
            'stepSeedConfiguration',
            'stepCreateAdminUser',
            'stepGrantPermissions',
            'stepInsertFormInputs',
            'stepCreateAnonymousUser',
            'stepCreateInstance',
            'stepInitializeSearchEngine',
            'stepAdjustHtaccess',
        ];

        $lastPos = 0;
        foreach ($stepOrder as $step) {
            $pos = strpos($body, $step);
            $this->assertNotFalse($pos, "Step $step should be called in run()");
            $this->assertGreaterThan($lastPos, $pos, "Step $step should come after previous step");
            $lastPos = $pos;
        }
    }

    public function testBuildOpenSearchClientOptionsUsesDefaults(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'buildOpenSearchClientOptions');

        $options = $reflectionMethod->invoke($runner, 'https://opensearch.example');

        $this->assertSame(
            [
                'base_uri' => 'https://opensearch.example',
                'verify_peer' => true,
            ],
            $options,
        );
    }

    public function testBuildOpenSearchClientOptionsAppliesTlsOverrides(): void
    {
        $system = $this->createStub(System::class);
        $runner = new InstallationRunner($system);
        $reflectionMethod = new \ReflectionMethod(InstallationRunner::class, 'buildOpenSearchClientOptions');

        $options = $reflectionMethod->invoke($runner, 'https://opensearch.example', [
            'verify_peer' => 'false',
            'cafile' => '/tmp/ca.pem',
            'capath' => '/tmp/certs',
        ]);

        $this->assertSame('https://opensearch.example', $options['base_uri']);
        $this->assertFalse($options['verify_peer']);
        $this->assertSame('/tmp/ca.pem', $options['cafile']);
        $this->assertSame('/tmp/certs', $options['capath']);
    }

    public function testConfigFilesAreWrittenToTheConfigDirectoryTheApplicationReads(): void
    {
        $rootDir = sys_get_temp_dir() . '/pmf-installation-runner-' . bin2hex(random_bytes(6));
        $configDir = $rootDir . '/content/core/config';
        mkdir($configDir, 0o777, true);

        $input = new InstallationInput(
            dbSetup: ['dbType' => 'sqlite3', 'dbServer' => $rootDir . '/faq.db'],
            ldapSetup: ['ldapServer' => 'ldap.example.com', 'ldapPort' => 389, 'ldapPassword' => "it's secret"],
            esSetup: ['hosts' => ['es1:9200', 'es2:9200'], 'index' => 'faq'],
            osSetup: ['hosts' => ['os1:9200', 'os2:9200'], 'index' => 'faq'],
            loginName: 'admin',
            password: 'password1234',
            language: 'en',
            realname: 'Admin',
            email: 'admin@example.com',
            permLevel: 'basic',
            rootDir: $rootDir,
            ldapEnabled: true,
            esEnabled: true,
            osEnabled: true,
        );

        try {
            $runner = new InstallationRunner($this->createStub(System::class));
            new \ReflectionMethod(InstallationRunner::class, 'stepCreateConfigFiles')->invoke($runner, $input);

            $PMF_LDAP = [];
            $PMF_ES = [];
            $PMF_OS = [];
            $this->assertFileExists($configDir . '/database.php');
            include $configDir . '/ldap.php';
            include $configDir . '/elasticsearch.php';
            include $configDir . '/opensearch.php';

            $this->assertSame("it's secret", $PMF_LDAP['ldap_password']);
            $this->assertSame(['es1:9200', 'es2:9200'], $PMF_ES['hosts']);
            $this->assertSame(['os1:9200', 'os2:9200'], $PMF_OS['hosts']);
        } finally {
            new Filesystem()->remove($rootDir);
        }
    }
}
