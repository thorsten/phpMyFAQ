<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Frontend\Api\SetupController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Setup\UpdateToken;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SetupControllerTest
 *
 * The setup endpoints run without a login, so they have to reject every caller that
 * neither has an administrator session nor knows the update token from the file system.
 */
#[AllowMockObjectsWithoutExpectations]
#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
class SetupControllerTest extends TestCase
{
    private Configuration $configuration;

    private UpdateToken $updateToken;

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

        $this->updateToken = new UpdateToken(PMF_CONFIG_DIR);
        $this->updateToken->delete();
    }

    protected function tearDown(): void
    {
        $this->updateToken->delete();

        parent::tearDown();
    }

    public function testCheckIsDeniedWithoutAuthorization(): void
    {
        $response = (new SetupController())->check($this->createRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $this->assertStringContainsString('not allowed to run the update', $response->getContent());
    }

    public function testBackupIsDeniedWithoutAuthorization(): void
    {
        $response = (new SetupController())->backup($this->createRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUpdateDatabaseIsDeniedWithoutAuthorization(): void
    {
        $response = (new SetupController())->updateDatabase($this->createRequest());

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUpdateDatabaseIsDeniedWithAnInvalidUpdateToken(): void
    {
        $this->updateToken->getOrCreate();

        $response = (new SetupController())->updateDatabase($this->createRequest('an-invalid-token'));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * With a valid token the request passes the authorization and is stopped by the
     * next gate: the update only runs while the FAQ is in maintenance mode.
     *
     * @throws Exception
     */
    public function testAValidUpdateTokenPassesTheAuthorization(): void
    {
        $token = $this->updateToken->getOrCreate();

        $response = (new SetupController())->updateDatabase($this->createRequest($token));

        $this->assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertStringContainsString('Maintenance mode is not enabled', $response->getContent());
    }

    private function createRequest(?string $token = null): Request
    {
        $request = new Request(content: '4.1.6');

        if (is_string($token)) {
            $request->headers->set(SetupController::TOKEN_HEADER, $token);
        }

        return $request;
    }
}
