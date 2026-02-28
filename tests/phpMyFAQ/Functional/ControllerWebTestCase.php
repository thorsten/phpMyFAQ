<?php

declare(strict_types=1);

namespace phpMyFAQ\Functional;

use phpMyFAQ\Configuration;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

abstract class ControllerWebTestCase extends WebTestCase
{
    private static ?string $activeContext = null;

    /** @var array<string, array{configuration: Configuration, values: array<string, mixed>}> */
    private array $originalConfigurations = [];

    protected function requestPublic(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('public', $method, $uri, $parameters, $server);
    }

    protected function requestAdmin(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('admin', $method, $uri, $parameters, $server);
    }

    protected function requestApi(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('api', $method, $uri, $parameters, $server);
    }

    protected function requestAdminApi(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
    ): Response {
        return $this->requestWithContext('admin-api', $method, $uri, $parameters, $server);
    }

    protected function requestAny(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('all', $method, $uri, $parameters, $server);
    }

    protected function requestApiJson(string $method, string $uri, array $payload, array $server = []): Response
    {
        return $this->requestWithContext('api', $method, $uri, [], array_merge([
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ], $server), json_encode($payload, JSON_THROW_ON_ERROR));
    }

    protected function overrideConfigurationValues(array $values, string $context = 'public'): void
    {
        $configuration = $this->getConfiguration($context);
        self::assertInstanceOf(Configuration::class, $configuration);

        $reflection = new ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');

        $currentConfig = $configProperty->getValue($configuration);
        self::assertIsArray($currentConfig);

        if (!array_key_exists($context, $this->originalConfigurations)) {
            $this->originalConfigurations[$context] = [
                'configuration' => $configuration,
                'values' => $currentConfig,
            ];
        }

        $configProperty->setValue($configuration, array_merge($currentConfig, $values));
    }

    protected function getConfiguration(string $context = 'public'): Configuration
    {
        $this->ensureClientForContext($context);
        $container = self::$kernel?->getContainer();
        self::assertNotNull($container, 'Kernel container is not available.');

        $configuration = $container->get('phpmyfaq.configuration');
        self::assertInstanceOf(Configuration::class, $configuration);

        return $configuration;
    }

    protected function assertResponseContains(string $needle, ?Response $response = null): void
    {
        $response ??= self::$client?->getResponse();
        self::assertInstanceOf(Response::class, $response, 'No response available. Did you make a request?');
        self::assertStringContainsString($needle, (string) $response->getContent());
    }

    protected function assertRedirectLocationContains(string $needle, ?Response $response = null): void
    {
        $response ??= self::$client?->getResponse();
        self::assertInstanceOf(Response::class, $response, 'No response available. Did you make a request?');
        self::assertTrue($response->isRedirect(), 'Expected a redirect response.');
        self::assertStringContainsString($needle, (string) $response->headers->get('Location'));
    }

    protected function requestWithContext(
        string $context,
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        ?string $content = null,
    ): Response {
        $client = $this->ensureClientForContext($context);
        $client->followRedirects(false);
        $serverParameters = array_merge([
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'HTTPS' => 'on',
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => PMF_ROOT_DIR . '/index.php',
            'REQUEST_URI' => $uri,
        ], $server);

        $originalServer = $_SERVER;
        foreach ($serverParameters as $key => $value) {
            $_SERVER[$key] = $value;
        }

        try {
            $client->request($method, $uri, $parameters, [], $serverParameters, $content);
        } finally {
            $_SERVER = $originalServer;
        }

        $response = $client->getResponse();
        self::assertInstanceOf(Response::class, $response, 'No Symfony response available after request.');

        return $response;
    }

    private function ensureClientForContext(string $context): HttpKernelBrowser
    {
        if (self::$client === null || self::$activeContext !== $context) {
            self::$client = self::createClient($context);
            self::$activeContext = $context;
            $container = self::$kernel?->getContainer();
            self::assertNotNull($container, 'Kernel container is not available.');

            $configuration = $container->get('phpmyfaq.configuration');
            self::assertInstanceOf(Configuration::class, $configuration);

            $reflection = new ReflectionClass(Configuration::class);
            $configProperty = $reflection->getProperty('config');
            $currentConfig = $configProperty->getValue($configuration);
            self::assertIsArray($currentConfig);
            $configProperty->setValue($configuration, array_merge($currentConfig, [
                'main.referenceURL' => 'https://localhost/',
            ]));
        }

        return self::$client;
    }

    protected function tearDown(): void
    {
        foreach ($this->originalConfigurations as $configurationSnapshot) {
            $configuration = $configurationSnapshot['configuration'];
            $reflection = new ReflectionClass(Configuration::class);
            $configProperty = $reflection->getProperty('config');
            $configProperty->setValue($configuration, $configurationSnapshot['values']);
        }

        $this->originalConfigurations = [];
        self::$activeContext = null;
        parent::tearDown();
    }
}
