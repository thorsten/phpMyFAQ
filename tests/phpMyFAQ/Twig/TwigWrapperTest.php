<?php

namespace phpMyFAQ\Twig;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;
use Twig\Extension\ExtensionInterface;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigWrapperTest extends TestCase
{
    private TwigWrapper $twigWrapper;
    private string $templatePath = PMF_ROOT_DIR . '/assets/templates';

    /**
     */
    protected function setUp(): void
    {
        $this->twigWrapper = new TwigWrapper($this->templatePath);
    }

    public function testSetSetup(): void
    {
        $this->twigWrapper->setSetup(true);
        $reflection = new \ReflectionClass($this->twigWrapper);
        $property = $reflection->getProperty('isSetup');
        $property->setAccessible(true);
        $this->assertTrue($property->getValue($this->twigWrapper));

        $this->twigWrapper->setSetup(false);
        $this->assertFalse($property->getValue($this->twigWrapper));
    }

    /**
     * @throws TemplateException|Exception
     */
    public function testLoadTemplate(): void
    {
        $templateFile = 'index.twig';

        $result = $this->twigWrapper->loadTemplate($templateFile);

        $this->assertInstanceOf(TemplateWrapper::class, $result);
    }

    public function testAddExtension(): void
    {
        $extensionMock = $this->createMock(ExtensionInterface::class);
        $this->twigWrapper->addExtension($extensionMock);

        $reflection = new \ReflectionClass($this->twigWrapper);
        $property = $reflection->getProperty('twigEnvironment');
        $property->setAccessible(true);
        $twigEnvironment = $property->getValue($this->twigWrapper);

        $this->assertTrue($twigEnvironment->hasExtension(get_class($extensionMock)));
    }

    public function testAddFunction(): void
    {
        $function = new TwigFunction('testFunction', function () {
            return 'test';
        });
        $this->twigWrapper->addFunction($function);

        $reflection = new \ReflectionClass($this->twigWrapper);
        $property = $reflection->getProperty('twigEnvironment');
        $property->setAccessible(true);
        $twigEnvironment = $property->getValue($this->twigWrapper);

        $this->assertInstanceOf(TwigFunction::class, $twigEnvironment->getFunction('testFunction'));
    }

    public function testGetExtension(): void
    {
        $extensionMock = $this->createMock(ExtensionInterface::class);
        $this->twigWrapper->addExtension($extensionMock);

        $this->assertInstanceOf(ExtensionInterface::class, $this->twigWrapper->getExtension(get_class($extensionMock)));
    }

    public function testAddFilter(): void
    {
        $filter = new TwigFilter('testFilter', function ($value) {
            return $value;
        });
        $this->twigWrapper->addFilter($filter);

        $reflection = new \ReflectionClass($this->twigWrapper);
        $property = $reflection->getProperty('twigEnvironment');
        $property->setAccessible(true);
        $twigEnvironment = $property->getValue($this->twigWrapper);

        $this->assertInstanceOf(TwigFilter::class, $twigEnvironment->getFilter('testFilter'));
    }

    public function testGetTemplateSetName(): void
    {
        $this->assertEquals('default', TwigWrapper::getTemplateSetName());
    }

    public function testSetTemplateSetName(): void
    {
        TwigWrapper::setTemplateSetName('newTemplateSet');
        $this->assertEquals('newTemplateSet', TwigWrapper::getTemplateSetName());
        TwigWrapper::setTemplateSetName();
    }
}
