<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Queue\DatabaseMessageBus;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(RegistrationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class RegistrationControllerValidationTest extends ApiControllerTestCase
{
    public function testCreateThrowsExceptionWhenRealnameIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing realname');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenPayloadIsNotAnObject(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid request payload');

        $controller->create(Request::create('/api/register', 'POST', content: '[]'));
    }

    public function testCreateThrowsExceptionWhenUsernameIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing username');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'email' => 'testuser@example.com',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsMissing(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty email');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsEmpty(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing or empty email');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => '   ',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenEmailIsInvalid(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid email address');

        $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'not-an-email',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsBadRequestWhenCaptchaValidationFails(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser@example.com',
            'email' => 'testuser@example.com',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    /**
     * @return array<string, array{array<string, mixed>, int}>
     */
    public static function visibilityCheckboxProvider(): array
    {
        return [
            'ticked checkbox is serialized as "on"' => [['isVisible' => 'on'], 1],
            'JSON true' => [['isVisible' => true], 1],
            'unticked checkbox is omitted' => [[], 0],
            'JSON false' => [['isVisible' => false], 0],
        ];
    }

    /**
     * @param array<string, mixed> $visibilityPayload
     */
    #[DataProvider('visibilityCheckboxProvider')]
    public function testCreateRegistersUserWithTheVisibilityOfTheCheckbox(
        array $visibilityPayload,
        int $expectedVisibility,
    ): void {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'mail.useQueue' => true,
            'main.administrationMail' => 'admin@example.com',
        ]);
        $mailQueue = $this->queueMailsInTestDatabase();

        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->create(Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Visible User',
            'name' => 'visibleuser',
            'email' => 'visibleuser@example.com',
            'captcha' => 'ignored-for-logged-in-user',
            ...$visibilityPayload,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertTrue($payload['registered']);

        $user = new User($this->configuration);
        self::assertTrue($user->getUserByLogin('visibleuser'));
        self::assertSame($expectedVisibility, (int) $user->getUserData('is_visible'));
        self::assertNotNull($mailQueue->reserve('mail'), 'The notification must be queued, not sent.');
    }

    /**
     * Routes the registration notification into the queue table of the private database copy,
     * so that the test never hands a message to a real mail transport.
     */
    private function queueMailsInTestDatabase(): DatabaseTransport
    {
        $mailQueue = new DatabaseTransport($this->configuration);
        $messageBus = new DatabaseMessageBus($mailQueue);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturn(true);
        $container->method('get')->willReturn($messageBus);

        $this->configuration->setContainer($container);

        return $mailQueue;
    }

    public function testCreateReturnsBadRequestWhenNormalizedUserDataIsEmpty(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => '   ',
            'name' => '   ',
            'email' => 'testuser@example.com',
            'captcha' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }

    public function testCreateReturnsBadRequestWhenDomainIsNotAllowed(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues([
            'security.domainWhiteListForRegistrations' => 'allowed.example',
        ]);

        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => 'Test User',
            'name' => 'testuser',
            'email' => 'testuser@blocked.example',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('The domain is not allowed.', $payload['error']);
    }

    public function testCreateReturnsBadRequestWhenNormalizedUserDataIsEmptyAfterCaptchaSucceeds(): void
    {
        $controller = new RegistrationController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $request = Request::create('/api/register', 'POST', content: json_encode([
            'realname' => '   ',
            'name' => '   ',
            'email' => 'testuser@example.com',
            'captcha' => 'ignored-for-logged-in-user',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
    }
}
