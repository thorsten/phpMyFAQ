<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SignatureCounter::class)]
#[UsesClass(Exception::class)]
final class SignatureCounterTest extends TestCase
{
    public function testReadsTheBigEndianCounterBehindTheHashAndTheFlags(): void
    {
        $authenticatorData = str_repeat('h', 32) . chr(0x01) . "\x00\x00\x01\x02" . 'attested credential data';

        self::assertSame(258, SignatureCounter::fromAuthenticatorData($authenticatorData)->value);
    }

    public function testReadsTheLargestCounterAsAPositiveNumber(): void
    {
        $authenticatorData = str_repeat('h', 32) . chr(0x01) . "\xff\xff\xff\xff";

        self::assertSame(4_294_967_295, SignatureCounter::fromAuthenticatorData($authenticatorData)->value);
    }

    public function testRejectsAuthenticatorDataWithoutACompleteCounter(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Authenticator data is too short to carry a signature counter');

        SignatureCounter::fromAuthenticatorData(str_repeat('h', 32) . chr(0x01) . "\x00\x00\x01");
    }

    /**
     * @return iterable<string, array{int, int, bool}>
     */
    public static function counterProvider(): iterable
    {
        yield 'authenticator that never counts' => [0, 0, true];
        yield 'first counted assertion' => [0, 1, true];
        yield 'next assertion' => [41, 42, true];
        yield 'assertions skipped elsewhere' => [41, 900, true];
        yield 'counter stands still' => [41, 41, false];
        yield 'counter goes back' => [41, 7, false];
        yield 'counter falls back to zero' => [41, 0, false];
    }

    #[DataProvider('counterProvider')]
    public function testFollowsOnlyAStoredCounterItExceeds(int $stored, int $received, bool $expected): void
    {
        $counter = SignatureCounter::fromAuthenticatorData(str_repeat('h', 33) . pack('N', $received));

        self::assertSame($expected, $counter->follows($stored));
    }
}
