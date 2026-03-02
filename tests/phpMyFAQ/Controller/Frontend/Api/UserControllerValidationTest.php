<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Auth\AuthDatabase;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Mail;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(UserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserControllerValidationTest extends ApiControllerTestCase
{
    private function createController(): UserController
    {
        return new UserController($this->createStub(StopWords::class), $this->createStub(Mail::class));
    }

    private function createValidCsrfToken(
        \Symfony\Component\HttpFoundation\Session\Session $session,
        string $page,
    ): string {
        $csrfToken = Token::getInstance($session)->getTokenString($page);
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5($page), 0, 10))] = $csrfToken;

        return $csrfToken;
    }

    public function testExportUserDataReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = new Request([], [
            'pmf-csrf-token' => 'invalid',
            'userid' => 1,
        ]);

        $response = $controller->exportUserData($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testUpdateDataReturnsBadRequestForUserIdMismatchWithValidCsrf(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('ucp');
        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5('ucp'), 0, 10))] = $csrfToken;
        $currentUser = $this->createAuthenticatedUserMock(1);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 2,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"User ID mismatch!"}', (string) $response->getContent());
    }

    public function testUpdateDataReturnsUnauthorizedForInvalidCsrf(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(1), $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => 'invalid',
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testUpdateDataReturnsConflictWhenPasswordsDoNotMatch(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('local');

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'different123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('ad_user_error_passwordsDontMatch'), $payload['error']);
    }

    public function testUpdateDataReturnsConflictWhenPasswordIsTooShortForLocalUser(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('local');

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'short',
            'faqpassword_confirm' => 'short',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('ad_passwd_fail'), $payload['error']);
    }

    public function testUpdateDataReturnsSuccessForLocalUserWhenProfileAndAuthUpdateSucceed(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $authDriver = $this
            ->getMockBuilder(AuthDatabase::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'disableReadOnly',
                'update',
            ])
            ->getMock();
        $authDriver->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $authDriver->expects($this->once())->method('update')->with('testuser', 'password123')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('local');
        $currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'Test User',
                'is_visible' => 1,
                'email' => 'test@example.com',
                'twofactor_enabled' => 0,
            ])
            ->willReturn(true);
        $currentUser->method('getAuthContainer')->willReturn([$authDriver]);
        $currentUser->method('getLogin')->willReturn('testuser');

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
    }

    public function testUpdateDataReturnsBadRequestWhenAuthDriverUpdateFails(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $authDriver = $this
            ->getMockBuilder(AuthDatabase::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'disableReadOnly',
                'update',
                'getErrors',
            ])
            ->getMock();
        $authDriver->expects($this->once())->method('disableReadOnly')->willReturn(false);
        $authDriver->expects($this->once())->method('update')->with('testuser', 'password123')->willReturn(false);
        $authDriver->expects($this->once())->method('getErrors')->willReturn('Driver failed');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('local');
        $currentUser->expects($this->once())->method('setUserData')->willReturn(true);
        $currentUser->method('getAuthContainer')->willReturn([$authDriver]);
        $currentUser->method('getLogin')->willReturn('testuser');

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Driver failed', $payload['error']);
    }

    public function testUpdateDataReturnsSuccessForAzureUser(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('azure');
        $currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'is_visible' => 1,
                'twofactor_enabled' => 1,
                'secret' => 'azure-secret',
            ])
            ->willReturn(true);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Azure User',
            'email' => 'azure@example.com',
            'is_visible' => 'on',
            'faqpassword' => '',
            'faqpassword_confirm' => '',
            'twofactor_enabled' => 'on',
            'secret' => 'azure-secret',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
    }

    public function testUpdateDataReturnsBadRequestWhenAzureProfileSaveFails(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('azure');
        $currentUser->expects($this->once())->method('setUserData')->willReturn(false);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Azure User',
            'email' => 'azure@example.com',
            'is_visible' => 'on',
            'faqpassword' => '',
            'faqpassword_confirm' => '',
            'twofactor_enabled' => 'on',
            'secret' => 'azure-secret',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
    }

    public function testUpdateDataReturnsSuccessForWebAuthnUserWithoutPasswordLengthCheck(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('webauthn');
        $currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'Passkey User',
                'is_visible' => 1,
            ])
            ->willReturn(true);
        $currentUser->method('getAuthContainer')->willReturn([]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Passkey User',
            'email' => 'passkey@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'short',
            'faqpassword_confirm' => 'short',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
    }

    public function testUpdateDataReturnsBadRequestForWebAuthnUserWhenProfileSaveFails(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('webauthn');
        $currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'Passkey User',
                'is_visible' => 1,
            ])
            ->willReturn(false);
        $currentUser->method('getAuthContainer')->willReturn([]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Passkey User',
            'email' => 'passkey@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'short',
            'faqpassword_confirm' => 'short',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
    }

    public function testExportUserDataReturnsZipArchiveForAuthenticatedUser(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            self::markTestSkipped('ZIP extension not available.');
        }

        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'export-userdata');

        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'userid' => 1,
        ]);

        $response = $controller->exportUserData($request);

        self::assertInstanceOf(BinaryFileResponse::class, $response);
        self::assertSame('application/zip', $response->headers->get('Content-Type'));
        self::assertStringContainsString('attachment;', (string) $response->headers->get('Content-Disposition'));
    }

    public function testExportUserDataReturnsBadRequestForUserIdMismatch(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'export-userdata');

        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $request = new Request([], [
            'pmf-csrf-token' => $csrfToken,
            'userid' => 99,
        ]);

        $response = $controller->exportUserData($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('User ID mismatch!', (string) $response->getContent());
    }

    public function testRequestUserRemovalReturnsSuccessWhenMailCanBeSent(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.administrationMail' => 'admin@example.com']);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $mailer = $this->createMock(Mail::class);
        $mailer->expects($this->once())->method('setReplyTo')->with('test@example.com', 'Test User');
        $mailer->expects($this->once())->method('addTo')->with('admin@example.com');
        $mailer->expects($this->once())->method('send');

        $controller = new UserController($stopWords, $mailer);
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'request-removal');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserById')->with(1)->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['email', 'test@example.com'],
            ]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->requestUserRemoval($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgMailContact'), $payload['success']);
    }

    public function testRequestUserRemovalThrowsExceptionForInvalidJson(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON data');

        $controller->requestUserRemoval(new Request([], [], [], [], [], [], ''));
    }

    public function testRequestUserRemovalThrowsExceptionWhenCsrfTokenIsMissing(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing CSRF token');

        $controller->requestUserRemoval(new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testRequestUserRemovalThrowsExceptionWhenCsrfTokenIsInvalid(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $controller->requestUserRemoval(new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
            'pmf-csrf-token' => 'invalid',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testRequestUserRemovalReturnsBadRequestWhenUserValidationFails(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'request-removal');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserById')->with(1)->willReturn(false);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['email', 'test@example.com'],
            ]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->requestUserRemoval($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_user_error_loginInvalid'), $payload['error']);
    }

    public function testRequestUserRemovalReturnsBadRequestWhenStopWordValidationFails(): void
    {
        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(false);

        $controller = new UserController($stopWords, $this->createStub(Mail::class));
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'request-removal');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserById')->with(1)->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['email', 'test@example.com'],
            ]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->requestUserRemoval($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('err_sendMail'), $payload['error']);
    }

    public function testRequestUserRemovalReturnsBadRequestWhenMailerFails(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.administrationMail' => 'admin@example.com']);

        $stopWords = $this->createStub(StopWords::class);
        $stopWords->method('checkBannedWord')->willReturn(true);

        $mailer = $this->createMock(Mail::class);
        $mailer->expects($this->once())->method('setReplyTo')->with('test@example.com', 'Test User');
        $mailer->expects($this->once())->method('addTo')->with('admin@example.com');
        $mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new \phpMyFAQ\Core\Exception('SMTP failed'));

        $controller = new UserController($stopWords, $mailer);
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'request-removal');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserById')->with(1)->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getLogin')->willReturn('testuser');
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['email', 'test@example.com'],
            ]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userId' => 1,
            'name' => 'Test User',
            'loginname' => 'testuser',
            'email' => 'test@example.com',
            'question' => 'Please remove my account.',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->requestUserRemoval($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('SMTP failed', $payload['error']);
    }

    public function testRemoveTwofactorConfigReturnsSuccessWhenSecretIsReset(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'remove-twofactor');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser
            ->expects($this->once())
            ->method('setUserData')
            ->with($this->callback(static function (array $data): bool {
                return (
                    isset($data['secret'], $data['twofactor_enabled'])
                    && $data['secret'] !== ''
                    && $data['twofactor_enabled'] === 0
                );
            }))
            ->willReturn(true);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->removeTwofactorConfig($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('msgRemoveTwofactorConfigSuccessful'), $payload['success']);
    }

    public function testRemoveTwofactorConfigThrowsExceptionForInvalidJson(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid JSON data');

        $controller->removeTwofactorConfig(new Request([], [], [], [], [], [], ''));
    }

    public function testRemoveTwofactorConfigThrowsExceptionWhenCsrfTokenIsMissing(): void
    {
        $controller = $this->createController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $this->createSession());

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing CSRF token');

        $controller->removeTwofactorConfig(
            new Request([], [], [], [], [], [], json_encode(new \stdClass(), JSON_THROW_ON_ERROR)),
        );
    }

    public function testRemoveTwofactorConfigThrowsExceptionWhenCsrfTokenIsInvalid(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock(), $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid CSRF token');

        $controller->removeTwofactorConfig(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => 'invalid',
        ], JSON_THROW_ON_ERROR)));
    }

    public function testRemoveTwofactorConfigThrowsExceptionWhenUserIsNotLoggedIn(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'remove-twofactor');

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The user is not logged in.');

        $controller->removeTwofactorConfig(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testRemoveTwofactorConfigReturnsBadRequestWhenSecretResetFails(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'remove-twofactor');

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->expects($this->once())->method('setUserData')->willReturn(false);

        $this->injectControllerState($controller, $currentUser, $session);

        $response = $controller->removeTwofactorConfig(new Request([], [], [], [], [], [], json_encode([
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgErrorOccurred'), $payload['error']);
    }

    public function testUpdateDataReturnsBadRequestWhenLocalProfileSaveFailsWithoutWritableAuthDriver(): void
    {
        $controller = $this->createController();
        $session = $this->createSession();
        $csrfToken = $this->createValidCsrfToken($session, 'ucp');

        $authDriver = $this
            ->getMockBuilder(AuthDatabase::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'disableReadOnly',
            ])
            ->getMock();
        $authDriver->expects($this->once())->method('disableReadOnly')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(1);
        $currentUser->method('getUserAuthSource')->willReturn('local');
        $currentUser->expects($this->once())->method('setUserData')->willReturn(false);
        $currentUser->method('getAuthContainer')->willReturn([$authDriver]);

        $this->injectControllerState($controller, $currentUser, $session);

        $request = new Request([], [], [], [], [], [], json_encode([
            'userid' => 1,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'is_visible' => 'on',
            'faqpassword' => 'password123',
            'faqpassword_confirm' => 'password123',
            'twofactor_enabled' => 'off',
            'secret' => '',
            'pmf-csrf-token' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->updateData($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
    }
}
