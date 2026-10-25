<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Setup\Migration\MigrationInterface;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\ConfigDeleteOperation;
use phpMyFAQ\Setup\Migration\Operations\ConfigRenameOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(Migration420RC1::class)]
#[UsesClass(OperationRecorder::class)]
#[UsesClass(ConfigAddOperation::class)]
#[UsesClass(ConfigDeleteOperation::class)]
#[UsesClass(ConfigRenameOperation::class)]
final class Migration420RC1Test extends TestCase
{
    public function testVersionAndDependencies(): void
    {
        $migration = new Migration420RC1($this->createStub(Configuration::class));

        $this->assertInstanceOf(MigrationInterface::class, $migration);
        $this->assertSame('4.2.0-RC.1', $migration->getVersion());
        $this->assertSame(['4.2.0-beta.3'], $migration->getDependencies());
    }

    public function testRenamesKeysThatStillCarryTheirOldName(): void
    {
        $operations = $this->record([
            'main.templateSet' => 'default',
            'main.enableCookieConsent' => 'true',
            'main.contactInformationHTML' => 'false',
        ]);

        $this->assertSame([
            'main.templateSet' => 'layout.templateSet',
            'main.enableCookieConsent' => 'layout.enableCookieConsent',
            'main.contactInformationHTML' => 'layout.contactInformationHTML',
        ], $operations['renamed']);
        $this->assertNotContains('main.templateSet', $operations['deleted']);
    }

    public function testDropsOldKeysWhenTheNewKeyAlreadyExists(): void
    {
        $operations = $this->record([
            'main.templateSet' => 'default',
            'layout.templateSet' => 'default',
        ]);

        $this->assertArrayNotHasKey('main.templateSet', $operations['renamed']);
        $this->assertContains('main.templateSet', $operations['deleted']);
    }

    public function testLeavesAlreadyMigratedInstallationsAlone(): void
    {
        $operations = $this->record([
            'layout.templateSet' => 'default',
            'layout.enableCookieConsent' => 'true',
            'layout.contactInformationHTML' => 'false',
        ]);

        $this->assertSame([], $operations['renamed']);
        $this->assertNotContains('main.templateSet', $operations['deleted']);
        $this->assertNotContains('main.enableCookieConsent', $operations['deleted']);
        $this->assertNotContains('main.contactInformationHTML', $operations['deleted']);
    }

    public function testReAddsConfigKeysAndRemovesObsoleteOnes(): void
    {
        $operations = $this->record([]);

        foreach (
            [
                'mail.noReplySenderAddress',
                'records.allowedMediaHosts',
                'seo.title',
                'seo.description',
                'layout.enablePrivacyLink',
                'layout.customCss',
                'seo.enableRichSnippets',
            ] as $key
        ) {
            $this->assertArrayHasKey($key, $operations['added']);
        }

        foreach (
            ['main.urlValidateInterval', 'main.enableGzipCompression', 'main.metaKeywords', 'main.send2friendText'] as $key
        ) {
            $this->assertContains($key, $operations['deleted']);
        }
    }

    /**
     * @param array<string, string> $existingConfig
     * @return array{added: array<string, mixed>, deleted: string[], renamed: array<string, string>}
     */
    private function record(array $existingConfig): array
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(
            static fn(string $item): mixed => $existingConfig[$item] ?? null,
        );

        $recorder = new OperationRecorder($configuration);
        new Migration420RC1($configuration)->up($recorder);

        $operations = ['added' => [], 'deleted' => [], 'renamed' => []];
        foreach ($recorder->getOperations() as $operation) {
            if ($operation instanceof ConfigAddOperation) {
                $operations['added'][$operation->getKey()] = $operation->getValue();
            }

            if ($operation instanceof ConfigDeleteOperation) {
                $operations['deleted'][] = $operation->getKey();
            }

            if ($operation instanceof ConfigRenameOperation) {
                $operations['renamed'][$operation->getOldKey()] = $operation->getNewKey();
            }
        }

        return $operations;
    }
}
