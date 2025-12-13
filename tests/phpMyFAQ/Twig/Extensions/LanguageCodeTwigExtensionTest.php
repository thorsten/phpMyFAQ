<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class LanguageCodeTwigExtensionTest extends TestCase
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function testGetFromLanguageCodeFilter(): void
    {
        $loader = new ArrayLoader([
            'index' => "{{ 'en' | getFromLanguageCode }}",
        ]);
        $twig = new Environment($loader);
        $twig->addExtension(new AttributeExtension(LanguageCodeTwigExtension::class));

        $output = $twig->render('index');
        $this->assertSame('English', $output);
    }
}
