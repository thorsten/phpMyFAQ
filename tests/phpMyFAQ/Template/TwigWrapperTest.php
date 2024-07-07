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
        $this->twigWrapper = new TwigWrapper(PMF_ROOT_DIR . '/assets/templates/default');
    }

    /**
     * @throws TemplateException
     */
    public function testLoadTemplate(): void
    {
        $templateFile = 'index.twig';

        $result = $this->twigWrapper->loadTemplate($templateFile);

        $this->assertInstanceOf(TemplateWrapper::class, $result);
    }
}
