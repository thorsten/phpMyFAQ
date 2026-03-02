<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Push\PushSubscriptionRepository;
use phpMyFAQ\Push\WebPushService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(PushController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PushControllerTest extends ApiControllerTestCase
{
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
        $controller = new PushController(
            $this->createStub(WebPushService::class),
            $this->createStub(PushSubscriptionRepository::class),
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], 'invalid json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid JSON payload"}', (string) $response->getContent());
    }

    public function testSubscribeReturnsBadRequestWhenRequiredDataIsMissing(): void
    {
        $controller = new PushController(
            $this->createStub(WebPushService::class),
            $this->createStub(PushSubscriptionRepository::class),
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], '{"endpoint":""}'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Missing required subscription data"}',
            (string) $response->getContent(),
        );
    }

    public function testSubscribeReturnsCreatedWhenSubscriptionIsSaved(): void
    {
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

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'contentEncoding' => 'aes128gcm',
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true}', (string) $response->getContent());
    }

    public function testSubscribeReturnsBadRequestWhenSubscriptionSaveFails(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())->method('save')->willReturn(false);

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->subscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => 'https://push.example.test/subscription',
            'publicKey' => 'public-key',
            'authToken' => 'auth-token',
            'contentEncoding' => 'aes128gcm',
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Failed to save subscription"}',
            (string) $response->getContent(),
        );
    }

    public function testUnsubscribeReturnsBadRequestForInvalidJson(): void
    {
        $controller = new PushController(
            $this->createStub(WebPushService::class),
            $this->createStub(PushSubscriptionRepository::class),
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], 'invalid json'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Invalid JSON payload"}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsBadRequestWhenEndpointIsMissing(): void
    {
        $controller = new PushController(
            $this->createStub(WebPushService::class),
            $this->createStub(PushSubscriptionRepository::class),
        );
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], '{"endpoint":""}'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Missing endpoint"}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsSuccessWhenSubscriptionIsRemoved(): void
    {
        $endpoint = 'https://push.example.test/subscription';
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository
            ->expects($this->once())
            ->method('deleteByEndpointHashAndUserId')
            ->with(hash('sha256', $endpoint), 1)
            ->willReturn(true);

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => $endpoint,
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"success":true}', (string) $response->getContent());
    }

    public function testUnsubscribeReturnsBadRequestWhenSubscriptionRemovalFails(): void
    {
        $endpoint = 'https://push.example.test/subscription';
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository
            ->expects($this->once())
            ->method('deleteByEndpointHashAndUserId')
            ->with(hash('sha256', $endpoint), 1)
            ->willReturn(false);

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->unsubscribe(new Request([], [], [], [], [], [], json_encode([
            'endpoint' => $endpoint,
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

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->status();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"subscribed":true}', (string) $response->getContent());
    }
}
