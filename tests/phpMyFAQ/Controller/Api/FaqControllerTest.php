<?php

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\BasicPermission;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AllowMockObjectsWithoutExpectations]
class FaqControllerTest extends TestCase
{
    private const string TOKEN = 'valid-token';

    private const int EDITOR_ID = 23;

    protected function setUp(): void
    {
        parent::setUp();

        // hasValidToken() reads the token from the PHP globals via Request::createFromGlobals().
        $_SERVER['HTTP_X_PMF_TOKEN'] = self::TOKEN;
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_X_PMF_TOKEN']);

        parent::tearDown();
    }

    private function buildController(Faq $faq): FaqController
    {
        $reflection = new ReflectionClass(FaqController::class);
        $controller = $reflection->newInstanceWithoutConstructor();

        $language = $this->createMock(Language::class);
        $language->method('getLanguage')->willReturn('en');

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $item) => match ($item) {
            'api.apiClientToken' => self::TOKEN,
            default => null,
        });
        $configuration->method('getLanguage')->willReturn($language);

        // An authenticated editor holding the global "edit FAQ" right.
        $permission = $this->createMock(BasicPermission::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('getUserId')->willReturn(self::EDITOR_ID);
        $currentUser->perm = $permission;

        $container = $this->createMock(ContainerBuilder::class);
        $container->method('get')->willReturnCallback(static fn(string $id) => match ($id) {
            'phpmyfaq.faq' => $faq,
            default => null,
        });

        $parent = $reflection->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $configuration);
        $parent->getProperty('currentUser')->setValue($controller, $currentUser);

        return $controller;
    }

    private function updateRequest(int $faqId): Request
    {
        return Request::create('/api/v3.1/faq/update', 'PUT', [], [], [], [], json_encode([
            'faq-id' => $faqId,
            'language' => 'en',
            'question' => 'HACKED VIA BOLA',
            'answer' => '<p>Editor modified a restricted FAQ</p>',
            'keywords' => '',
            'author' => 'Attacker',
            'email' => 'attacker@evil.test',
            'is-active' => false,
            'is-sticky' => false,
        ]));
    }

    /**
     * Security regression: the global "edit FAQ" right must not be sufficient to modify
     * a record the requester is not permitted to access (BOLA on PUT /faq/update).
     */
    public function testUpdateReturnsNotFoundAndDoesNotWriteWhenFaqIsNotEditableForUser(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->expects($this->once())->method('isFaqEditableForUser')->with(1, 'en')->willReturn(false);
        $faq->expects($this->never())->method('update');

        $response = $this->buildController($faq)->update($this->updateRequest(1));

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(
            ['stored' => false, 'error' => 'The given FAQ was not found.'],
            json_decode($response->getContent(), true),
        );
    }

    public function testUpdateScopesTheObjectCheckToTheRequestingUser(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('setUser')->with(self::EDITOR_ID)->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->method('isFaqEditableForUser')->willReturn(false);

        $this->buildController($faq)->update($this->updateRequest(1));
    }

    public function testUpdateWritesWhenFaqIsEditableForUser(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->method('setUser')->willReturnSelf();
        $faq->method('setGroups')->willReturnSelf();
        $faq->method('hasTitleAHash')->willReturn(false);
        $faq->expects($this->once())->method('isFaqEditableForUser')->with(1, 'en')->willReturn(true);
        $faq
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(
                static fn(FaqEntity $entity) => $entity->getId() === 1 && $entity->getLanguage() === 'en',
            ))
            ->willReturnArgument(0);

        $response = $this->buildController($faq)->update($this->updateRequest(1));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(['stored' => true], json_decode($response->getContent(), true));
    }
}
