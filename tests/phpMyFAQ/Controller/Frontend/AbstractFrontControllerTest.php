<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use DateTime;
use LogicException;
use phpMyFAQ\Attachment\AttachmentFactory;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\CustomPage;
use phpMyFAQ\Entity\CustomPageEntity;
use phpMyFAQ\Language;
use phpMyFAQ\Seo;
use phpMyFAQ\Seo\SeoRepository;
use phpMyFAQ\System;
use phpMyFAQ\TestDatabaseTrait;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AbstractFrontController::class)]
#[UsesClass(AbstractController::class)]
#[UsesClass(AttachmentFactory::class)]
#[UsesClass(\phpMyFAQ\Twig\TwigWrapper::class)]
#[UsesClass(Seo::class)]
#[UsesClass(SeoRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class AbstractFrontControllerTest extends TestCase
{
    use TestDatabaseTrait;

    public function testHandleStaticPageRedirectPointsToAnActiveCustomPage(): void
    {
        $configuration = $this->createDatabaseConfiguration();
        $this->createCustomPage($configuration, 'imprint-page', active: true);
        self::assertTrue($configuration->set('main.imprintURL', 'page:imprint-page'));

        $controller = new AbstractFrontControllerTestStub();
        $controller->setContainer(
            $this->createControllerContainer($configuration, new System(), new Seo($configuration)),
        );

        $response = $controller->redirectStaticPage('main.imprintURL');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame($configuration->getDefaultUrl() . 'page/imprint-page.html', $response->getTargetUrl());
    }

    public function testHandleStaticPageRedirectIgnoresAnInactiveCustomPage(): void
    {
        $configuration = $this->createDatabaseConfiguration();
        $this->createCustomPage($configuration, 'hidden-page', active: false);
        self::assertTrue($configuration->set('main.imprintURL', 'page:hidden-page'));

        $controller = new AbstractFrontControllerTestStub();
        $controller->setContainer(
            $this->createControllerContainer($configuration, new System(), new Seo($configuration)),
        );

        $response = $controller->redirectStaticPage('main.imprintURL');

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('page:hidden-page', $response->getTargetUrl());
    }

    private function createDatabaseConfiguration(): Configuration
    {
        $configuration = $this->createTestConfiguration();
        $language = new Language($configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        return $configuration;
    }

    private function createCustomPage(Configuration $configuration, string $slug, bool $active): void
    {
        $page = new CustomPageEntity();
        $page
            ->setLanguage('en')
            ->setPageTitle('Static page ' . $slug)
            ->setSlug($slug)
            ->setContent('<p>Content</p>')
            ->setAuthorName('Author')
            ->setAuthorEmail('author@example.org')
            ->setActive($active)
            ->setCreated(new DateTime());

        self::assertGreaterThan(0, new CustomPage($configuration)->create($page));
    }

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

        $session = $this->createStub(FlashBagAwareSessionInterface::class);

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

    public function redirectStaticPage(string $configKey): Response
    {
        return $this->handleStaticPageRedirect($configKey);
    }
}
