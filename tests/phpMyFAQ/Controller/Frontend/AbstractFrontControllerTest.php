<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use LogicException;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Seo;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\System;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractFrontController::class)]
#[UsesClass(AbstractController::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(Seo::class)]
#[UsesClass(SeoRepository::class)]
final class AbstractFrontControllerTest extends TestCase
{
    public function testSetContainerInitializesSystemAndSeo(): void
    {
        $system = new System();
        $configuration = $this->createConfiguration();
        $seo = new Seo($configuration);
        $controller = new AbstractFrontControllerTestStub();

        $controller->setContainer($this->createControllerContainer($configuration, $system, $seo));

        self::assertSame($system, $controller->getFaqSystemInstance());
        self::assertSame($seo, $controller->getSeoInstance());
    }

    public function testSetContainerThrowsWhenSystemServiceHasUnexpectedType(): void
    {
        $configuration = $this->createConfiguration();
        $seo = new Seo($configuration);
        $controller = new AbstractFrontControllerTestStub();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('System service not found in container.');
        $controller->setContainer($this->createControllerContainer($configuration, new \stdClass(), $seo));
    }

    public function testSetContainerThrowsWhenSeoServiceHasUnexpectedType(): void
    {
        $configuration = $this->createConfiguration();
        $controller = new AbstractFrontControllerTestStub();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Seo service not found in container.');
        $controller->setContainer($this->createControllerContainer($configuration, new System(), new \stdClass()));
    }

    private function createConfiguration(): Configuration
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getTemplateSet')->willReturn('default');
        $configuration
            ->method('get')
            ->willReturnMap([
                ['security.enableLoginOnly', false],
            ]);

        return $configuration;
    }

    private function createControllerContainer(
        Configuration $configuration,
        mixed $system,
        mixed $seo,
    ): ContainerInterface {
        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->method('isLoggedIn')->willReturn(false);

        $session = $this->createStub(SessionInterface::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(static function (string $id) use (
                $configuration,
                $currentUser,
                $session,
                $system,
                $seo,
            ) {
                return match ($id) {
                    'phpmyfaq.configuration' => $configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.system' => $system,
                    'phpmyfaq.seo' => $seo,
                    default => null,
                };
            });

        return $container;
    }
}

final class AbstractFrontControllerTestStub extends AbstractFrontController
{
    public function __construct()
    {
    }

    public function getFaqSystemInstance(): System
    {
        return $this->faqSystem;
    }

    public function getSeoInstance(): Seo
    {
        return $this->seo;
    }
}
