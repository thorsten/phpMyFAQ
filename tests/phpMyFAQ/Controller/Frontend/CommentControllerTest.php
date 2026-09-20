<?php

/**
 * Frontend CommentController Test — guest comment response uniformity.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Controller\Frontend
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-09-20
 */

namespace phpMyFAQ\Controller\Frontend;

use Monolog\Logger;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Session\Token;
use phpMyFAQ\StopWords;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class CommentControllerTest extends TestCase
{
    private const string CSRF_VALUE = 'valid-add-comment-token';

    private CommentController $controller;
    private Configuration&MockObject $configurationMock;
    private ContainerBuilder&MockObject $containerMock;
    private CurrentUser&MockObject $currentUserMock;
    private Comments&MockObject $commentsMock;
    private Notification&MockObject $notificationMock;
    private User&MockObject $userMock;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $this->configurationMock = $this->createMock(Configuration::class);
        $this->configurationMock
            ->method('get')
            ->willReturnCallback(fn(string $item) => match ($item) {
                'records.allowCommentsForGuests' => true,
                'main.languageDetection' => false,
                'main.language' => 'en',
                'spam.enableCaptchaCode' => false,
                'security.enableGoogleReCaptchaV2' => false,
                default => null,
            });
        $this->configurationMock->method('getLogger')->willReturn($this->createMock(Logger::class));

        $this->currentUserMock = $this->createMock(CurrentUser::class);
        $this->currentUserMock->method('isLoggedIn')->willReturn(false);
        $this->currentUserMock->method('getUserId')->willReturn(-1);

        $faqMock = $this->createMock(Faq::class);
        $faqMock->method('isActive')->willReturn(true);

        $this->commentsMock = $this->createMock(Comments::class);
        $this->commentsMock->method('isCommentAllowed')->willReturn(true);

        $stopWordsMock = $this->createMock(StopWords::class);
        $stopWordsMock->method('checkBannedWord')->willReturn(true);

        $userSessionMock = $this->createMock(UserSession::class);
        $userSessionMock->method('setCurrentUser')->willReturnSelf();

        $languageMock = $this->createMock(Language::class);
        $languageMock->method('setLanguageFromConfiguration')->willReturn('en');

        $this->notificationMock = $this->createMock(Notification::class);
        $this->userMock = $this->createMock(User::class);

        $gravatarMock = $this->createMock(Gravatar::class);
        $gravatarMock->method('getImageUrl')->willReturn('https://www.gravatar.com/avatar/test');

        $session = $this->primeCsrfToken('add-comment', self::CSRF_VALUE);

        $this->containerMock = $this->createMock(ContainerBuilder::class);
        $this->containerMock
            ->method('get')
            ->willReturnCallback(fn(string $id) => match ($id) {
                'phpmyfaq.faq' => $faqMock,
                'phpmyfaq.comments' => $this->commentsMock,
                'phpmyfaq.stop-words' => $stopWordsMock,
                'phpmyfaq.user.session' => $userSessionMock,
                'phpmyfaq.language' => $languageMock,
                'phpmyfaq.user' => $this->userMock,
                'phpmyfaq.notification' => $this->notificationMock,
                'phpmyfaq.services.gravatar' => $gravatarMock,
                'session' => $session,
                default => null,
            });

        $reflection = new ReflectionClass(CommentController::class);
        $this->controller = $reflection->newInstanceWithoutConstructor();

        $parent = $reflection->getParentClass();
        $parent->getProperty('configuration')->setValue($this->controller, $this->configurationMock);
        $parent->getProperty('container')->setValue($this->controller, $this->containerMock);
        $parent->getProperty('currentUser')->setValue($this->controller, $this->currentUserMock);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        $_COOKIE = [];
        parent::tearDown();
    }

    /**
     * A guest whose display name and e-mail address both belong to a registered user must receive the
     * exact same response as any other guest, while the comment itself is not stored. This is the
     * regression guard for the account-enumeration report.
     */
    public function testGuestCommentWithRegisteredIdentityIsDiscardedWithUniformResponse(): void
    {
        $this->userMock->method('checkDisplayName')->willReturn(true);
        $this->userMock->method('checkMailAddress')->willReturn(true);

        $this->commentsMock->expects($this->never())->method('create');
        $this->notificationMock->expects($this->never())->method('sendFaqCommentNotification');

        $response = $this->controller->create($this->createCommentRequest('Registered User', 'registered@example.com'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            $this->expectedSuccessPayload('Registered User', 'registered@example.com'),
            $this->decode($response),
        );
    }

    public function testGuestCommentWithUnknownIdentityIsStored(): void
    {
        $this->userMock->method('checkDisplayName')->willReturn(false);
        $this->userMock->method('checkMailAddress')->willReturn(false);

        $this->commentsMock->expects($this->once())->method('create')->willReturn(true);
        $this->notificationMock->expects($this->once())->method('sendFaqCommentNotification');

        $response = $this->controller->create($this->createCommentRequest('Unknown Guest', 'guest@example.com'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            $this->expectedSuccessPayload('Unknown Guest', 'guest@example.com'),
            $this->decode($response),
        );
    }

    /**
     * Only a full match of display name AND e-mail address is treated as a registered identity.
     */
    public function testGuestCommentWithPartialMatchIsStored(): void
    {
        $this->userMock->method('checkDisplayName')->willReturn(true);
        $this->userMock->method('checkMailAddress')->willReturn(false);

        $this->commentsMock->expects($this->once())->method('create')->willReturn(true);

        $response = $this->controller->create($this->createCommentRequest('Registered User', 'other@example.com'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame(
            $this->expectedSuccessPayload('Registered User', 'other@example.com'),
            $this->decode($response),
        );
    }

    public function testGuestCommentWithRegisteredIdentityNeverReturnsConflict(): void
    {
        $this->userMock->method('checkDisplayName')->willReturn(true);
        $this->userMock->method('checkMailAddress')->willReturn(true);

        $response = $this->controller->create($this->createCommentRequest('Registered User', 'registered@example.com'));

        $this->assertNotSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        $this->assertArrayNotHasKey('error', $this->decode($response));
    }

    private function createCommentRequest(string $username, string $email): Request
    {
        return Request::create('/api/comment/create', 'POST', [], [], [], ['REQUEST_TIME' => 1758355200], json_encode([
            'pmf-csrf-token' => self::CSRF_VALUE,
            'type' => 'faq',
            'id' => 1,
            'user' => $username,
            'mail' => $email,
            'comment_text' => 'probe',
            'captcha' => '',
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function expectedSuccessPayload(string $username, string $email): array
    {
        return [
            'success' => Translation::get(key: 'msgCommentThanks'),
            'commentData' => [
                'username' => $username,
                'email' => $email,
                'comment' => 'probe',
                'date' => '1758355200',
                'gravatarUrl' => 'https://www.gravatar.com/avatar/test',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        return json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Primes a valid CSRF token in a real session plus its matching cookie so that
     * Token::verifyToken() succeeds and the controller logic beyond the CSRF check runs.
     */
    private function primeCsrfToken(string $page, string $tokenValue): Session
    {
        Token::resetInstanceForTests();

        $session = new Session(new MockArraySessionStorage());

        $tokenReflection = new ReflectionClass(Token::class);
        $token = $tokenReflection->newInstanceWithoutConstructor();
        $token
            ->setPage($page)
            ->setExpiry(time() + 3600)
            ->setSessionToken($tokenValue)
            ->setCookieToken($tokenValue);

        $session->set(sprintf('%s.%s', Token::PMF_SESSION_NAME, $page), $token);

        $_COOKIE[sprintf('%s-%s', Token::PMF_SESSION_NAME, substr(md5($page), 0, 10))] = $tokenValue;

        return $session;
    }
}
