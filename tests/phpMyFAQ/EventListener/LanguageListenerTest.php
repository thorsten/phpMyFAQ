<?php

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Strings;
use phpMyFAQ\Strings\AbstractString;
use phpMyFAQ\Strings\Mbstring;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(LanguageListener::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Language::class)]
#[UsesClass(LanguageCodes::class)]
#[UsesClass(Translation::class)]
#[UsesClass(Strings::class)]
#[UsesClass(AbstractString::class)]
#[UsesClass(Mbstring::class)]
final class LanguageListenerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Translation::resetInstance();
        Language::$language = '';

        $reflection = new \ReflectionClass(Strings::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }

    public function testOnKernelRequestSkipsSubRequests(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->never())->method('has');

        $listener = new LanguageListener($container);
        $listener->onKernelRequest($this->createEvent(HttpKernelInterface::SUB_REQUEST));

        self::assertSame('', Translation::getInstance()->getCurrentLanguage());
    }

    public function testOnKernelRequestFallsBackToEnglishWhenServicesAreMissing(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $container
            ->method('has')
            ->willReturnMap([
                ['phpmyfaq.configuration', false],
                ['phpmyfaq.language',      false],
            ]);

        $listener = new LanguageListener($container);
        $listener->onKernelRequest($this->createEvent());

        self::assertSame('en', Translation::getInstance()->getCurrentLanguage());
    }

    public function testOnKernelRequestUsesLanguageDetectionWhenEnabled(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $configuration = $this->createMock(Configuration::class);
        $language = $this->createMock(Language::class);

        $container
            ->method('has')
            ->willReturnMap([
                ['phpmyfaq.configuration', true],
                ['phpmyfaq.language',      true],
            ]);
        $container
            ->method('get')
            ->willReturnMap([
                ['phpmyfaq.configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $configuration],
                ['phpmyfaq.language',      ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $language],
            ]);

        $configuration->expects($this->once())->method('setContainer')->with($container);
        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', true],
                ['main.language',          'de'],
            ]);
        $configuration->expects($this->once())->method('setLanguage')->with($language);

        $language->expects($this->once())->method('setLanguageWithDetection')->with('de')->willReturn('de');
        $language->expects($this->never())->method('setLanguageFromConfiguration');

        $listener = new LanguageListener($container);
        $listener->onKernelRequest($this->createEvent());

        self::assertSame('de', Translation::getInstance()->getCurrentLanguage());
    }

    public function testOnKernelRequestUsesConfiguredLanguageWhenDetectionDisabledAndInitializesOnlyOnce(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $configuration = $this->createMock(Configuration::class);
        $language = $this->createMock(Language::class);

        $container
            ->method('has')
            ->willReturnMap([
                ['phpmyfaq.configuration', true],
                ['phpmyfaq.language',      true],
            ]);
        $container
            ->method('get')
            ->willReturnMap([
                ['phpmyfaq.configuration', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $configuration],
                ['phpmyfaq.language',      ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $language],
            ]);

        $configuration->expects($this->once())->method('setContainer')->with($container);
        $configuration
            ->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['main.languageDetection', false],
                ['main.language',          'fr'],
            ]);
        $configuration->expects($this->once())->method('setLanguage')->with($language);

        $language->expects($this->once())->method('setLanguageFromConfiguration')->with('fr')->willReturn('fr');
        $language->expects($this->never())->method('setLanguageWithDetection');

        $listener = new LanguageListener($container);
        $listener->onKernelRequest($this->createEvent());
        $listener->onKernelRequest($this->createEvent());

        self::assertSame('fr', Translation::getInstance()->getCurrentLanguage());
    }

    private function createEvent(int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), Request::create('/'), $requestType);
    }
}
