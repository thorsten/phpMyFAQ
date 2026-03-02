<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Faq\Permission;
use phpMyFAQ\Helper\SearchHelper;
use phpMyFAQ\Language\Plurals;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Search;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(AutoCompleteController::class)]
#[UsesNamespace('phpMyFAQ')]
final class AutoCompleteControllerDirectTest extends ApiControllerTestCase
{
    public function testSearchReturnsNotFoundForZeroSearchString(): void
    {
        $controller = new AutoCompleteController(
            $this->createStub(Permission::class),
            $this->createStub(Search::class),
            $this->createStub(SearchHelper::class),
            $this->createStub(Plurals::class),
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->search(Request::create('/api/autocomplete?search=0', 'GET'));

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('[]', (string) $response->getContent());
    }

    public function testSearchReturnsHelperResultForValidQuery(): void
    {
        $faqSearch = $this->createMock(Search::class);
        $faqSearch->expects($this->once())->method('setCategory');
        $faqSearch->expects($this->once())->method('autoComplete')->with('test')->willReturn([]);

        $searchHelper = $this->createMock(SearchHelper::class);
        $searchHelper->expects($this->once())->method('setSearchTerm')->with('test')->willReturnSelf();
        $searchHelper->expects($this->once())->method('setCategory')->willReturnSelf();
        $searchHelper->expects($this->once())->method('setPlurals')->willReturnSelf();
        $searchHelper
            ->expects($this->once())
            ->method('createAutoCompleteResult')
            ->willReturn([['title' => 'Test FAQ', 'url' => 'https://localhost/test']]);

        $controller = new AutoCompleteController(
            $this->createStub(Permission::class),
            $faqSearch,
            $searchHelper,
            $this->createStub(Plurals::class),
        );
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $this->createSession());

        $response = $controller->search(Request::create('/api/autocomplete?search=test', 'GET'));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Test FAQ', $payload[0]['title']);
        self::assertSame('https://localhost/test', $payload[0]['url']);
    }
}
