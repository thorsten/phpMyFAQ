<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Backup;
use phpMyFAQ\Administration\Faq;
use phpMyFAQ\Administration\LatestUsers;
use phpMyFAQ\Administration\Session as AdminSession;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class DashboardControllerTest extends TestCase
{
    /**
     * @param array<string, object> $services
     */
    private function buildController(
        CurrentUser $actingUser,
        Configuration $configuration,
        array $services = [],
    ): DashboardController {
        $controller = (new ReflectionClass(DashboardController::class))->newInstanceWithoutConstructor();

        $session = new Session(new MockArraySessionStorage());
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($session, $services): object {
                if ($id === 'session') {
                    return $session;
                }

                if (isset($services[$id])) {
                    return $services[$id];
                }

                throw new \RuntimeException(sprintf('Service "%s" must not be requested for this user.', $id));
            });

        $reflection = new ReflectionClass(DashboardController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('configuration')->setValue($controller, $configuration);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);

        return $controller;
    }

    /**
     * @param PermissionType[] $granted
     */
    private function userHolding(array $granted): CurrentUser
    {
        $grantedValues = array_map(static fn(PermissionType $type): string => $type->value, $granted);

        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturnCallback(static fn(int $userId, string $permission): bool => in_array(
            $permission,
            $grantedValues,
            strict: true,
        ));

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn(7);

        return $user;
    }

    /**
     * An authenticated user without any admin right (e.g. a plain front-end account that logged in
     * through the regular login form) may open the dashboard, but none of the permission-gated data
     * sources may be queried and the template must receive no unpublished FAQs, user identities,
     * counters or backup information.
     */
    public function testUserWithoutRightsGetsEmptyDashboard(): void
    {
        $database = $this->createMock(DatabaseDriver::class);
        $database->expects($this->never())->method('getTableStatus');

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('getInactiveFaqsData');

        $controller = $this->buildController($this->userHolding([]), $configuration, ['phpmyfaq.admin.faq' => $faq]);

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertFalse($widgets['hasPermissionViewStatistics']);
        $this->assertFalse($widgets['hasPermissionViewInactiveFaqs']);
        $this->assertFalse($widgets['hasPermissionViewLatestUsers']);
        $this->assertFalse($widgets['hasPermissionViewBackup']);
        $this->assertSame([], $widgets['adminDashboardInactiveFaqs']);
        $this->assertSame([], $widgets['adminDashboardLatestUsers']);
        $this->assertArrayNotHasKey('adminDashboardInfoNumVisits', $widgets);
        $this->assertArrayNotHasKey('adminDashboardInfoNumFaqs', $widgets);
        $this->assertArrayNotHasKey('adminDashboardInfoNumComments', $widgets);
        $this->assertArrayNotHasKey('adminDashboardInfoNumQuestions', $widgets);
        $this->assertArrayNotHasKey('adminDashboardInfoNumUser', $widgets);
        $this->assertArrayNotHasKey('adminDashboardInfoNumUsersOnline', $widgets);
        $this->assertArrayNotHasKey('lastBackupDate', $widgets);
        $this->assertArrayNotHasKey('isBackupOlderThan30Days', $widgets);
    }

    public function testInactiveFaqsRequireFaqEdit(): void
    {
        $inactive = [['question' => 'Unreleased draft', 'url' => 'https://localhost/admin/faq/edit/1/en']];

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('getInactiveFaqsData')->willReturn($inactive);

        $controller = $this->buildController(
            $this->userHolding([PermissionType::FAQ_EDIT]),
            $this->createMock(Configuration::class),
            ['phpmyfaq.admin.faq' => $faq],
        );

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertTrue($widgets['hasPermissionViewInactiveFaqs']);
        $this->assertSame($inactive, $widgets['adminDashboardInactiveFaqs']);
        $this->assertFalse($widgets['hasPermissionViewStatistics']);
        $this->assertFalse($widgets['hasPermissionViewLatestUsers']);
        $this->assertFalse($widgets['hasPermissionViewBackup']);
        $this->assertSame([], $widgets['adminDashboardLatestUsers']);
    }

    public function testLatestUsersRequireUserEdit(): void
    {
        $database = $this->createMock(DatabaseDriver::class);
        $database->expects($this->once())->method('query')->willReturn(true);
        $database->method('fetchArray')->willReturnOnConsecutiveCalls([
            'user_id' => 3,
            'login' => 'newest',
            'member_since' => '',
            'display_name' => 'Newest User',
        ], false);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $controller = $this->buildController($this->userHolding([PermissionType::USER_EDIT]), $configuration, [
            'phpmyfaq.admin.latest-users' => new LatestUsers($configuration),
        ]);

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertTrue($widgets['hasPermissionViewLatestUsers']);
        $this->assertCount(1, $widgets['adminDashboardLatestUsers']);
        $this->assertSame('newest', $widgets['adminDashboardLatestUsers'][0]['login']);
        $this->assertFalse($widgets['hasPermissionViewInactiveFaqs']);
        $this->assertSame([], $widgets['adminDashboardInactiveFaqs']);
    }

    public function testCountersRequireStatisticsViewLogs(): void
    {
        $database = $this->createMock(DatabaseDriver::class);
        $database
            ->expects($this->once())
            ->method('getTableStatus')
            ->willReturn([
                'faqdata' => 12,
                'faqcomments' => 3,
                'faqquestions' => 4,
                'faquser' => 6,
            ]);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $session = $this->createMock(AdminSession::class);
        $session->method('getNumberOfSessions')->willReturn(99);
        $session->method('getNumberOfOnlineUsers')->willReturn(2);

        $controller = $this->buildController(
            $this->userHolding([PermissionType::STATISTICS_VIEWLOGS]),
            $configuration,
            ['phpmyfaq.admin.session' => $session],
        );

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertTrue($widgets['hasPermissionViewStatistics']);
        $this->assertSame(99, $widgets['adminDashboardInfoNumVisits']);
        $this->assertSame(12, $widgets['adminDashboardInfoNumFaqs']);
        $this->assertSame(3, $widgets['adminDashboardInfoNumComments']);
        $this->assertSame(4, $widgets['adminDashboardInfoNumQuestions']);
        $this->assertSame(5, $widgets['adminDashboardInfoNumUser']);
        $this->assertSame(2, $widgets['adminDashboardInfoNumUsersOnline']);
        $this->assertFalse($widgets['hasPermissionViewInactiveFaqs']);
        $this->assertFalse($widgets['hasPermissionViewLatestUsers']);
    }

    public function testBackupInformationRequiresBackupRight(): void
    {
        $backup = $this->createMock(Backup::class);
        $backup
            ->expects($this->once())
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => '2026-09-01 10:00',
                'isBackupOlderThan30Days' => false,
            ]);

        $controller = $this->buildController(
            $this->userHolding([PermissionType::BACKUP]),
            $this->createMock(Configuration::class),
            ['phpmyfaq.admin.backup' => $backup],
        );

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertTrue($widgets['hasPermissionViewBackup']);
        $this->assertSame('2026-09-01 10:00', $widgets['lastBackupDate']);
        $this->assertFalse($widgets['isBackupOlderThan30Days']);
    }

    /**
     * Pins the exact right each widget is gated on: a user holding every right except the
     * withheld one must not receive that widget, which catches drift to a wrong or weaker check.
     *
     * @return array<string, array{PermissionType, string}>
     */
    public static function withheldRightProvider(): array
    {
        return [
            'statistics' => [PermissionType::STATISTICS_VIEWLOGS, 'hasPermissionViewStatistics'],
            'inactive FAQs' => [PermissionType::FAQ_EDIT, 'hasPermissionViewInactiveFaqs'],
            'latest users' => [PermissionType::USER_EDIT, 'hasPermissionViewLatestUsers'],
            'backup' => [PermissionType::BACKUP, 'hasPermissionViewBackup'],
        ];
    }

    #[DataProvider('withheldRightProvider')]
    public function testEachWidgetIsWithheldWithoutItsRight(PermissionType $withheld, string $flag): void
    {
        $granted = array_filter(
            [
                PermissionType::STATISTICS_VIEWLOGS,
                PermissionType::FAQ_EDIT,
                PermissionType::USER_EDIT,
                PermissionType::BACKUP,
            ],
            static fn(PermissionType $type): bool => $type !== $withheld,
        );

        $database = $this->createMock(DatabaseDriver::class);
        $database
            ->method('getTableStatus')
            ->willReturn([
                'faqdata' => 1,
                'faqcomments' => 1,
                'faqquestions' => 1,
                'faquser' => 2,
            ]);
        $database->method('query')->willReturn(true);
        $database->method('fetchArray')->willReturn(false);

        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getDb')->willReturn($database);

        $backup = $this->createMock(Backup::class);
        $backup
            ->method('getLastBackupInfo')
            ->willReturn([
                'lastBackupDate' => null,
                'isBackupOlderThan30Days' => true,
            ]);

        $controller = $this->buildController($this->userHolding($granted), $configuration, [
            'phpmyfaq.admin.session' => $this->createMock(AdminSession::class),
            'phpmyfaq.admin.faq' => $this->createMock(Faq::class),
            'phpmyfaq.admin.latest-users' => new LatestUsers($configuration),
            'phpmyfaq.admin.backup' => $backup,
        ]);

        $widgets = $controller->getPermissionGatedWidgets(7);

        $this->assertFalse($widgets[$flag]);
        foreach ([
            'hasPermissionViewStatistics',
            'hasPermissionViewInactiveFaqs',
            'hasPermissionViewLatestUsers',
            'hasPermissionViewBackup',
        ] as $other) {
            if ($other === $flag) {
                continue;
            }

            $this->assertTrue($widgets[$other], sprintf('%s must still be granted', $other));
        }
    }
}
