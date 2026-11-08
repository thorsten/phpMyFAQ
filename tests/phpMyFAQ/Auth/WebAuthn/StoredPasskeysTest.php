<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use phpMyFAQ\Core\Json;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(StoredPasskeys::class)]
#[UsesClass(Json::class)]
final class StoredPasskeysTest extends TestCase
{
    private const string TWO_KEYS = '[{"key":"KEY-A","id":[1,2,3],"created":1700000000},{"key":"KEY-B","id":[255,254]}]';

    public function testEmptyStorageHoldsNoPasskeys(): void
    {
        self::assertSame(0, StoredPasskeys::fromJson('')->count());
        self::assertSame(0, StoredPasskeys::fromJson('0')->count());
        self::assertSame(0, StoredPasskeys::fromJson('[]')->count());
        self::assertSame(0, StoredPasskeys::fromJson('not json')->count());
    }

    public function testSummariesExposeTheCredentialIdAsBase64UrlAndTheRegistrationTime(): void
    {
        $summaries = StoredPasskeys::fromJson(self::TWO_KEYS)->summaries();

        self::assertSame(
            [
                ['id' => 'AQID', 'created' => 1700000000],
                ['id' => '__4', 'created' => null],
            ],
            $summaries,
        );
    }

    public function testSummariesSkipEntriesThatAreNotObjects(): void
    {
        $summaries = StoredPasskeys::fromJson('[{"key":"K","id":[1]}, "garbage", 42]')->summaries();

        self::assertCount(1, $summaries);
        self::assertSame('AQ', $summaries[0]['id']);
    }

    public function testKeysWithoutAUsableIdGetAnEmptyCredentialId(): void
    {
        $summaries = StoredPasskeys::fromJson('[{"key":"K","id":"not a list"},{"key":"L","id":[1,"x"]}]')->summaries();

        self::assertSame('', $summaries[0]['id']);
        self::assertSame('', $summaries[1]['id']);
    }

    public function testHasFindsAStoredCredentialId(): void
    {
        $passkeys = StoredPasskeys::fromJson(self::TWO_KEYS);

        self::assertTrue($passkeys->has('AQID'));
        self::assertTrue($passkeys->has('__4'));
        self::assertFalse($passkeys->has('AQIE'));
    }

    public function testWithoutRemovesOnlyTheNamedPasskeyAndKeepsTheKeyMaterial(): void
    {
        $remaining = StoredPasskeys::fromJson(self::TWO_KEYS)->without('AQID');

        self::assertSame(1, $remaining->count());
        self::assertSame('[{"key":"KEY-B","id":[255,254]}]', $remaining->toJson());
    }

    public function testWithoutAnUnknownIdChangesNothing(): void
    {
        $passkeys = StoredPasskeys::fromJson(self::TWO_KEYS);

        self::assertSame($passkeys->toJson(), $passkeys->without('nope')->toJson());
    }

    public function testRemovingTheLastPasskeySerialisesToTheEmptyStorageValue(): void
    {
        self::assertSame('', StoredPasskeys::fromJson('[{"key":"K","id":[1]}]')->without('AQ')->toJson());
    }
}
