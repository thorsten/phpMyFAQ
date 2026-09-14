<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use phpMyFAQ\Functional\PhpMyFaqTestKernel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(UnauthorizedUserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UnauthorizedUserControllerWebTest extends ControllerWebTestCase
{
    public function testRequestResetReturnsGenericSuccessForUnknownUser(): void
    {
        $response = $this->requestWithContext(
            'api',
            'PUT',
            '/user/password/update',
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode(['username' => 'no-such-user', 'email' => 'no-such@example.org'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(200, $response);
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }

    public function testResetEndpointRejectsRequestWithoutToken(): void
    {
        $response = $this->requestWithContext(
            'api',
            'POST',
            '/user/password/reset',
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode(['password' => 'NewSecret123', 'password_repeat' => 'NewSecret123'], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(400, $response);
    }

    /**
     * Every request boots its own kernel, so the counter can only survive through the
     * shared cache-backed rate limiter the container injects. A controller instantiated
     * with `new` would fall back to an in-memory limiter and never return 429.
     */
    public function testRequestResetReturnsTooManyRequestsAfterFiveRequestsFromOneIp(): void
    {
        $statusCodes = [];
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $statusCodes[] = $this->sendFromFreshKernel(
                'PUT',
                '/user/password/update',
                ['username' => 'no-such-user', 'email' => 'no-such@example.org'],
                '203.0.113.10',
            )->getStatusCode();
        }

        self::assertSame([200, 200, 200, 200, 200, 429], $statusCodes);
    }

    public function testResetEndpointReturnsTooManyRequestsAfterTenRequestsFromOneIp(): void
    {
        $statusCodes = [];
        for ($attempt = 0; $attempt < 11; $attempt++) {
            $statusCodes[] = $this->sendFromFreshKernel(
                'POST',
                '/user/password/reset',
                ['u' => 1, 'exp' => 1, 'sig' => 'invalid', 'password' => 'NewSecret123', 'password_repeat' => 'NewSecret123'],
                '203.0.113.20',
            )->getStatusCode();
        }

        self::assertSame(array_merge(array_fill(0, 10, 400), [429]), $statusCodes);
    }

    /**
     * @param array<string, int|string> $payload
     */
    private function sendFromFreshKernel(string $method, string $uri, array $payload, string $clientIp): Response
    {
        if (static::$client === null) {
            self::createClient('api');
        }

        $kernel = new PhpMyFaqTestKernel('api');
        $request = Request::create(
            $uri,
            $method,
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => $clientIp,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_HOST' => 'localhost',
                'HTTPS' => 'on',
            ],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return $kernel->handle($request);
    }
}
