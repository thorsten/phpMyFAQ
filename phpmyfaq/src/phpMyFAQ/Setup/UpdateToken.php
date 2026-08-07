<?php

/**
 * The UpdateToken class manages the one-time secret that authorizes the update
 * wizard when no administrator session is available.
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
 * @since     2026-08-02
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use Random\RandomException;

/**
 * A major update leaves the installation in a state where a login can be impossible:
 * the new code already expects columns the old database does not have yet, so an
 * administrator upgrading from an older release cannot authenticate before the
 * migration has run. The update endpoints therefore accept a second proof of
 * authorization: a secret that is written to the configuration directory and can
 * only be read by someone with access to the file system of the server.
 *
 * The token file is a PHP file that exits immediately, so it stays unreadable over
 * HTTP even on servers without the shipped deny rules for the content directory.
 */
readonly class UpdateToken
{
    public const string TOKEN_FILENAME = 'update-token.php';

    /** Lifetime of a token in seconds. */
    public const int TOKEN_LIFETIME = 3600;

    private const string FILE_HEADER = '<?php exit; ?>';

    public function __construct(
        private string $configDir,
    ) {
    }

    public function getTokenFilePath(): string
    {
        return $this->configDir . DIRECTORY_SEPARATOR . self::TOKEN_FILENAME;
    }

    /**
     * Returns the current token and creates a new one if there is none or if the
     * existing one has expired.
     *
     * @throws Exception
     */
    public function getOrCreate(): string
    {
        $token = $this->read();
        if (is_string($token)) {
            return $token;
        }

        return $this->create();
    }

    /**
     * Returns true if the given token matches the stored, non-expired token.
     */
    public function isValid(#[\SensitiveParameter] ?string $token): bool
    {
        if (!is_string($token) || $token === '') {
            return false;
        }

        $storedToken = $this->read();

        return is_string($storedToken) && hash_equals($storedToken, $token);
    }

    /**
     * Removes the token file, e.g. once the update has been applied.
     */
    public function delete(): void
    {
        $tokenFilePath = $this->getTokenFilePath();
        if (is_file($tokenFilePath)) {
            unlink($tokenFilePath);
        }
    }

    /**
     * Creates and stores a new token.
     *
     * @throws Exception
     */
    private function create(): string
    {
        if (!is_dir($this->configDir) || !is_writable($this->configDir)) {
            throw new Exception(sprintf(
                'Cannot create the update token, the directory %s is not writable.',
                $this->configDir,
            ));
        }

        try {
            $token = bin2hex(random_bytes(16));
        } catch (RandomException $randomException) {
            throw new Exception('Cannot create the update token: ' . $randomException->getMessage());
        }

        $content = self::FILE_HEADER . PHP_EOL . (string) json_encode(['token' => $token, 'created' => time()]);

        if (file_put_contents($this->getTokenFilePath(), $content) === false) {
            throw new Exception(sprintf('Cannot write the update token to %s.', $this->getTokenFilePath()));
        }

        return $token;
    }

    /**
     * Returns the stored token, or null if there is none or if it has expired.
     */
    private function read(): ?string
    {
        $tokenFilePath = $this->getTokenFilePath();
        if (!is_file($tokenFilePath)) {
            return null;
        }

        $content = file_get_contents($tokenFilePath);
        if ($content === false || !str_starts_with($content, self::FILE_HEADER)) {
            return null;
        }

        /** @var array<array-key, mixed>|bool|float|int|string|null $payload */
        $payload = json_decode(substr($content, strlen(self::FILE_HEADER)), associative: true);
        if (!is_array($payload)) {
            return null;
        }

        if (!array_key_exists('token', $payload) || !is_string($payload['token']) || $payload['token'] === '') {
            return null;
        }

        if (!array_key_exists('created', $payload) || !is_int($payload['created'])) {
            return null;
        }

        if (($payload['created'] + self::TOKEN_LIFETIME) < time()) {
            return null;
        }

        return $payload['token'];
    }
}
