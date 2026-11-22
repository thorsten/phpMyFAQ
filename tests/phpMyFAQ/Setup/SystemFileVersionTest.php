<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(SystemFileVersion::class)]
#[UsesClass(System::class)]
final class SystemFileVersionTest extends TestCase
{
    private string $systemFile;

    protected function setUp(): void
    {
        $systemFile = tempnam(sys_get_temp_dir(), 'pmf-system-file-');
        self::assertNotFalse($systemFile);
        $this->systemFile = $systemFile;
    }

    protected function tearDown(): void
    {
        @unlink($this->systemFile);
    }

    /**
     * The scenario of the command line update: the loaded System class is the old one, the
     * file on disk already belongs to the new release.
     */
    public function testReadsTheVersionOfTheFileOnDiskAndNotOfTheLoadedClass(): void
    {
        file_put_contents($this->systemFile, $this->systemSource(9, 8, 7, 'beta.2', '9.1'));

        $installedCode = new SystemFileVersion($this->systemFile);

        $this->assertSame('9.8.7-beta.2', $installedCode->version());
        $this->assertSame('9.1', $installedCode->apiVersion());
        $this->assertNotSame(System::getVersion(), $installedCode->version());
    }

    public function testAStableReleaseHasNoPreReleaseSuffix(): void
    {
        file_put_contents($this->systemFile, $this->systemSource(4, 2, 10, '', '4.0'));

        $this->assertSame('4.2.10', new SystemFileVersion($this->systemFile)->version());
    }

    public function testMatchesTheLoadedClassForTheShippedSystemFile(): void
    {
        $installedCode = new SystemFileVersion((string) new ReflectionClass(System::class)->getFileName());

        $this->assertSame(System::getVersion(), $installedCode->version());
        $this->assertSame(System::getApiVersion(), $installedCode->apiVersion());
    }

    public function testReturnsNullWhenTheVersionCannotBeRead(): void
    {
        $missing = new SystemFileVersion($this->systemFile . '.missing');
        $this->assertNull($missing->version());
        $this->assertNull($missing->apiVersion());

        file_put_contents($this->systemFile, '<?php class System { private const int VERSION_MAJOR = 4; }');
        $incomplete = new SystemFileVersion($this->systemFile);
        $this->assertNull($incomplete->version());
        $this->assertNull($incomplete->apiVersion());
    }

    private function systemSource(int $major, int $minor, int $patchLevel, string $preRelease, string $api): string
    {
        return sprintf(
            "<?php\nclass System\n{\n    private const int VERSION_MAJOR = %d;\n"
            . "    private const int VERSION_MINOR = %d;\n    private const int VERSION_PATCH_LEVEL = %d;\n"
            . "    private const string VERSION_PRE_RELEASE = '%s';\n    private const string VERSION_API = '%s';\n}\n",
            $major,
            $minor,
            $patchLevel,
            $preRelease,
            $api,
        );
    }
}
