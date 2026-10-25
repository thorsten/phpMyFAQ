<?php

declare(strict_types=1);

namespace phpMyFAQ\Command;

use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

#[CoversClass(CreateHashesCommand::class)]
#[RunTestsInSeparateProcesses]
#[AllowMockObjectsWithoutExpectations]
class CreateHashesCommandTest extends TestCase
{
    /**
     * @throws ExceptionInterface
     * @throws \JsonException
     */
    public function testCommandWritesHashesToFile(): void
    {
        $system = $this->createMock(System::class);
        $filesystem = new Filesystem();

        $system
            ->expects($this->once())
            ->method('createHashes')
            ->willReturn(json_encode([
                'created' => '2025-01-01T00:00:00Z',
                '/foo.php' => 'abc123',
            ], JSON_THROW_ON_ERROR));

        $command = new CreateHashesCommand($system, $filesystem);

        $tempFile = tempnam(sys_get_temp_dir(), 'hashes');
        $input = new ArrayInput(['--root' => PMF_ROOT_DIR, '--out' => $tempFile]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($tempFile);
        $this->assertJsonStringEqualsJsonString(
            json_encode(['created' => '2025-01-01T00:00:00Z', '/foo.php' => 'abc123'], JSON_THROW_ON_ERROR),
            file_get_contents($tempFile),
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws \JsonException
     */
    public function testCommandOutputsHashesWhenNoOutOption(): void
    {
        $hashes = json_encode(['created' => '2025-01-02T00:00:00Z'], JSON_THROW_ON_ERROR);

        $system = $this->createMock(System::class);
        $system->expects($this->once())->method('createHashes')->willReturn($hashes);

        $filesystem = new Filesystem();
        $command = new CreateHashesCommand($system, $filesystem);

        $input = new ArrayInput(['--root' => PMF_ROOT_DIR]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString($hashes, $output->fetch());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testCommandFailsWhenRootMissing(): void
    {
        $system = $this->createMock(System::class);
        $system->expects($this->never())->method('createHashes');
        $filesystem = new Filesystem();

        $command = new CreateHashesCommand($system, $filesystem);

        $input = new ArrayInput(['--root' => '/definitely/not/here']);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('does not exist', $output->fetch());
    }

    /**
     * @throws ExceptionInterface
     */
    public function testCommandFailsWhenHashCreationThrows(): void
    {
        $system = $this->createMock(System::class);
        $system
            ->expects($this->once())
            ->method('createHashes')
            ->willThrowException(new \RuntimeException('disk gone'));

        $command = new CreateHashesCommand($system, new Filesystem());

        $input = new ArrayInput(['--root' => PMF_ROOT_DIR]);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Failed to create hashes: disk gone', $output->fetch());
    }

    /**
     * @throws ExceptionInterface
     * @throws \JsonException
     */
    public function testCommandCreatesMissingOutputDirectory(): void
    {
        $hashes = json_encode(['created' => '2025-01-03T00:00:00Z'], JSON_THROW_ON_ERROR);

        $system = $this->createMock(System::class);
        $system->expects($this->once())->method('createHashes')->willReturn($hashes);

        $directory = sys_get_temp_dir() . '/pmf-hashes-' . uniqid('', true);
        $outputPath = $directory . '/hashes.json';
        $this->assertDirectoryDoesNotExist($directory);

        $command = new CreateHashesCommand($system, new Filesystem());

        try {
            $exitCode = $command->run(new ArrayInput([
                '--root' => PMF_ROOT_DIR,
                '--out' => $outputPath,
            ]), new BufferedOutput());

            $this->assertSame(Command::SUCCESS, $exitCode);
            $this->assertFileExists($outputPath);
            $this->assertSame($hashes, file_get_contents($outputPath));
        } finally {
            @unlink($outputPath);
            @rmdir($directory);
        }
    }

    /**
     * @throws ExceptionInterface
     */
    public function testCommandFailsWhenHashesCannotBeWritten(): void
    {
        $system = $this->createMock(System::class);
        $system->expects($this->once())->method('createHashes')->willReturn('{}');

        $filesystem = $this->createMock(Filesystem::class);
        $filesystem->method('exists')->willReturn(true);
        $filesystem->method('dumpFile')->willThrowException(new IOException('read-only file system'));

        $command = new CreateHashesCommand($system, $filesystem);

        $input = new ArrayInput(['--root' => PMF_ROOT_DIR, '--out' => sys_get_temp_dir() . '/pmf-unwritable.json']);
        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Unable to write hashes: read-only file system', $output->fetch());
    }
}
