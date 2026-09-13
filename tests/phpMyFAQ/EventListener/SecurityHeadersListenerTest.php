<?php

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(SecurityHeadersListener::class)]
final class SecurityHeadersListenerTest extends TestCase
{
    public function testHtmlResponseGetsAllHardeningHeaders(): void
    {
        $response = new Response('<html></html>');
        $event = $this->createEvent($response);

        new SecurityHeadersListener()->onKernelResponse($event);

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertSame('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        self::assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        self::assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));

        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString("default-src 'self'", $csp);
        self::assertStringContainsString(
            "script-src 'self' 'unsafe-inline' https://www.google.com https://www.gstatic.com",
            $csp,
        );
        self::assertStringContainsString("style-src 'self' 'unsafe-inline'", $csp);
        self::assertStringContainsString("img-src 'self' data: https:", $csp);
        self::assertStringContainsString("font-src 'self' data:", $csp);
        self::assertStringContainsString("connect-src 'self'", $csp);
        self::assertStringContainsString("frame-src 'self' https://www.google.com", $csp);
        self::assertStringContainsString("frame-ancestors 'self'", $csp);
        self::assertStringContainsString("object-src 'none'", $csp);
        self::assertStringContainsString("base-uri 'self'", $csp);
        self::assertStringContainsString("form-action 'self'", $csp);
    }

    public function testAllowedMediaHostsAreAppendedToFrameAndMediaSources(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('getAllowedMediaHosts')
            ->willReturn(['www.youtube.com', ' https://player.vimeo.com ', 'not a host!', '']);

        $response = new Response('<html></html>');
        new SecurityHeadersListener($configuration)->onKernelResponse($this->createEvent($response));

        $csp = (string) $response->headers->get('Content-Security-Policy');
        self::assertStringContainsString(
            "frame-src 'self' https://www.google.com https://www.youtube.com https://player.vimeo.com;",
            $csp,
        );
        self::assertStringContainsString("media-src 'self' https://www.youtube.com https://player.vimeo.com;", $csp);
        self::assertStringNotContainsString('not a host', $csp);
    }

    public function testExistingHeadersAreNotOverwritten(): void
    {
        $response = new Response('<html></html>');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        new SecurityHeadersListener()->onKernelResponse($this->createEvent($response));

        self::assertSame('DENY', $response->headers->get('X-Frame-Options'));
        self::assertSame("default-src 'none'", $response->headers->get('Content-Security-Policy'));
        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
    }

    public function testNonHtmlResponsesOnlyGetNosniff(): void
    {
        $response = new JsonResponse(['ok' => true]);

        new SecurityHeadersListener()->onKernelResponse($this->createEvent($response));

        self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertFalse($response->headers->has('X-Frame-Options'));
        self::assertFalse($response->headers->has('Permissions-Policy'));
    }

    public function testSubRequestsAreIgnored(): void
    {
        $response = new Response('<html></html>');

        new SecurityHeadersListener()->onKernelResponse($this->createEvent(
            $response,
            HttpKernelInterface::SUB_REQUEST,
        ));

        self::assertFalse($response->headers->has('Content-Security-Policy'));
        self::assertFalse($response->headers->has('X-Content-Type-Options'));
    }

    private function createEvent(Response $response, int $type = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        return new ResponseEvent($this->createStub(HttpKernelInterface::class), Request::create('/'), $type, $response);
    }
}
