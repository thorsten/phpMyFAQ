<?php

namespace phpMyFAQ\Template;

use PHPUnit\Framework\TestCase;
use Twig\TemplateWrapper;

class TwigWrapperTest extends TestCase
{
    private TwigWrapper $twigWrapper;

    /**
     */
    protected function setUp(): void
    {
        $this->twigWrapper = new TwigWrapper(PMF_TEST_DIR . '/assets/templates');
    }

    /**
     * @throws TemplateException
     */
    public function testLoadTemplate(): void
    {
        $templateFile = 'template.twig';

        $result = $this->twigWrapper->loadTemplate($templateFile);

        $this->assertInstanceOf(TemplateWrapper::class, $result);
    }
}
