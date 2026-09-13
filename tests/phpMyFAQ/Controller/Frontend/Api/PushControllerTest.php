<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Push\PushEndpointValidator;
use phpMyFAQ\Push\PushSubscriptionRepository;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(PushController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PushControllerTest extends ApiControllerTestCase
{
    /**
     * @return array{0: Session, 1: string}
     */
    private function createValidCsrfSession(): array
    {
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString(PushController::CSRF_PAGE);

        return [$session, $csrfToken];
    }

    /**
     * A validator whose DNS lookup always yields a public address, so controller tests
     * never hit the network.
     */
    private function createPublicEndpointValidator(): PushEndpointValidator
    {
        return new PushEndpointValidator(static fn(string $host): array => [
            '93.184.216.34' === $host ? $host : '8.8.8.8',
        ]);
    }

    private function createController(
        ?PushSubscriptionRepository $repository = null,
        ?PushEndpointValidator $validator = null,
    ): PushController {
        return new PushController(
            $this->createStub(WebPushService::class),
            $repository ?? $this->createStub(PushSubscriptionRepository::class),
            $validator ?? $this->createPublicEndpointValidator(),
        );
    }

    public function testGetVapidPublicKeyReturnsServiceState(): void
    {
        $webPushService = $this->createStub(WebPushService::class);
        $webPushService->method('isEnabled')->willReturn(true);
        $webPushService->method('getVapidPublicKey')->willReturn('public-key');

        $controller = new PushController($webPushService, $this->createStub(PushSubscriptionRepository::class));

        $response = $controller->getVapidPublicKey();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"enabled":true,"vapidPublicKey":"public-key"}',
            (string) $response->getContent(),
        );
    }

    public function testSubscribeReturnsBadRequestForInvalidJson(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], 'invalid json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid JSON payload"}', (string) $response->getContent());
    }

    public function testSubscribeReturnsUnauthorizedWithoutCsrfToken(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->never())->method('save');

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('ad_msg_noauth'), $payload['error']);
    }

    public function testSubscribeReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        [$session] = $this->createValidCsrfSession();
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->never())->method('save');

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'csrfToken' => 'wrong-token',
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testSubscribeReturnsBadRequestWhenRequiredDataIsMissing(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => '',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Missing required subscription data"}',
            (string) $response->getContent(),
        );
    }

    public function testSubscribeReturnsCreatedWhenSubscriptionIsSaved(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(static function ($entity): bool {
                return (
                    $entity->getUserId() === 1
                    && $entity->getEndpoint() === 'https://push.example.test/subscription'
                    && $entity->getPublicKey() === 'public-key'
                    && $entity->getAuthToken() === 'auth-token'
                    && $entity->getContentEncoding() === 'aes128gcm'
                );
            }))
            ->willReturn(true);

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'contentEncoding' => 'aes128gcm',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true}', (string) $response->getContent());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function nonPublicEndpointProvider(): iterable
    {
        yield 'plain http' => ['http://push.example.test/subscription'];
        yield 'loopback address' => ['https://127.0.0.1/subscription'];
        yield 'private address' => ['https://10.0.0.5:8443/subscription'];
        yield 'link-local metadata address' => ['https://169.254.169.254/latest/meta-data'];
        yield 'localhost name' => ['https://localhost/subscription'];
        yield 'host resolving to private address' => ['https://intranet.example.test/subscription'];
        yield 'not a url' => ['https://'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('nonPublicEndpointProvider')]
    public function testSubscribeRejectsNonPublicEndpoints(string $endpoint): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->never())->method('save');

        $validator = new PushEndpointValidator(static fn(string $host): array => match ($host) {
            'intranet.example.test' => ['192.168.1.20'],
            default => ['8.8.8.8'],
        });

        $controller = $this->createController($repository, $validator);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => $endpoint,
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgPushInvalidEndpoint'), $payload['error']);
    }

    public function testSubscribeReturnsBadRequestWhenSubscriptionSaveFails(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())->method('save')->willReturn(false);

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'contentEncoding' => 'aes128gcm',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Failed to save subscription"}',
            (string) $response->getContent(),
        );
    }

    public function testUnsubscribeReturnsBadRequestForInvalidJson(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], 'invalid json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid JSON payload"}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsUnauthorizedWithoutCsrfToken(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->never())->method('deleteByEndpointHashAndUserId');

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('ad_msg_noauth'), $payload['error']);
    }

    public function testUnsubscribeReturnsBadRequestWhenEndpointIsMissing(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => '',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Missing endpoint"}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsSuccessWhenSubscriptionIsRemoved(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $endpoint = 'https://push.example.test/subscription';
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository
            ->expects($this->once())
            ->method('deleteByEndpointHashAndUserId')
            ->with(hash('sha256', $endpoint), 1)
            ->willReturn(true);

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => $endpoint,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsBadRequestWhenSubscriptionRemovalFails(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $endpoint = 'https://push.example.test/subscription';
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository
            ->expects($this->once())
            ->method('deleteByEndpointHashAndUserId')
            ->with(hash('sha256', $endpoint), 1)
            ->willReturn(false);

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => $endpoint,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Failed to remove subscription"}',
            (string) $response->getContent(),
        );
    }

    public function testStatusReturnsSubscriptionState(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())->method('hasSubscription')->with(1)->willReturn(true);

        $controller = $this->createController($repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->status();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"subscribed":true}', (string) $response->getContent());
    }
}
