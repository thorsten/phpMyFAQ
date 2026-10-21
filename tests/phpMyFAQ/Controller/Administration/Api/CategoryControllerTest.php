<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Category\Order;
use phpMyFAQ\Configuration;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class CategoryControllerTest extends TestCase
{
    private Session $session;

    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        Token::resetInstanceForTests();
        $this->session = new Session(new MockArraySessionStorage());
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        unset($_COOKIE[$this->cookieName()]);

        parent::tearDown();
    }

    private function buildController(): CategoryController
    {
        $controller = (new ReflectionClass(CategoryController::class))->newInstanceWithoutConstructor();

        $order = new Order($this->createStub(Configuration::class));
        $session = $this->session;
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static fn(string $id) => match ($id) {
                'session' => $session,
                'phpmyfaq.category.order' => $order,
                default => null,
            });

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(1);

        $parent = (new ReflectionClass(CategoryController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $user);

        return $controller;
    }

    private function cookieName(): string
    {
        return sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('category'), 0, 10));
    }

    private function createValidCsrfToken(): string
    {
        $tokenString = 'valid-test-token';

        $token = (new ReflectionClass(Token::class))->newInstanceWithoutConstructor();
        $token
            ->setPage('category')
            ->setExpiry(time() + 3600)
            ->setSessionToken($tokenString)
            ->setCookieToken($tokenString);

        $this->session->set(sprintf('%s.%s', Token::PMF_SESSION_NAME, 'category'), $token);
        $_COOKIE[$this->cookieName()] = $tokenString;

        return $tokenString;
    }

    private function jsonRequest(string $method, string $content): Request
    {
        return Request::create('/admin/api/category', $method, [], [], [], [], $content);
    }

    public function testDeleteReturnsBadRequestForPayloadWithoutCsrfToken(): void
    {
        $response = $this->buildController()->delete($this->jsonRequest('DELETE', '{"foo":1}'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertArrayHasKey('error', json_decode($response->getContent(), true));
    }

    public function testDeleteReturnsBadRequestForInvalidJson(): void
    {
        $response = $this->buildController()->delete($this->jsonRequest('DELETE', 'not json'));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testDeleteReturnsBadRequestForNonNumericCategoryId(): void
    {
        $response = $this->buildController()->delete($this->jsonRequest(
            'DELETE',
            '{"categoryId":"abc","language":"en","csrfToken":"token"}',
        ));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUpdateOrderReturnsBadRequestForMissingCategoryTree(): void
    {
        $response = $this->buildController()->updateOrder($this->jsonRequest(
            'POST',
            '{"categoryId":1,"csrfToken":"token"}',
        ));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }

    public function testUpdateOrderReturnsBadRequestWhenCategoryIsNotInTree(): void
    {
        $csrfToken = $this->createValidCsrfToken();
        $payload = json_encode([
            'categoryId' => 99,
            'categoryTree' => [['id' => 1, 'children' => []]],
            'csrfToken' => $csrfToken,
        ]);

        $response = $this->buildController()->updateOrder($this->jsonRequest('POST', $payload));

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
