<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;

class IsoDateTwigExtensionTest extends TestCase
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testCreateIsoDateFilter(): void
    {
        $loader = new ArrayLoader([
            'index' => '{{ 202504011230 | createIsoDate }}',
        ]);
        $twig = new Environment($loader);
        $twig->addExtension(new AttributeExtension(IsoDateTwigExtension::class));

        $output = $twig->render('index');
        $this->assertSame('2025-04-01 12:30', $output);
    }
}
