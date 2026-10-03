<?php

declare(strict_types=1);

namespace phpMyFAQ\Link\Strategy;

use phpMyFAQ\Link;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StrategyRegistry::class)]
final class StrategyRegistryTest extends TestCase
{
    private function createStrategy(string $result): StrategyInterface
    {
        return new class ($result) implements StrategyInterface {
            public function __construct(
                private readonly string $result,
            ) {
            }

            public function build(array $params, Link $link): string
            {
                return $this->result;
            }
        };
    }

    public function testEmptyRegistry(): void
    {
        $registry = new StrategyRegistry();

        $this->assertSame([], $registry->list());
        $this->assertFalse($registry->has('show'));
        $this->assertNull($registry->get('show'));
    }

    public function testNullInitialBehavesLikeEmpty(): void
    {
        $this->assertSame([], new StrategyRegistry(null)->list());
    }

    public function testInitialStrategiesAreRegisteredInOrder(): void
    {
        $show = $this->createStrategy('show.html');
        $news = $this->createStrategy('news.html');

        $registry = new StrategyRegistry(['show' => $show, 'news' => $news]);

        $this->assertSame(['show', 'news'], $registry->list());
        $this->assertTrue($registry->has('show'));
        $this->assertSame($show, $registry->get('show'));
        $this->assertSame($news, $registry->get('news'));
    }

    public function testRegisterAddsStrategy(): void
    {
        $registry = new StrategyRegistry();
        $strategy = $this->createStrategy('faq.html');

        $registry->register('faq', $strategy);

        $this->assertTrue($registry->has('faq'));
        $this->assertSame($strategy, $registry->get('faq'));
        $this->assertSame(['faq'], $registry->list());
    }

    public function testRegisterOverridesExistingStrategyWithoutDuplicatingAction(): void
    {
        $original = $this->createStrategy('original.html');
        $replacement = $this->createStrategy('replacement.html');
        $registry = new StrategyRegistry(['show' => $original]);

        $registry->register('show', $replacement);

        $this->assertSame($replacement, $registry->get('show'));
        $this->assertSame(['show'], $registry->list());
    }
}
