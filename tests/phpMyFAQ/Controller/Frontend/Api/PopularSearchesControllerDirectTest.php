<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(PopularSearchesController::class)]
#[UsesNamespace('phpMyFAQ')]
final class PopularSearchesControllerDirectTest extends ApiControllerTestCase
{
    public function testPopularReturnsResults(): void
    {
        $search = $this->createMock(Search::class);
        $search
            ->expects($this->once())
            ->method('getMostPopularSearches')
            ->willReturn([['id' => 1, 'searchterm' => 'mac', 'number' => '18']]);

        $controller = new PopularSearchesController($search);
        $currentUser = $this->createAuthenticatedUserMock();
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->popular();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('mac', $payload[0]['searchterm']);
        self::assertSame('18', $payload[0]['number']);
    }

    public function testPopularReturnsNotFoundWhenEmpty(): void
    {
        $search = $this->createMock(Search::class);
        $search->expects($this->once())->method('getMostPopularSearches')->willReturn([]);

        $controller = new PopularSearchesController($search);
        $currentUser = $this->createAuthenticatedUserMock();
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->popular();

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('[]', (string) $response->getContent());
    }

    public function testPopularPassesConfiguredCountToSearch(): void
    {
        $search = $this->createMock(Search::class);
        $search
            ->expects($this->once())
            ->method('getMostPopularSearches')
            ->with(5)
            ->willReturn([['id' => 1, 'searchterm' => 'php', 'number' => '3']]);

        $controller = new PopularSearchesController($search);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        // Override after controller construction: the AbstractController constructor reads
        // template settings via getAll(), which reloads the whole config from the database.
        $this->overrideConfigurationValues(['search.numberSearchTerms' => '5']);

        $response = $controller->popular();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testPopularFallsBackToSevenWhenConfiguredCountIsZero(): void
    {
        $search = $this->createMock(Search::class);
        $search
            ->expects($this->once())
            ->method('getMostPopularSearches')
            ->with(7)
            ->willReturn([['id' => 1, 'searchterm' => 'php', 'number' => '3']]);

        $controller = new PopularSearchesController($search);
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        // Override after controller construction: the AbstractController constructor reads
        // template settings via getAll(), which reloads the whole config from the database.
        $this->overrideConfigurationValues(['search.numberSearchTerms' => '0']);

        $response = $controller->popular();

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }
}
