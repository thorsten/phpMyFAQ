<?php

declare(strict_types=1);

namespace phpMyFAQ\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContentSecurityPolicyNonce::class)]
final class ContentSecurityPolicyNonceTest extends TestCase
{
    protected function setUp(): void
    {
        ContentSecurityPolicyNonce::reset();
    }

    protected function tearDown(): void
    {
        ContentSecurityPolicyNonce::reset();
    }

    public function testTheSameNonceIsReturnedThroughoutARequest(): void
    {
        $this->assertSame(ContentSecurityPolicyNonce::get(), ContentSecurityPolicyNonce::get());
    }

    public function testResetStartsANewNonce(): void
    {
        $first = ContentSecurityPolicyNonce::get();

        ContentSecurityPolicyNonce::reset();

        $this->assertNotSame($first, ContentSecurityPolicyNonce::get());
    }

    public function testTheNonceIsBase64EncodedRandomnessOfTheRecommendedLength(): void
    {
        $nonce = ContentSecurityPolicyNonce::get();

        $this->assertMatchesRegularExpression('#^[A-Za-z0-9+/]+={0,2}$#', $nonce);
        $this->assertSame(16, strlen((string) base64_decode($nonce, strict: true)));
    }

    public function testTheNonceCannotTerminateTheAttributeItIsRenderedInto(): void
    {
        for ($run = 0; $run < 50; ++$run) {
            ContentSecurityPolicyNonce::reset();
            $nonce = ContentSecurityPolicyNonce::get();

            $this->assertSame($nonce, strip_tags($nonce));
            $this->assertStringNotContainsString('"', $nonce);
            $this->assertStringNotContainsString("'", $nonce);
            $this->assertStringNotContainsString('<', $nonce);
            $this->assertStringNotContainsString(' ', $nonce);
            $this->assertStringNotContainsString(';', $nonce);
        }
    }
}
