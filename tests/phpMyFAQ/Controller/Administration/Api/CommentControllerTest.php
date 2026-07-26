<?php

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class CommentControllerTest extends TestCase
{
    private Configuration $configuration;

    protected function setUp(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $instance = new ReflectionProperty(Token::class, 'instance');
        $instance->setValue(null, null);
        $_COOKIE = [];
    }

    private function buildController(Session $session, Comments $comments): CommentController
    {
        $controller = (new ReflectionClass(CommentController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session, $comments) {
                return match ($id) {
                    'session' => $session,
                    'phpmyfaq.comments' => $comments,
                    default => null,
                };
            });

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(1);

        $parent = (new ReflectionClass(CommentController::class))->getParentClass();
        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $this->configuration);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    private function primeCsrf(Session $session, string $page): string
    {
        $tokenValue = 'unit-test-token-' . bin2hex(random_bytes(8));
        $cookieName = 'pmf-csrf-token-' . substr(md5($page), 0, 10);

        $reflection = new ReflectionClass(Token::class);
        $token = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('session')->setValue($token, $session);
        $token->setPage($page);
        $token->setExpiry(time() + 3600);
        $token->setSessionToken($tokenValue);
        $token->setCookieToken($tokenValue);

        $session->set('pmf-csrf-token.' . $page, $token);
        $_COOKIE[$cookieName] = $tokenValue;

        return $tokenValue;
    }

    private function jsonRequest(array $payload): Request
    {
        return new Request([], [], [], [], [], [], json_encode($payload));
    }

    /**
     * Regression test: the frontend serializes FormData, so comment IDs arrive
     * as JSON strings. With strict_types the controller must cast them before
     * calling Comments::delete(string, int), otherwise every deletion fails
     * with a TypeError ("Network response was not ok" in the admin UI).
     */
    public function testDeleteAcceptsStringCommentIdsFromSerializedFormData(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'delete-comment');

        $comments = $this->createMock(Comments::class);
        $comments
            ->expects($this->exactly(2))
            ->method('delete')
            ->with('faq', $this->logicalOr(42, 43))
            ->willReturn(true);

        $controller = $this->buildController($session, $comments);

        $response = $controller->delete($this->jsonRequest([
            'type' => 'faq',
            'data' => [
                'pmf-csrf-token' => $csrf,
                'comments[]' => ['42', '43'],
            ],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue(json_decode($response->getContent())->success);
    }

    public function testDeleteAcceptsSingleStringCommentId(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'delete-comment');

        $comments = $this->createMock(Comments::class);
        $comments
            ->expects($this->once())
            ->method('delete')
            ->with('news', 7)
            ->willReturn(true);

        $controller = $this->buildController($session, $comments);

        $response = $controller->delete($this->jsonRequest([
            'type' => 'news',
            'data' => [
                'pmf-csrf-token' => $csrf,
                'comments[]' => '7',
            ],
        ]));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue(json_decode($response->getContent())->success);
    }

    public function testDeleteRejectsInvalidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->primeCsrf($session, 'delete-comment');

        $comments = $this->createMock(Comments::class);
        $comments->expects($this->never())->method('delete');

        $controller = $this->buildController($session, $comments);

        $response = $controller->delete($this->jsonRequest([
            'type' => 'faq',
            'data' => [
                'pmf-csrf-token' => 'wrong-token',
                'comments[]' => ['42'],
            ],
        ]));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }
}
