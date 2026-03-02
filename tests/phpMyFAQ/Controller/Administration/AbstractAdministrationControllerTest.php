<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Twig\TwigWrapper;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractAdministrationController::class)]
#[UsesClass(AbstractController::class)]
#[UsesClass(TwigWrapper::class)]
final class AbstractAdministrationControllerTest extends TestCase
{
    public function testSetContainerInitializesAdminLog(): void
    {
        $adminLog = $this->createStub(AdminLog::class);
        $controller = new AbstractAdministrationControllerTestStub();

        $controller->setContainer($this->createControllerContainer($adminLog));

        self::assertSame($adminLog, $controller->getAdminLogInstance());
    }

    private function createControllerContainer(AdminLog $adminLog): ContainerInterface
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

        $session = $this->createStub(SessionInterface::class);

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

final class AbstractAdministrationControllerTestStub extends AbstractAdministrationController
{
    public function __construct()
    {
    }

    public function getAdminLogInstance(): ?AdminLog
    {
        return $this->adminLog;
    }
}
