<?php

namespace phpMyFAQ\Controller\Administration\Api;

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
class AttachmentControllerTest extends TestCase
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

    private function buildController(Session $session): AttachmentController
    {
        $controller = (new ReflectionClass(AttachmentController::class))->newInstanceWithoutConstructor();

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session) {
                return match ($id) {
                    'session' => $session,
                    default => null,
                };
            });

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $actingUser = $this->createMock(CurrentUser::class);
        $actingUser->perm = $perm;
        $actingUser->method('isLoggedIn')->willReturn(true);
        $actingUser->method('getUserId')->willReturn(1);

        $parent = (new ReflectionClass(AttachmentController::class))->getParentClass();
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

    /**
     * The upload endpoint is multipart/form-data, so the token arrives as a
     * regular POST field named "pmf-csrf-token".
     *
     * @param array<string, string> $post
     */
    private function uploadRequest(array $post): Request
    {
        return new Request([], $post);
    }

    public function testUploadRejectsInvalidCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->primeCsrf($session, 'upload-attachment');

        $controller = $this->buildController($session);

        $response = $controller->upload($this->uploadRequest([
            'pmf-csrf-token' => 'wrong-token',
            'record_id' => '1',
            'record_lang' => 'en',
        ]));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testUploadRejectsMissingCsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $this->primeCsrf($session, 'upload-attachment');

        $controller = $this->buildController($session);

        $response = $controller->upload($this->uploadRequest([
            'record_id' => '1',
            'record_lang' => 'en',
        ]));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    /**
     * With a valid CSRF token the request passes the token gate and proceeds to
     * input validation. Without any files this yields a 400 (not a 401), proving
     * the token check accepts a valid token rather than blocking every upload.
     */
    public function testUploadWithValidCsrfTokenPassesTokenGate(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = $this->primeCsrf($session, 'upload-attachment');

        $controller = $this->buildController($session);

        $response = $controller->upload($this->uploadRequest([
            'pmf-csrf-token' => $csrf,
            'record_id' => '1',
            'record_lang' => 'en',
        ]));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
    }
}
