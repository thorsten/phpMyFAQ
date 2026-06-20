<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(PopularSearchesController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PopularSearchesControllerWebTest extends ControllerWebTestCase
{
    /**
     * Exercises the route through the real container, ensuring the controller is
     * registered for dependency injection. Without the DI registration this fails
     * with an ArgumentCountError / 500 instead of a valid JSON response.
     */
    public function testPopularReturnsValidJsonThroughContainer(): void
    {
        $response = $this->requestApi('GET', '/searches/popular');

        self::assertContains(
            $response->getStatusCode(),
            [Response::HTTP_OK, Response::HTTP_NOT_FOUND],
            'The popular searches endpoint must resolve through the container and '
            . 'return a valid HTTP status, not a 500 from an unregistered controller.',
        );
        self::assertStringContainsString('json', (string) $response->headers->get('Content-Type'));
        self::assertJson((string) $response->getContent());
    }
}
