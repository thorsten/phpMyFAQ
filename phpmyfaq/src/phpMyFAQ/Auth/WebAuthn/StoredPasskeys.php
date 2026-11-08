<?php

/**
 * The passkeys stored for one account, as kept in faquser.webauthnkeys.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use phpMyFAQ\Core\Json;
use stdClass;

/**
 * Reads the JSON list AuthWebAuthn::register() writes, so a passkey can be listed and removed
 * without touching the key material. The credential ID is exposed as base64url, the form the
 * browser uses for it, so it can travel through a form or a URL.
 */
final readonly class StoredPasskeys
{
    /**
     * @param list<stdClass> $keys
     */
    private function __construct(
        private array $keys,
    ) {
    }

    public static function fromJson(string $json): self
    {
        if (in_array($json, ['', '0'], strict: true)) {
            return new self([]);
        }

        $keys = Json::decodeList($json) ?? [];

        return new self(array_values(array_filter($keys, static fn(mixed $key): bool => $key instanceof stdClass)));
    }

    public function count(): int
    {
        return count($this->keys);
    }

    /**
     * One entry per passkey: the credential ID and, for keys registered since the timestamp was
     * introduced, the time of registration.
     *
     * @return list<array{id: string, created: int|null}>
     */
    public function summaries(): array
    {
        $summaries = [];
        foreach ($this->keys as $key) {
            $summaries[] = [
                'id' => $this->credentialId($key),
                'created' => is_int($key->created ?? null) ? $key->created : null,
            ];
        }

        return $summaries;
    }

    public function has(string $credentialId): bool
    {
        foreach ($this->keys as $key) {
            if (hash_equals($this->credentialId($key), $credentialId)) {
                return true;
            }
        }

        return false;
    }

    public function without(string $credentialId): self
    {
        return new self(array_values(array_filter(
            $this->keys,
            fn(stdClass $key): bool => !hash_equals($this->credentialId($key), $credentialId),
        )));
    }

    public function toJson(): string
    {
        if ($this->keys === []) {
            return '';
        }

        return (string) json_encode($this->keys);
    }

    private function credentialId(stdClass $key): string
    {
        if (!is_array($key->id ?? null)) {
            return '';
        }

        $bytes = implode('', array_map(static fn(mixed $byte): string => is_int($byte)
            ? chr($byte & 0xff)
            : '', $key->id));
        // A non-integer entry contributed nothing; such an ID is unusable.
        if (strlen($bytes) !== count($key->id)) {
            return '';
        }

        return rtrim(strtr(base64_encode($bytes), from: '+/', to: '-_'), characters: '=');
    }
}
