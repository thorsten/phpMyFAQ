<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Rating;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(VotingController::class)]
#[UsesNamespace('phpMyFAQ')]
final class VotingControllerFlowTest extends ApiControllerTestCase
{
    /**
     * @return array{0: \Symfony\Component\HttpFoundation\Session\Session, 1: string}
     */
    private function createValidCsrfSession(): array
    {
        $session = $this->createSession();
        $csrfToken = Token::getInstance($session)->getTokenString('voting');

        return [$session, $csrfToken];
    }

    /**
     * A vote must never be stored for a FAQ the requester may not see; the response must
     * not reveal whether the record exists.
     */
    public function testCreateReturnsNotFoundForFaqTheRequesterMayNotSee(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();
        $this->seedUnpublishedFaq(42);

        $rating = $this->createMock(Rating::class);
        $rating->expects($this->never())->method('check');
        $rating->expects($this->never())->method('create');
        $rating->expects($this->never())->method('update');

        $controller = new VotingController($rating, $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 4,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame(Translation::get('msgAccessDenied'), $payload['error']);
    }

    private function seedUnpublishedFaq(int $faqId): void
    {
        self::assertNotFalse(
            $this->configuration
                ->getDb()
                ->query(sprintf(
                    "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
             VALUES (%d, 'en', %d, 0, 'draft', 0, '', 'Secret draft', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
                    $faqId,
                    1000 + $faqId,
                )),
        );
        self::assertNotFalse(
            $this->configuration
                ->getDb()
                ->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $faqId)),
        );
    }

    public function testCreateReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $session = $this->createSession();
        $this->injectControllerState($controller, $currentUser, $session);

        $response = $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 3,
            'csrfToken' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testCreateThrowsExceptionForInvalidJson(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid JSON data');

        $controller->create(Request::create('/api/voting', 'POST', content: ''));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsMissing(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenFaqIdIsMissing(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing FAQ ID');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'value' => 3,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenFaqIdIsInvalid(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing FAQ ID');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 0,
            'value' => 3,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsTooLow(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 0,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsTooHigh(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 6,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsNotAnInteger(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 'not-an-integer',
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsBadRequestWhenVotingIsNotAllowed(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $rating = $this->createMock(Rating::class);
        $rating->expects($this->once())->method('check')->with(42, '127.0.0.1')->willReturn(false);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('error_save_voting', 42);

        $controller = new VotingController($rating, $userSession);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 5,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testCreateCreatesInitialVoteWhenNoVotesExist(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $rating = $this->createMock(Rating::class);
        $rating->expects($this->once())->method('check')->with(42, '127.0.0.1')->willReturn(true);
        $rating->expects($this->once())->method('getNumberOfVotings')->with(42)->willReturn(0);
        $rating
            ->expects($this->once())
            ->method('create')
            ->with($this->callback(static function (Vote $vote): bool {
                return $vote->getFaqId() === 42 && $vote->getVote() === 4 && $vote->getIp() === '127.0.0.1';
            }))
            ->willReturn(true);
        $rating->expects($this->never())->method('update');
        $rating->expects($this->once())->method('get')->with(42)->willReturn('rating-html');

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_voting', 42);

        $controller = new VotingController($rating, $userSession);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 4,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('rating-html', $payload['rating']);
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateUpdatesVoteWhenVotesAlreadyExist(): void
    {
        [$session, $csrfToken] = $this->createValidCsrfSession();

        $rating = $this->createMock(Rating::class);
        $rating->expects($this->once())->method('check')->with(42, '127.0.0.1')->willReturn(true);
        $rating->expects($this->once())->method('getNumberOfVotings')->with(42)->willReturn(3);
        $rating->expects($this->never())->method('create');
        $rating
            ->expects($this->once())
            ->method('update')
            ->with($this->callback(static function (Vote $vote): bool {
                return $vote->getFaqId() === 42 && $vote->getVote() === 2 && $vote->getIp() === '127.0.0.1';
            }))
            ->willReturn(true);
        $rating->expects($this->once())->method('get')->with(42)->willReturn('updated-rating');

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('save_voting', 42);

        $controller = new VotingController($rating, $userSession);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser, $session);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 2,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('updated-rating', $payload['rating']);
        self::assertArrayHasKey('success', $payload);
    }
}
