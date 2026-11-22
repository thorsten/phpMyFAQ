<?php

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\Configuration;
use phpMyFAQ\Network;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Follows a visitor over several requests on a private database copy: the "pmf-sid" cookie handed
 * out by the first tracked request has to bring the visitor back to the same faqsessions row.
 */
#[CoversClass(UserSession::class)]
#[UsesNamespace('phpMyFAQ')]
final class UserSessionCookieTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private string $trackingDirectory;

    /** @var list<array{name: string, value: string, options: array<string, mixed>}> */
    private array $cookies = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createTestConfiguration();
        $this->configuration->set('main.enableUserTracking', 'true');
        $this->configuration->set('main.referenceURL', 'https://faq.example.org/faq/');
        $this->configuration->getDb()->query('DELETE FROM faqsessions');

        $this->trackingDirectory = sys_get_temp_dir() . '/pmf-user-session-cookie-' . bin2hex(random_bytes(6));
        mkdir($this->trackingDirectory, 0o755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->trackingDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->trackingDirectory);

        parent::tearDown();
    }

    public function testFirstTrackedRequestHandsOutTheSessionCookie(): void
    {
        $this->track($this->createRequest(requestTime: 2000));

        $this->assertCount(1, $this->cookies);
        $this->assertSame(UserSession::COOKIE_NAME_SESSION_ID, $this->cookies[0]['name']);
        $this->assertSame((string) $this->storedSessions()[0]['sid'], $this->cookies[0]['value']);
        $this->assertSame(
            [
                'expires' => 5600,
                'path' => '/faq',
                'domain' => 'faq.example.org',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'lax',
            ],
            $this->cookies[0]['options'],
        );
    }

    public function testSessionCookieIsSecureOnHttpsAndUsesTheRootPathWithoutASubdirectory(): void
    {
        $this->configuration->set('main.referenceURL', 'https://faq.example.org');

        $this->track($this->createRequest(requestTime: 2000, server: ['HTTPS' => 'on']));

        $this->assertTrue($this->cookies[0]['options']['secure']);
        $this->assertSame('/', $this->cookies[0]['options']['path']);
    }

    public function testReturningVisitorReusesTheSessionRow(): void
    {
        $this->track($this->createRequest(requestTime: 2000));
        $sessionId = $this->cookies[0]['value'];

        $returning = $this->track($this->createRequest(requestTime: 2600, sessionCookie: $sessionId));

        $this->assertSame(
            [['sid' => (int) $sessionId, 'time' => 2600]],
            $this->storedSessions(),
            'The second request must update the session of the first one instead of adding a row.',
        );
        $this->assertSame((int) $sessionId, $returning->getCurrentSessionId());
        $this->assertSame($sessionId, $this->cookies[1]['value']);
        $this->assertSame(6200, $this->cookies[1]['options']['expires']);
    }

    public function testSeveralTrackedActionsOfTheFirstRequestShareOneSession(): void
    {
        $session = $this->createSession($this->createRequest(requestTime: 2000));

        $session->userTracking('fulltext_search', 0);
        $session->userTracking('fulltext_search', 'phpMyFAQ');

        $this->assertCount(1, $this->storedSessions());
        $this->assertCount(1, $this->cookies);
    }

    public function testSessionCookieOfAnotherAddressStartsANewSession(): void
    {
        $this->track($this->createRequest(requestTime: 2000));
        $foreignSessionId = $this->cookies[0]['value'];

        $this->track($this->createRequest(requestTime: 2600, sessionCookie: $foreignSessionId, server: [
            'REMOTE_ADDR' => '198.51.100.7',
        ]));

        $sessions = $this->storedSessions();
        $this->assertCount(2, $sessions);
        $this->assertSame(2000, $sessions[0]['time'], 'The session of the other address stays untouched.');
        $this->assertSame((string) $sessions[1]['sid'], $this->cookies[1]['value']);
        $this->assertNotSame($foreignSessionId, $this->cookies[1]['value']);
        $this->assertStringContainsString(
            '0;old_session;' . $foreignSessionId . ';198.51.100.0;',
            $this->trackingLog(),
        );
    }

    public function testExpiredSessionCookieStartsANewSession(): void
    {
        $this->track($this->createRequest(requestTime: 2000));
        $expiredSessionId = $this->cookies[0]['value'];

        $this->track($this->createRequest(requestTime: 2000 + 86_401, sessionCookie: $expiredSessionId));

        $this->assertCount(2, $this->storedSessions());
        $this->assertNotSame($expiredSessionId, $this->cookies[1]['value']);
    }

    public function testSessionIdFromTheQueryStringIsNotMistakenForTheCookie(): void
    {
        $request = $this->createRequest(requestTime: 2000);
        $request->query->set(UserSession::COOKIE_NAME_SESSION_ID, '4711');

        $this->track($request);

        $this->assertNotSame('4711', $this->cookies[0]['value']);
        $this->assertStringNotContainsString('4711;view;', $this->trackingLog());
    }

    public function testSessionCookieSentAsAnArrayStartsANewSession(): void
    {
        $request = $this->createRequest(requestTime: 2000);
        $request->cookies->set(UserSession::COOKIE_NAME_SESSION_ID, ['1']);

        $this->track($request);

        $this->assertCount(1, $this->storedSessions());
        $this->assertCount(1, $this->cookies);
    }

    public function testNoSessionCookieWhenTrackingIsDisabled(): void
    {
        $this->configuration->set('main.enableUserTracking', 'false');

        $this->track($this->createRequest(requestTime: 2000));

        $this->assertSame([], $this->cookies);
        $this->assertSame([], $this->storedSessions());
    }

    public function testNoSessionCookieForBots(): void
    {
        $this->configuration->set('main.botIgnoreList', 'crawler');

        $this->track($this->createRequest(requestTime: 2000, server: ['HTTP_USER_AGENT' => 'crawler/1.0']));

        $this->assertSame([], $this->cookies);
        $this->assertSame([], $this->storedSessions());
    }

    /**
     * @param array<string, string> $server
     */
    private function createRequest(int $requestTime, ?string $sessionCookie = null, array $server = []): Request
    {
        return Request::create(
            '/faq/index.php',
            'GET',
            [],
            $sessionCookie === null ? [] : [UserSession::COOKIE_NAME_SESSION_ID => $sessionCookie],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.5',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REQUEST_TIME' => $requestTime,
                'SCRIPT_NAME' => '/faq/index.php',
                ...$server,
            ],
        );
    }

    private function track(Request $request): UserSession
    {
        $session = $this->createSession($request);
        $session->userTracking('view', 1);

        return $session;
    }

    /**
     * Every request gets its own UserSession, as the service container hands out a new one per request.
     */
    private function createSession(Request $request): UserSession
    {
        $network = $this->createStub(Network::class);
        $network->method('isBanned')->willReturn(false);

        return new UserSession(
            $this->configuration,
            $request,
            static fn(Configuration $configuration): Network => $network,
            function (string $name, string $value, array $options): void {
                $this->cookies[] = ['name' => $name, 'value' => $value, 'options' => $options];
            },
            $this->trackingDirectory,
        );
    }

    /**
     * @return list<array{sid: int, time: int}>
     */
    private function storedSessions(): array
    {
        $database = $this->configuration->getDb();
        $result = $database->query('SELECT sid, time FROM faqsessions ORDER BY sid');

        $sessions = [];
        while ($row = $database->fetchObject($result)) {
            $sessions[] = ['sid' => (int) $row->sid, 'time' => (int) $row->time];
        }

        return $sessions;
    }

    private function trackingLog(): string
    {
        return implode('', array_map(file_get_contents(...), glob($this->trackingDirectory . '/tracking*') ?: []));
    }
}
