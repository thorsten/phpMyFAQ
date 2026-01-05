<?php

namespace phpMyFAQ\Twig\Extensions;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;

#[AllowMockObjectsWithoutExpectations]
class TitleSlugifierTwigExtensionTest extends TestCase
{
    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[DataProvider('slugifyProvider')]
    public function testSlugifyFilter(string $input, string $expected): void
    {
        $loader = new ArrayLoader([
            'index' => '{{ title|slugify }}',
        ]);
        $twig = new Environment($loader);
        $twig->addExtension(new AttributeExtension(TitleSlugifierTwigExtension::class));

        $output = $twig->render('index', ['title' => $input]);
        $this->assertSame($expected, $output);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function slugifyProvider(): array
    {
        return [
            'simple title' => [
                'input' => 'Hello World',
                'expected' => 'hello-world',
            ],
            'title with umlauts' => [
                'input' => 'Über uns',
                'expected' => 'ueber-uns',
            ],
            'title with punctuation' => [
                'input' => 'What is PHP?',
                'expected' => 'what-is-php',
            ],
            'title with apostrophe' => [
                'input' => "It's a test",
                'expected' => 'it_s-a-test',
            ],
            'title with multiple spaces' => [
                'input' => 'Multiple   spaces   here',
                'expected' => 'multiple-spaces-here',
            ],
            'title with dashes' => [
                'input' => 'HD-Ready TV',
                'expected' => 'hd_ready-tv',
            ],
        ];
    }
}
