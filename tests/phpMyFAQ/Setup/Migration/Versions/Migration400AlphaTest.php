<?php

namespace phpMyFAQ\Setup\Migration\Versions;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Filesystem\Filesystem;
use phpMyFAQ\Setup\Migration\AbstractMigration;
use phpMyFAQ\Setup\Migration\Operations\ConfigAddOperation;
use phpMyFAQ\Setup\Migration\Operations\ConfigDeleteOperation;
use phpMyFAQ\Setup\Migration\Operations\DirectoryCopyOperation;
use phpMyFAQ\Setup\Migration\Operations\FileCopyOperation;
use phpMyFAQ\Setup\Migration\Operations\OperationRecorder;
use phpMyFAQ\Setup\Migration\Operations\SqlOperation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Migration400Alpha::class)]
#[UsesClass(AbstractMigration::class)]
#[UsesClass(Database::class)]
#[UsesClass(OperationRecorder::class)]
#[UsesClass(ConfigAddOperation::class)]
#[UsesClass(ConfigDeleteOperation::class)]
#[UsesClass(DirectoryCopyOperation::class)]
#[UsesClass(FileCopyOperation::class)]
#[UsesClass(SqlOperation::class)]
class Migration400AlphaTest extends TestCase
{
    public function testMetadata(): void
    {
        $migration = new Migration400Alpha($this->createStub(Configuration::class));

        $this->assertSame('4.0.0-alpha', $migration->getVersion());
        $this->assertSame(['3.2.3'], $migration->getDependencies());
        $this->assertStringContainsString('bookmarks', $migration->getDescription());
    }

    /**
     * The legacy config directory constant is only defined by the bootstrap of a real
     * installation, so it is defined here in an isolated process.
     */
    #[RunInSeparateProcess]
    public function testUpCopiesTheLegacyConfigurationFiles(): void
    {
        if (!defined('PMF_LEGACY_CONFIG_DIR')) {
            define('PMF_LEGACY_CONFIG_DIR', '/legacy/config');
        }

        $recorder = new OperationRecorder(
            $this->createStub(Configuration::class),
            $this->createStub(Filesystem::class),
        );
        new Migration400Alpha($this->createStub(Configuration::class))->up($recorder);

        $fileCopies = $recorder->getOperationsByType('file_copy');

        $this->assertCount(4, $fileCopies);
        $this->assertContainsOnlyInstancesOf(FileCopyOperation::class, $fileCopies);
        $this->assertSame('/legacy/config/database.php', $fileCopies[0]->getSource());
        $this->assertSame(PMF_CONFIG_DIR . '/database.php', $fileCopies[0]->getDestination());
        $this->assertSame(
            ['/legacy/config/azure.php', '/legacy/config/elasticsearch.php', '/legacy/config/ldap.php'],
            array_map(
                static fn(FileCopyOperation $operation): string => $operation->getSource(),
                array_slice($fileCopies, 1),
            ),
        );
        $this->assertNotEmpty($recorder->getOperationsByType('directory_copy'));
    }
}
