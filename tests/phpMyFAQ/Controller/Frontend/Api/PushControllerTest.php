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
        self::assertJsonStringEqualsJsonString(
            '{"error":"Invalid JSON payload"}',
            (string) $response->getContent(),
        );
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

    public function testStatusReturnsSubscriptionState(): void
    {
        $repository = $this->createMock(PushSubscriptionRepository::class);
        $repository->expects($this->once())
            ->method('hasSubscription')
            ->with(1)
            ->willReturn(true);

        $controller = new PushController($this->createStub(WebPushService::class), $repository);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->status();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"subscribed":true}',
            (string) $response->getContent(),
        );
    }
}
