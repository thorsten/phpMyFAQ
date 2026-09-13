<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(PackageVerifier::class)]
final class PackageVerifierTest extends TestCase
{
    private string $package;
    private TestHandler $logHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->package = tempnam(sys_get_temp_dir(), 'pmf-package-');
        file_put_contents($this->package, 'package bytes');
        $this->logHandler = new TestHandler();
    }

    protected function tearDown(): void
    {
        unlink($this->package);
        parent::tearDown();
    }

    public function testReleaseIsAcceptedWhenSha256Matches(): void
    {
        $verifier = $this->createVerifier(['zip' => ['sha256' => strtoupper(hash_file('sha256', $this->package))]]);

        self::assertTrue($verifier->verifyRelease($this->package, '4.2.0'));
    }

    public function testReleaseIsRefusedWhenMd5MatchesButSha256DoesNot(): void
    {
        $verifier = $this->createVerifier([
            'zip' => ['md5' => md5_file($this->package), 'sha256' => str_repeat('f', 64)],
        ]);

        self::assertFalse($verifier->verifyRelease($this->package, '4.2.0'));
    }

    public function testReleaseWithoutChecksumsIsRefused(): void
    {
        self::assertFalse($this->createVerifier(['zip' => ['size' => 123]])->verifyRelease($this->package, '4.2.0'));
        self::assertFalse($this->createVerifier('not json')->verifyRelease($this->package, '4.2.0'));
    }

    public function testNightlyDigestIsCompared(): void
    {
        $name = basename($this->package);
        $ok = $this->createVerifier([
            'assets' => [['name' => $name, 'digest' => 'sha256:' . hash_file('sha256', $this->package)]],
        ]);
        $bad = $this->createVerifier(['assets' => [['name' => $name, 'digest' => 'sha256:' . str_repeat('0', 64)]]]);
        $md5Digest = $this->createVerifier([
            'assets' => [['name' => $name, 'digest' => 'md5:' . md5_file($this->package)]],
        ]);

        self::assertTrue($ok->verifyNightly($this->package));
        self::assertFalse($bad->verifyNightly($this->package));
        self::assertFalse($md5Digest->verifyNightly($this->package), 'only sha256 digests are accepted');
    }

    public function testNightlyWithoutDigestIsRefusedUnlessAllowedAndLogged(): void
    {
        $refusing = $this->createVerifier(['assets' => []]);
        self::assertFalse($refusing->verifyNightly($this->package));
        self::assertFalse($this->logHandler->hasWarningRecords());

        $allowing = $this->createVerifier(['assets' => []], allowUnverified: true);
        self::assertTrue($allowing->isUnverifiedNightlyAllowed());
        self::assertTrue($allowing->verifyNightly($this->package));
        self::assertTrue($this->logHandler->hasWarningThatContains('without digest verification'));
    }

    public function testNightlyIsRefusedAndLoggedWhenGitHubFails(): void
    {
        $verifier = $this->createVerifier('', httpCode: 500);

        self::assertFalse($verifier->verifyNightly($this->package));
        self::assertTrue($this->logHandler->hasErrorRecords());
    }

    /**
     * @param array<string, mixed>|string $body
     */
    private function createVerifier(
        array|string $body,
        bool $allowUnverified = false,
        int $httpCode = 200,
    ): PackageVerifier {
        $logger = new Logger('test', [$this->logHandler]);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getLogger')->willReturn($logger);
        $configuration->method('get')->willReturnCallback(static fn(string $item): mixed => (
            $item === 'upgrade.allowUnverifiedNightly' ? ($allowUnverified ? 'true' : 'false') : null
        ));

        $content = is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body;
        $httpClient = new MockHttpClient(new MockResponse($content, ['http_code' => $httpCode]));

        return new PackageVerifier($configuration, $httpClient);
    }
}
