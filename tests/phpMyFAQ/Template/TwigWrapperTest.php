<?php

namespace phpMyFAQ\Template;

use phpMyFAQ\Core\Exception;
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
     * @throws TemplateException|Exception
     */
    public function testLoadTemplate(): void
    {
        $templateFile = 'index.twig';

        $result = $this->twigWrapper->loadTemplate($templateFile);

        $this->assertInstanceOf(TemplateWrapper::class, $result);
    }
}
