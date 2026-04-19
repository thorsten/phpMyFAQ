<?php

declare(strict_types=1);

namespace phpMyFAQ\Functional;

use phpMyFAQ\Configuration;
use phpMyFAQ\Session\SessionWrapper;
use phpMyFAQ\User\CurrentUser;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;

abstract class ControllerWebTestCase extends WebTestCase
{
    private static ?string $activeContext = null;

    /** @var array<string, array{configuration: Configuration, values: array<string, mixed>}> */
    private array $originalConfigurations = [];

    /** @var array<string, list<string>> */
    private array $addedConfigurationKeys = [];

    protected function requestPublic(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('public', $method, $uri, $parameters, $server);
    }

    protected function requestAdmin(string $method, string $uri, array $parameters = [], array $server = []): Response
    {
        return $this->requestWithContext('admin', $method, $uri, $parameters, $server, null, true);
    }

    protected function requestAdminGuest(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
    ): Response {
        return $this->requestWithContext('admin', $method, $uri, $parameters, $server, null, false);
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
        return $this->requestWithContext(
            'api',
            $method,
            $uri,
            [],
            array_merge([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ], $server),
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
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

        $this->addedConfigurationKeys[$context] ??= [];
        $normalizedValues = [];
        foreach ($values as $name => $value) {
            $key = (string) $name;
            $storedValue = $this->normalizeConfigurationValue($value);
            $normalizedValues[$key] = $storedValue;
            $existingValue = $configuration->get($key);
            if ($existingValue === null) {
                $configuration->add($key, $storedValue);
                $this->addedConfigurationKeys[$context][] = $key;
            } else {
                $configuration->update([$key => $storedValue]);
            }
        }

        $latestConfig = $configProperty->getValue($configuration);
        self::assertIsArray($latestConfig);
        $configProperty->setValue($configuration, array_merge($latestConfig, $normalizedValues));
    }

    private function normalizeConfigurationValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return (string) $value;
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
        bool $authenticateAdmin = false,
    ): Response {
        $client = $this->ensureClientForContext($context);
        $client->followRedirects(false);
        $this->prepareAuthenticationSession($context, $uri, $authenticateAdmin);
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

    private function prepareAuthenticationSession(string $context, string $uri, bool $authenticateAdmin): void
    {
        $this->ensureNativeSession();
        $this->clearAuthenticationSession();

        if (!$authenticateAdmin || $context !== 'admin' || in_array($uri, ['/login', '/authenticate'], true)) {
            return;
        }

        $configuration = $this->getConfiguration($context);
        $currentUser = new CurrentUser($configuration);
        $currentUser->getUserById(1);
        $currentUser->setLoggedIn(true);
        $currentUser->updateSessionId();
        $currentUser->saveToSession();
    }

    private function ensureNativeSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function clearAuthenticationSession(): void
    {
        $sessionWrapper = new SessionWrapper();
        $sessionWrapper->remove(CurrentUser::SESSION_CURRENT_USER);
        $sessionWrapper->remove(CurrentUser::SESSION_ID_TIMESTAMP);
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

            $configuration->update(['main.referenceURL' => 'https://localhost/']);

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
        foreach ($this->originalConfigurations as $context => $configurationSnapshot) {
            $configuration = $configurationSnapshot['configuration'];
            $originalValues = $configurationSnapshot['values'];
            $reflection = new ReflectionClass(Configuration::class);
            $configProperty = $reflection->getProperty('config');

            $addedKeys = $this->addedConfigurationKeys[$context] ?? [];
            foreach ($addedKeys as $addedKey) {
                $configuration->delete($addedKey);
            }

            $restore = [];
            foreach ($originalValues as $name => $value) {
                if (in_array($name, $addedKeys, true)) {
                    continue;
                }
                $restore[$name] = $value;
            }
            if ($restore !== []) {
                $configuration->update($restore);
            }

            $configProperty->setValue($configuration, $originalValues);
        }

        $this->originalConfigurations = [];
        $this->addedConfigurationKeys = [];
        self::$activeContext = null;
        parent::tearDown();
    }
}
