<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(TranslationController::class)]
#[UsesNamespace('phpMyFAQ')]
final class TranslationControllerTest extends ApiControllerTestCase
{
    private function createController(
        ?\Closure $setCurrentLanguage = null,
        ?\Closure $getTranslations = null,
    ): TranslationController {
        $controller = new TranslationController($setCurrentLanguage, $getTranslations);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        return $controller;
    }

    public function testTranslationsWithSupportedLanguageReturnsJsonResponse(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);

        self::assertInstanceOf(JsonResponse::class, $response);
    }

    public function testTranslationsWithSupportedLanguageReturnsOk(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testTranslationsWithUnsupportedLanguageReturnsBadRequest(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'xyz');

        $response = $this->createController()->translations($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testTranslationsWithUnsupportedLanguageReturnsError(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'invalid');

        $response = $this->createController()->translations($request);
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayHasKey('error', $data);
    }

    public function testTranslationsReturnsValidJsonContent(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);

        self::assertJson((string) $response->getContent());
    }

    public function testTranslationsReturnsArrayData(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($data);
    }

    public function testTranslationsResponseHasCorrectContentType(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);

        self::assertTrue($response->headers->has('Content-Type'));
        self::assertStringContainsString('application/json', (string) $response->headers->get('Content-Type'));
    }

    public function testTranslationsWithMultipleSupportedLanguages(): void
    {
        foreach (['en', 'de', 'fr', 'es'] as $lang) {
            $request = new Request();
            $request->attributes->set('language', $lang);

            $response = $this->createController()->translations($request);

            self::assertInstanceOf(JsonResponse::class, $response);
            self::assertContains($response->getStatusCode(), [Response::HTTP_OK, Response::HTTP_BAD_REQUEST]);
        }
    }

    public function testTranslationsResponseIsNotEmpty(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController()->translations($request);

        self::assertNotEmpty((string) $response->getContent());
    }

    public function testTranslationsReturnsInternalServerErrorWhenTranslationLoaderFails(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $response = $this->createController(static function (string $language): void {
            throw new Exception('translation backend failed');
        })->translations($request);
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame('translation backend failed', $data['error']);
    }

    public function testTranslationsUsesInjectedTranslationCallbacks(): void
    {
        $request = new Request();
        $request->attributes->set('language', 'en');

        $currentLanguage = null;
        $response = $this->createController(static function (string $language) use (&$currentLanguage): void {
            $currentLanguage = $language;
        }, static fn(): array => ['hello' => 'world'])->translations($request);
        $data = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('en', $currentLanguage);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(['hello' => 'world'], $data);
    }
}
