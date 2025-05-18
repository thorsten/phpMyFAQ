<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;

class FormatBytesTwigExtensionTest extends TestCase
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testFormatBytesFilter(): void
    {
        $loader = new ArrayLoader([
            'index' => '{{ 1536|formatBytes }}',
        ]);
        $twig = new Environment($loader);
        $twig->addExtension(new AttributeExtension(FormatBytesTwigExtension::class));

        $output = $twig->render('index');
        $this->assertSame('1.5 KB', $output);
    }
}
