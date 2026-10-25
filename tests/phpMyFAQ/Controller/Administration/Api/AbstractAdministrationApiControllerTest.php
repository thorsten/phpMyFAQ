<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractAdministrationApiController::class)]
#[UsesClass(AbstractController::class)]
#[UsesClass(AttachmentFactory::class)]
#[UsesClass(TwigWrapper::class)]
final class AbstractAdministrationApiControllerTest extends TestCase
{
    public function testSetContainerInitializesAdminLog(): void
    {
        $adminLog = $this->createStub(AdminLog::class);
        $controller = new AbstractAdministrationApiControllerTestStub();

        $controller->setContainer($this->createControllerContainer($adminLog));

        self::assertSame($adminLog, $controller->getAdminLogInstance());
    }

    public function testSetContainerThrowsWhenAdminLogServiceIsMissing(): void
    {
        $controller = new AbstractAdministrationApiControllerTestStub();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('AdminLog service not found in container.');

        $controller->setContainer($this->createControllerContainer(null));
    }

    public function testRestrictGroupRightToScopeFailsWhenLanguageRestrictionCannotBeStored(): void
    {
        $permission = $this->createStub(MediumPermission::class);
        $permission->method('setLanguageRestrictions')->willReturn(false);
        $permission->method('setCategoryRestrictions')->willReturn(true);

        $controller = new AbstractAdministrationApiControllerTestStub();

        self::assertFalse($controller->restrictScope($permission, 1, 2, ['de'], [3]));
        self::assertFalse($controller->restrictScope($permission, 1, 2, [], null));
    }

    public function testRestrictGroupRightToScopeFailsWhenCategoryRestrictionCannotBeStored(): void
    {
        $permission = $this->createStub(MediumPermission::class);
        $permission->method('setLanguageRestrictions')->willReturn(true);
        $permission->method('setCategoryRestrictions')->willReturn(false);

        $controller = new AbstractAdministrationApiControllerTestStub();

        self::assertFalse($controller->restrictScope($permission, 1, 2, ['de'], ['3']));
        self::assertFalse($controller->restrictScope($permission, 1, 2, null, []));
    }

    public function testRestrictGroupRightToScopeSucceedsWhenBothRestrictionsAreStored(): void
    {
        $permission = $this->createStub(MediumPermission::class);
        $permission->method('setLanguageRestrictions')->willReturn(true);
        $permission->method('setCategoryRestrictions')->willReturn(true);

        $controller = new AbstractAdministrationApiControllerTestStub();

        self::assertTrue($controller->restrictScope($permission, 1, 2, ['de'], ['3']));
        self::assertTrue($controller->restrictScope($permission, 1, 2, null, null));
    }

    private function createControllerContainer(?AdminLog $adminLog): ContainerInterface
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTemplateSet')->willReturn('default');
        $configuration
            ->method('get')
            ->willReturnMap([
                ['security.enableLoginOnly', false],
            ]);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $session = $this->createStub(FlashBagAwareSessionInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use ($adminLog, $configuration, $currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }
}

final class AbstractAdministrationApiControllerTestStub extends AbstractAdministrationApiController
{
    public function __construct()
    {
    }

    public function getAdminLogInstance(): ?AdminLog
    {
        return $this->adminLog;
    }

    public function restrictScope(
        MediumPermission $permission,
        int $groupId,
        int $rightId,
        ?array $ownLanguages,
        ?array $ownCategories,
    ): bool {
        return $this->restrictGroupRightToScope($permission, $groupId, $rightId, $ownLanguages, $ownCategories);
    }
}
