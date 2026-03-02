<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Captcha\CaptchaInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(CaptchaController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CaptchaControllerTest extends ApiControllerTestCase
{
    public function testRenderImageReturnsNotFoundForNonBuiltinCaptcha(): void
    {
        $captcha = $this->createStub(CaptchaInterface::class);

        $controller = new CaptchaController($captcha);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->renderImage();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('', (string) $response->getContent());
    }

    public function testRenderImageReturnsJpegResponseForBuiltinCaptcha(): void
    {
        $captcha = $this->createMock(BuiltinCaptcha::class);
        $captcha->expects($this->once())->method('getCaptchaImage')->willReturn('jpeg-binary');

        $controller = new CaptchaController($captcha);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $response = $controller->renderImage();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('image/jpeg', $response->headers->get('Content-Type'));
        self::assertSame('jpeg-binary', (string) $response->getContent());
    }
}
