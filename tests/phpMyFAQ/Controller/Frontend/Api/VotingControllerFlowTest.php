<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Entity\Vote;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Rating;
use phpMyFAQ\User\UserSession;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(VotingController::class)]
#[UsesNamespace('phpMyFAQ')]
final class VotingControllerFlowTest extends ApiControllerTestCase
{
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
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenFaqIdIsMissing(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing FAQ ID');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'value' => 3,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenFaqIdIsInvalid(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing FAQ ID');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 0,
            'value' => 3,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsTooLow(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 0,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateThrowsExceptionWhenVoteValueIsTooHigh(): void
    {
        $controller = new VotingController($this->createStub(Rating::class), $this->createStub(UserSession::class));
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid vote value');

        $controller->create(Request::create('/api/voting', 'POST', content: json_encode([
            'id' => 42,
            'value' => 6,
        ], JSON_THROW_ON_ERROR)));
    }

    public function testCreateReturnsBadRequestWhenVotingIsNotAllowed(): void
    {
        $rating = $this->createMock(Rating::class);
        $rating->expects($this->once())->method('check')->with(42, '127.0.0.1')->willReturn(false);

        $userSession = $this->createMock(UserSession::class);
        $userSession->expects($this->once())->method('setCurrentUser')->willReturnSelf();
        $userSession->expects($this->once())->method('userTracking')->with('error_save_voting', 42);

        $controller = new VotingController($rating, $userSession);
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);
        $this->injectControllerState($controller, $currentUser);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 5,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('error', (string) $response->getContent());
    }

    public function testCreateCreatesInitialVoteWhenNoVotesExist(): void
    {
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
        $this->injectControllerState($controller, $currentUser);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 4,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('rating-html', $payload['rating']);
        self::assertArrayHasKey('success', $payload);
    }

    public function testCreateUpdatesVoteWhenVotesAlreadyExist(): void
    {
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
        $this->injectControllerState($controller, $currentUser);

        $request = Request::create('/api/voting', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1'], content: json_encode([
            'id' => 42,
            'value' => 2,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('updated-rating', $payload['rating']);
        self::assertArrayHasKey('success', $payload);
    }
}
