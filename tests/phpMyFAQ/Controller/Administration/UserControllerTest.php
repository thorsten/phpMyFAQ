<?php

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Throwable;

#[AllowMockObjectsWithoutExpectations]
class UserControllerTest extends TestCase
{
    /**
     * Builds a CurrentUser mock with USER_ADD/USER_EDIT/USER_DELETE granted.
     */
    private function buildActingUser(int $userId, bool $isSuperAdmin): CurrentUser
    {
        $perm = $this->createMock(PermissionInterface::class);
        $perm->method('hasPermission')->willReturn(true);

        $user = $this->createMock(CurrentUser::class);
        $user->perm = $perm;
        $user->method('isLoggedIn')->willReturn(true);
        $user->method('getUserId')->willReturn($userId);
        $user->method('isSuperAdmin')->willReturn($isSuperAdmin);

        return $user;
    }

    /**
     * Builds a controller whose 'phpmyfaq.user' service is a real User backed by a
     * query-recording database stub, so the enumeration SQL can be inspected.
     *
     * @param string[] $executed
     */
    private function buildController(CurrentUser $actingUser, array &$executed): UserController
    {
        $controller = (new ReflectionClass(UserController::class))->newInstanceWithoutConstructor();

        $database = $this->createStub(Sqlite3::class);
        $database
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$executed): bool {
                $executed[] = $query;
                return true;
            });
        $database->method('numRows')->willReturn(0);
        $database->method('fetchArray')->willReturn(null);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($database);
        $configuration->method('getDefaultUrl')->willReturn('https://faq.example.test/');
        $configuration
            ->method('get')
            ->willReturnMap([
                ['security.permLevel', 'basic'],
            ]);

        $user = new User($configuration);
        $session = new Session(new MockArraySessionStorage());

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($session, $user) {
                return match ($id) {
                    'session' => $session,
                    'phpmyfaq.user' => $user,
                    default => null,
                };
            });

        $reflection = new ReflectionClass(UserController::class);
        $parent = $reflection->getParentClass();
        while ($parent !== false && !$parent->hasProperty('currentUser')) {
            $parent = $parent->getParentClass();
        }

        $parent->getProperty('container')->setValue($controller, $container);
        $parent->getProperty('currentUser')->setValue($controller, $actingUser);
        $parent->getProperty('configuration')->setValue($controller, $configuration);

        return $controller;
    }

    /**
     * Runs list() far enough to capture the user enumeration query. Template rendering
     * needs the full admin container and is not under test here, so failures past the
     * enumeration step are tolerated.
     *
     * @param string[] $executed
     */
    private function runListAndGetEnumerationQuery(UserController $controller, array &$executed): string
    {
        try {
            $controller->list(new Request());
        } catch (Throwable) {
            // rendering is out of scope; the enumeration query has already been issued
        }

        foreach ($executed as $query) {
            if (str_contains($query, 'faquser') && str_contains($query, 'user_id')) {
                return $query;
            }
        }

        $this->fail('No user enumeration query was executed.');
    }

    /**
     * The server-rendered list must mirror the 403 guard on the user data API: a
     * non-SuperAdmin must not get SuperAdmin or protected rows with edit links (see #4782).
     */
    public function testListNonSuperAdminDoesNotEnumeratePrivilegedAccounts(): void
    {
        $executed = [];
        $controller = $this->buildController($this->buildActingUser(userId: 5, isSuperAdmin: false), $executed);

        $query = $this->runListAndGetEnumerationQuery($controller, $executed);

        $this->assertStringContainsString('COALESCE(is_superadmin, 0) = 0', $query);
        $this->assertStringContainsString("<> 'protected'", $query);
    }

    public function testListSuperAdminEnumeratesAllAccounts(): void
    {
        $executed = [];
        $controller = $this->buildController($this->buildActingUser(userId: 1, isSuperAdmin: true), $executed);

        $query = $this->runListAndGetEnumerationQuery($controller, $executed);

        $this->assertStringNotContainsString('is_superadmin', $query);
        $this->assertStringNotContainsString('protected', $query);
    }
}
