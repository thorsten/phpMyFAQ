<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class UpdateTokenTest
 *
 * @testdox The update token
 */
class UpdateTokenTest extends TestCase
{
    private string $configDir;

    private UpdateToken $updateToken;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configDir = sys_get_temp_dir() . '/pmf-update-token-' . uniqid();
        mkdir($this->configDir);

        $this->updateToken = new UpdateToken($this->configDir);
    }

    protected function tearDown(): void
    {
        if (is_file($this->configDir . '/' . UpdateToken::TOKEN_FILENAME)) {
            unlink($this->configDir . '/' . UpdateToken::TOKEN_FILENAME);
        }

        if (is_dir($this->configDir)) {
            rmdir($this->configDir);
        }

        parent::tearDown();
    }

    /**
     * @throws Exception
     */
    public function testGetOrCreateCreatesARandomToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $token);
        $this->assertFileExists($this->updateToken->getTokenFilePath());
    }

    /**
     * The token file lives in the document root, so it must not disclose anything
     * when it is requested over HTTP: PHP exits before the payload is reached.
     *
     * @throws Exception
     */
    public function testTokenFileIsNotReadableOverHttp(): void
    {
        $this->updateToken->getOrCreate();

        $content = file_get_contents($this->updateToken->getTokenFilePath());

        $this->assertStringStartsWith('<?php exit; ?>', $content);
        $this->assertStringEndsWith('.php', $this->updateToken->getTokenFilePath());
    }

    /**
     * @throws Exception
     */
    public function testGetOrCreateKeepsAnExistingToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->assertSame($token, $this->updateToken->getOrCreate());
        $this->assertSame($token, (new UpdateToken($this->configDir))->getOrCreate());
    }

    /**
     * @throws Exception
     */
    public function testIsValidAcceptsTheStoredToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->assertTrue($this->updateToken->isValid($token));
    }

    /**
     * @throws Exception
     */
    public function testIsValidRejectsEverythingElse(): void
    {
        $this->updateToken->getOrCreate();

        $this->assertFalse($this->updateToken->isValid('not-the-token'));
        $this->assertFalse($this->updateToken->isValid(''));
        $this->assertFalse($this->updateToken->isValid(null));
    }

    public function testIsValidRejectsATokenWithoutATokenFile(): void
    {
        $this->assertFalse($this->updateToken->isValid('0123456789abcdef0123456789abcdef'));
    }

    /**
     * @throws Exception
     */
    public function testIsValidRejectsAnExpiredToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->writeTokenFile($token, time() - UpdateToken::TOKEN_LIFETIME - 1);

        $this->assertFalse($this->updateToken->isValid($token));
    }

    /**
     * @throws Exception
     */
    public function testGetOrCreateReplacesAnExpiredToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->writeTokenFile($token, time() - UpdateToken::TOKEN_LIFETIME - 1);

        $this->assertNotSame($token, $this->updateToken->getOrCreate());
    }

    public function testIsValidRejectsABrokenTokenFile(): void
    {
        file_put_contents($this->updateToken->getTokenFilePath(), '<?php exit; ?>' . PHP_EOL . 'no json at all');

        $this->assertFalse($this->updateToken->isValid('no json at all'));
    }

    /**
     * @throws Exception
     */
    public function testDeleteRemovesTheToken(): void
    {
        $token = $this->updateToken->getOrCreate();

        $this->updateToken->delete();

        $this->assertFileDoesNotExist($this->updateToken->getTokenFilePath());
        $this->assertFalse($this->updateToken->isValid($token));
    }

    public function testGetOrCreateThrowsForAnUnwritableDirectory(): void
    {
        $updateToken = new UpdateToken($this->configDir . '/does-not-exist');

        $this->expectException(Exception::class);

        $updateToken->getOrCreate();
    }

    private function writeTokenFile(string $token, int $created): void
    {
        file_put_contents(
            $this->updateToken->getTokenFilePath(),
            '<?php exit; ?>' . PHP_EOL . json_encode(['token' => $token, 'created' => $created]),
        );
    }
}
