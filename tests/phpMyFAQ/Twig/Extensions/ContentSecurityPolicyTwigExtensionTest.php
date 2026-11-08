<?php

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use phpMyFAQ\Http\ContentSecurityPolicyNonce;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\AttributeExtension;
use Twig\Loader\ArrayLoader;

#[CoversClass(ContentSecurityPolicyTwigExtension::class)]
#[UsesClass(ContentSecurityPolicyNonce::class)]
final class ContentSecurityPolicyTwigExtensionTest extends TestCase
{
    protected function tearDown(): void
    {
        ContentSecurityPolicyNonce::reset();
    }

    public function testTheFunctionReturnsTheNonceOfTheCurrentRequest(): void
    {
        $this->assertSame(ContentSecurityPolicyNonce::get(), ContentSecurityPolicyTwigExtension::cspNonce());
    }

    public function testATemplateRendersTheNonceIntoTheScriptElement(): void
    {
        $environment = new Environment(new ArrayLoader([
            'page' => '<script nonce="{{ cspNonce() }}">alert(1);</script>',
        ]));
        $environment->addExtension(new AttributeExtension(ContentSecurityPolicyTwigExtension::class));

        $html = $environment->render('page');

        $this->assertSame(sprintf('<script nonce="%s">alert(1);</script>', ContentSecurityPolicyNonce::get()), $html);
    }
}
