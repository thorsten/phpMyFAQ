<?php

namespace phpMyFAQ\User;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\SessionActionType;
use phpMyFAQ\Network;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class UserSessionTest extends TestCase
{
    private string $trackingDirectory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->trackingDirectory = sys_get_temp_dir() . '/phpmyfaq-user-session-' . uniqid('', true);
        mkdir($this->trackingDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->trackingDirectory . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->trackingDirectory);
        parent::tearDown();
    }

    public function testGetAndSetCurrentSessionId(): void
    {
        $session = new UserSession($this->createStub(Configuration::class));

        $this->assertNull($session->getCurrentSessionId());
        $this->assertSame($session, $session->setCurrentSessionId(42));
        $this->assertSame(42, $session->getCurrentSessionId());
    }

    public function testSetCurrentUserReturnsSelf(): void
    {
        $session = new UserSession($this->createStub(Configuration::class));
        $currentUser = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();

        $this->assertSame($session, $session->setCurrentUser($currentUser));
    }

    public function testCheckSessionIdLogsOldSessionWhenSessionWasNotFound(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('query')->willReturn('result');
        $db->expects($this->once())->method('numRows')->with('result')->willReturn(0);

        $configuration = $this->createConfiguration($db);
        $request = Request::create('/', 'GET', [], [], [], ['REQUEST_TIME' => 2000]);

        $session = $this
            ->getMockBuilder(UserSession::class)
            ->setConstructorArgs([$configuration, $request])
            ->onlyMethods(['userTracking'])
            ->getMock();

        $session->expects($this->once())->method('userTracking')->with(SessionActionType::OLD_SESSION->value, 15);

        $session->checkSessionId(15, '192.0.2.10');
    }

    public function testCheckSessionIdUpdatesStoredSessionWhenSessionExists(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->exactly(2))->method('query')->willReturnOnConsecutiveCalls('result', true);
        $db->expects($this->once())->method('numRows')->with('result')->willReturn(1);

        $configuration = $this->createConfiguration($db);
        $request = Request::create('/', 'GET', [], [], [], ['REQUEST_TIME' => 2000]);

        $currentUser = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();
        $currentUser->method('getUserId')->willReturn(7);

        $session = new UserSession($configuration, $request);
        $session->setCurrentUser($currentUser);
        $session->checkSessionId(15, '192.0.2.10');

        $this->assertSame(15, $session->getCurrentSessionId());
    }

    public function testUserTrackingReturnsEarlyWhenDisabled(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('query');

        $configuration = $this->createConfiguration($db, ['main.enableUserTracking' => false]);
        $session = new UserSession($configuration, Request::create('/'));

        $session->userTracking('view');
        $this->assertNull($session->getCurrentSessionId());
    }

    public function testUserTrackingSkipsBots(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('query');

        $configuration = $this->createConfiguration($db, [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => 'Googlebot',
        ]);
        $request = Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            [
                'HTTP_USER_AGENT' => 'Googlebot',
                'REQUEST_TIME' => 2000,
            ],
        );
        $network = $this
            ->getMockBuilder(Network::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBanned'])
            ->getMock();
        $network->method('isBanned')->willReturn(false);

        $session = new UserSession(
            $configuration,
            $request,
            static fn(Configuration $configuration): Network => $network,
            null,
            $this->trackingDirectory,
        );

        $session->userTracking('view');
        $this->assertNull($session->getCurrentSessionId());
    }

    public function testUserTrackingSkipsBannedIps(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('query');

        $configuration = $this->createConfiguration($db, [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => 'crawler',
        ]);
        $request = Request::create(
            '/',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.5',
                'REQUEST_TIME' => 2000,
            ],
        );
        $network = $this
            ->getMockBuilder(Network::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBanned'])
            ->getMock();
        $network->method('isBanned')->willReturn(true);

        $session = new UserSession(
            $configuration,
            $request,
            static fn(Configuration $configuration): Network => $network,
            null,
            $this->trackingDirectory,
        );

        $session->userTracking('view');
        $this->assertNull($session->getCurrentSessionId());
    }

    public function testUserTrackingCreatesSessionAndWritesTrackingFile(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->method('nextId')->willReturn(55);
        $db->expects($this->once())->method('query')->with($this->stringContains('INSERT INTO'))->willReturn(true);

        $configuration = $this->createConfiguration($db, [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => 'crawler',
        ]);
        $request = Request::create(
            '/index.php',
            'GET',
            [],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.5',
                'QUERY_STRING' => 'foo=bar',
                'HTTP_REFERER' => 'https://example.org/ref',
                'HTTP_USER_AGENT' => 'Mozilla/5.0',
                'REQUEST_TIME' => 2000,
                'SCRIPT_NAME' => '/index.php',
            ],
        );
        $network = $this
            ->getMockBuilder(Network::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBanned'])
            ->getMock();
        $network->method('isBanned')->willReturn(false);

        $currentUser = $this->getMockBuilder(CurrentUser::class)->disableOriginalConstructor()->getMock();
        $currentUser->method('getUserId')->willReturn(11);

        $session = new UserSession(
            $configuration,
            $request,
            static fn(Configuration $configuration): Network => $network,
            null,
            $this->trackingDirectory,
        );
        $session->setCurrentUser($currentUser);

        $session->userTracking('view', 99);

        $this->assertSame(55, $session->getCurrentSessionId());
        $files = glob($this->trackingDirectory . '/tracking*');
        $this->assertCount(1, $files);
        $contents = file_get_contents($files[0]);
        $this->assertStringContainsString('55;view;99;203.0.113.0;', $contents);
    }

    public function testUserTrackingResetsSessionIdForOldSessionAction(): void
    {
        $db = $this->createMock(DatabaseDriver::class);
        $db->expects($this->never())->method('nextId');
        $db->expects($this->never())->method('query');

        $configuration = $this->createConfiguration($db, [
            'main.enableUserTracking' => true,
            'main.botIgnoreList' => 'crawler',
        ]);
        $request = Request::create(
            '/',
            'GET',
            [UserSession::KEY_NAME_SESSION_ID => 123],
            [],
            [],
            [
                'REMOTE_ADDR' => '203.0.113.5',
                'REQUEST_TIME' => 2000,
                'SCRIPT_NAME' => '/index.php',
            ],
        );
        $network = $this
            ->getMockBuilder(Network::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isBanned'])
            ->getMock();
        $network->method('isBanned')->willReturn(false);

        $session = new UserSession(
            $configuration,
            $request,
            static fn(Configuration $configuration): Network => $network,
            null,
            $this->trackingDirectory,
        );

        $session->userTracking(SessionActionType::OLD_SESSION->value);

        $this->assertSame(0, $session->getCurrentSessionId());
        $files = glob($this->trackingDirectory . '/tracking*');
        $this->assertCount(1, $files);
    }

    public function testSetCookieDelegatesToInjectedCookieSetter(): void
    {
        $calls = [];
        $configuration = $this->createConfiguration(
            $this->createStub(DatabaseDriver::class),
            defaultUrl: 'https://faq.example.org',
        );
        $request = Request::create(
            '/admin/index.php',
            'GET',
            [],
            [],
            [],
            ['REQUEST_TIME' => 2000, 'HTTPS' => 'on', 'SCRIPT_NAME' => '/admin/index.php'],
        );

        $session = new UserSession($configuration, $request, null, static function (
            string $name,
            string $value,
            array $options,
        ) use (&$calls): void {
            $calls[] = [$name, $value, $options];
        });

        $session->setCookie('pmf-sid', 77, 3600, false);

        $this->assertCount(1, $calls);
        $this->assertSame('pmf-sid', $calls[0][0]);
        $this->assertSame('77', $calls[0][1]);
        $this->assertSame('faq.example.org', $calls[0][2]['domain']);
        $this->assertSame('', $calls[0][2]['samesite']);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function createConfiguration(
        DatabaseDriver $db,
        array $values = [],
        string $defaultUrl = 'https://localhost',
    ): Configuration {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($db);
        $configuration->method('getDefaultUrl')->willReturn($defaultUrl);
        $configuration->method('getLogger')->willReturn(new Logger('test'));
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => $values[$item] ?? null);

        return $configuration;
    }
}
