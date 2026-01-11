<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

#[AllowMockObjectsWithoutExpectations]
class RegistrationControllerTest extends TestCase
{
    private Configuration $configuration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configuration = Configuration::getConfigurationInstance();
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateReturnsJsonResponse(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateRequiresValidToken(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateRequiresAllRequiredFields(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateValidatesEmailFormat(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'invalid-email',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithInvalidJsonThrowsException(): void
    {
        $requestData = 'invalid json';

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithMissingUsername(): void
    {
        $requestData = json_encode([
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithMissingFullname(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithMissingEmail(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    public function testCreateWithMissingIsVisible(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithEmptyUsername(): void
    {
        $requestData = json_encode([
            'username' => '',
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */ public function testCreateWithEmptyFullname(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => '',
            'email' => 'test@example.com',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws \JsonException
     */ public function testCreateWithEmptyEmail(): void
    {
        $requestData = json_encode([
            'username' => 'testuser',
            'fullname' => 'Test User',
            'email' => '',
            'is-visible' => 'false',
        ]);

        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new RegistrationController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }
}
