<?php

namespace phpMyFAQ\User;

use Closure;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Network;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class TrackingTest extends TestCase
{
    private DatabaseDriver $database;

    private Configuration $configuration;

    private string $trackingDirectory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->resetTrackingSingleton();

        $this->database = $this->createMock(DatabaseDriver::class);
        $this->configuration = $this->createConfiguration();
        $this->trackingDirectory = sys_get_temp_dir() . '/pmf-tracking-' . uniqid('', true);
        mkdir($this->trackingDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->resetTrackingSingleton();

        if (is_dir($this->trackingDirectory)) {
            array_map('unlink', glob($this->trackingDirectory . '/*') ?: []);
            rmdir($this->trackingDirectory);
        }
    }

    public function testGetRemoteAddressUsesForwardedHeaderForLocalRequest(): void
    {
        $tracking = $this->createTracking(
            Request::create(
                '/',
                'GET',
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                    'HTTP_X_FORWARDED_FOR' => '198.51.100.24',
                ],
            ),
            $this->createMock(UserSession::class),
        );

        $this->assertSame('198.51.100.24', $tracking->getRemoteAddress());
    }

    public function testLogReturnsFalseWhenTrackingIsDisabled(): void
    {
        $this->configuration = $this->createConfiguration([
            'main.enableUserTracking' => false,
        ]);

        $tracking = $this->createTracking(Request::create('/'), $this->createMock(UserSession::class));

        $this->database->expects($this->never())->method('query');

        $this->assertFalse($tracking->log('view'));
    }

    /**
     * @throws Exception
     */
    public function testLogSkipsDatabaseWriteForIgnoredBots(): void
    {
        $this->configuration = $this->createConfiguration([
            'main.botIgnoreList' => 'Googlebot',
        ]);

        $tracking = $this->createTracking(
            Request::create(
                '/',
                'GET',
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => '198.51.100.24',
                    'HTTP_USER_AGENT' => 'Googlebot/2.1',
                    'REQUEST_TIME' => 1234567890,
                ],
            ),
            $this->createMock(UserSession::class),
        );

        $this->database->expects($this->never())->method('nextId');
        $this->database->expects($this->never())->method('query');

        $this->assertTrue($tracking->log('view'));
    }

    /**
     * @throws Exception
     */
    public function testLogSkipsDatabaseWriteForBannedAddress(): void
    {
        $network = $this->createMock(Network::class);
        $network->expects($this->once())->method('isBanned')->with('203.0.113.0')->willReturn(true);

        $tracking = $this->createTracking(
            Request::create(
                '/',
                'GET',
                [],
                [],
                [],
                [
                    'REMOTE_ADDR' => '203.0.113.99',
                    'HTTP_USER_AGENT' => 'UnitTest/1.0',
                    'REQUEST_TIME' => 1234567890,
                ],
            ),
            $this->createMock(UserSession::class),
            fn(): Network => $network,
        );

        $this->database->expects($this->never())->method('nextId');
        $this->database->expects($this->never())->method('query');

        $this->assertTrue($tracking->log('view'));
    }

    /**
     * @throws Exception
     */
    public function testLogResetsCurrentSessionIdForOldSessionAction(): void
    {
        $this->configuration = $this->createConfiguration([
            'main.botIgnoreList' => 'UnitBot',
        ]);

        $request = Request::create(
            '/?sid=11&pmf-sid=22',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '198.51.100.24',
                'HTTP_USER_AGENT' => 'UnitBot/1.0',
                'REQUEST_TIME' => 1234567890,
            ],
        );

        $userSession = $this->createMock(UserSession::class);
        $userSession
            ->expects($this->exactly(2))
            ->method('setCurrentSessionId')
            ->with($this->logicalOr($this->equalTo(22), $this->equalTo(0)))
            ->willReturnSelf();

        $tracking = $this->createTracking($request, $userSession);

        $this->database->expects($this->never())->method('query');

        $this->assertTrue($tracking->log(SessionActionType::OLD_SESSION->value));
    }

    /**
     * @throws Exception
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testLogCreatesSessionAndWritesTrackingData(): void
    {
        $request = Request::create(
            '/?search=faq',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.99',
                'QUERY_STRING' => 'search=faq',
                'HTTP_REFERER' => 'https://example.org/list',
                'HTTP_USER_AGENT' => 'Unit;Test/1.0',
                'REQUEST_TIME' => 1234567890,
            ],
        );

        $userSession = new UserSession($this->configuration, $request, null, null, $this->trackingDirectory);
        $queries = [];

        $this->database
            ->expects($this->once())
            ->method('nextId')
            ->with($this->stringContains('faqsessions'), 'sid')
            ->willReturn(41);

        $this->database
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): string {
                $queries[] = $query;
                return 'result';
            });

        $tracking = $this->createTracking($request, $userSession);

        $this->assertTrue($tracking->log('view', 99));
        $this->assertContains(true, array_map(
            static fn(string $query): bool => (
                str_contains($query, 'INSERT INTO ')
                && str_contains($query, 'VALUES (41')
                && str_contains($query, "'203.0.113.99'")
                && str_contains($query, '1234567890')
            ),
            $queries,
        ));
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function createConfiguration(array $overrides = []): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $items = array_merge([
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => '__no_bot__',
            'security.bannedIPs' => '',
            'security.permLevel' => 'basic',
        ], $overrides);

        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $items[$item] ?? null);
        $configuration->method('getDb')->willReturn($this->database);
        $configuration->method('getLogger')->willReturn(new Logger('test'));

        return $configuration;
    }

    private function createTracking(
        Request $request,
        UserSession $userSession,
        ?Closure $networkFactory = null,
    ): Tracking {
        return Tracking::getInstance(
            $this->configuration,
            $request,
            $userSession,
            $networkFactory,
            $this->trackingDirectory,
        );
    }

    private function resetTrackingSingleton(): void
    {
        $reflection = new ReflectionClass(Tracking::class);
        $property = $reflection->getProperty('tracking');
        $property->setValue(null, null);
    }
}
