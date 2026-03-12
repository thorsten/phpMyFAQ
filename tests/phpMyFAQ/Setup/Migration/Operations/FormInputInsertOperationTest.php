<?php

namespace phpMyFAQ\Setup\Migration\Operations;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Environment;
use phpMyFAQ\Forms;
use phpMyFAQ\Form\FormsRepository;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(FormInputInsertOperation::class)]
#[UsesClass(Forms::class)]
#[UsesClass(FormsRepository::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Database::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(Environment::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(Translation::class)]
final class FormInputInsertOperationTest extends TestCase
{
    private string $databaseFile;
    private PdoSqlite $database;
    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-form-operation-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $this->database = new PdoSqlite();
        $this->database->connect($this->databaseFile, '', '');
        $this->configuration = new Configuration($this->database);
    }

    protected function tearDown(): void
    {
        $this->database->close();
        @unlink($this->databaseFile);

        parent::tearDown();
    }

    public function testGetType(): void
    {
        $operation = new FormInputInsertOperation($this->configuration, $this->createFormInput());

        $this->assertSame('form_input_insert', $operation->getType());
    }

    public function testGetDescription(): void
    {
        $operation = new FormInputInsertOperation($this->configuration, $this->createFormInput());

        $this->assertSame(
            'Insert form input: form=7, input=3, label=msgContactName',
            $operation->getDescription(),
        );
    }

    public function testExecuteInsertsFormInput(): void
    {
        $operation = new FormInputInsertOperation($this->configuration, $this->createFormInput());

        $this->assertTrue($operation->execute());

        $result = $this->configuration->getDb()->query(
            "SELECT input_type, input_label, input_lang, input_active, input_required
             FROM faqforms
             WHERE form_id = 7 AND input_id = 3",
        );
        $row = $this->configuration->getDb()->fetchArray($result);

        $this->assertIsArray($row);
        $this->assertSame('text', $row['input_type']);
        $this->assertSame('msgContactName', $row['input_label']);
        $this->assertSame('default', $row['input_lang']);
        $this->assertSame(1, (int) $row['input_active']);
        $this->assertSame(1, (int) $row['input_required']);
    }

    public function testExecuteReturnsFalseWhenFormsInsertThrows(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $database = $this->createMock(DatabaseDriver::class);
        $database
            ->method('escape')
            ->willThrowException(new \RuntimeException('broken database driver'));
        $configuration->method('getDb')->willReturn($database);

        $operation = new FormInputInsertOperation($configuration, $this->createFormInput());

        $this->assertFalse($operation->execute());
    }

    public function testToArray(): void
    {
        $formInput = $this->createFormInput();
        $operation = new FormInputInsertOperation($this->configuration, $formInput);

        $this->assertSame(
            [
                'type' => 'form_input_insert',
                'description' => 'Insert form input: form=7, input=3, label=msgContactName',
                'form_input' => $formInput,
            ],
            $operation->toArray(),
        );
    }

    /**
     * @return array<string, int|string>
     */
    private function createFormInput(): array
    {
        return [
            'form_id' => 7,
            'input_id' => 3,
            'input_type' => 'text',
            'input_label' => 'msgContactName',
            'input_lang' => 'default',
            'input_active' => 1,
            'input_required' => 1,
        ];
    }
}
