<?php

declare(strict_types=1);

namespace phpMyFAQ;

use LogicException;
use Monolog\Logger;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

#[AllowMockObjectsWithoutExpectations]
#[CoversTrait(ConfigurationMethodsTrait::class)]
#[UsesNamespace('phpMyFAQ')]
final class ConfigurationMethodsTraitTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();
    }

    /**
     * A second Configuration on the same database, so a test can observe what was persisted
     * rather than what the first instance remembers in memory.
     */
    private function reloadedConfiguration(): Configuration
    {
        $configuration = new Configuration($this->configuration->getDb());
        $configuration->getAll();

        return $configuration;
    }

    public function testGetConvertsStoredBooleansAndReturnsNullForUnknownKeys(): void
    {
        $this->configuration->set('main.enableRewriteRules', 'true');
        $this->configuration->set('main.enableUserTracking', 'false');

        $this->assertTrue($this->configuration->get('main.enableRewriteRules'));
        $this->assertFalse($this->configuration->get('main.enableUserTracking'));
        $this->assertNull($this->configuration->get('does.not.exist'));
    }

    public function testGetLoadsTheConfigurationLazily(): void
    {
        $this->configuration->set('main.titleFAQ', 'Lazy FAQ');

        $fresh = new Configuration($this->configuration->getDb());

        $this->assertSame('Lazy FAQ', $fresh->get('main.titleFAQ'));
    }

    public function testSetPersistsAndUpdatesTheInMemoryValue(): void
    {
        $this->assertTrue($this->configuration->set('main.titleFAQ', 'Persisted title'));

        $this->assertSame('Persisted title', $this->configuration->get('main.titleFAQ'));
        $this->assertSame('Persisted title', $this->reloadedConfiguration()->get('main.titleFAQ'));
    }

    public function testGetAllReturnsEveryStoredItemAndTheRuntimeObjects(): void
    {
        $all = $this->configuration->getAll();

        $this->assertArrayHasKey('main.titleFAQ', $all);
        $this->assertArrayHasKey('main.currentVersion', $all);
        $this->assertSame($this->configuration->getDb(), $all['core.database']);
    }

    public function testAddInsertsMissingKeysOnlyOnce(): void
    {
        $this->assertNull($this->configuration->get('test.newKey'));

        $this->assertTrue($this->configuration->add('test.newKey', 'first'));
        $this->assertTrue($this->configuration->add('test.newKey', 'second'));

        $this->assertSame('first', $this->reloadedConfiguration()->get('test.newKey'));
    }

    public function testDeleteRemovesAStoredKey(): void
    {
        $this->configuration->add('test.toDelete', 'value');

        $this->assertTrue($this->configuration->delete('test.toDelete'));

        $this->assertNull($this->reloadedConfiguration()->get('test.toDelete'));
    }

    public function testRenameMovesTheValueToTheNewKey(): void
    {
        $this->configuration->add('test.oldName', 'kept');

        $this->assertTrue($this->configuration->rename('test.oldName', 'test.newName'));

        $reloaded = $this->reloadedConfiguration();
        $this->assertNull($reloaded->get('test.oldName'));
        $this->assertSame('kept', $reloaded->get('test.newName'));
    }

    public function testUpdateWritesRegularKeysAndInvalidatesThemInMemory(): void
    {
        $this->configuration->set('main.titleFAQ', 'Before');
        $this->configuration->set('main.administrationMail', 'before@example.org');

        $this->assertTrue($this->configuration->update([
            'main.titleFAQ' => 'After',
            'main.administrationMail' => null,
        ]));

        $this->assertSame('After', $this->configuration->get('main.titleFAQ'));
        $this->assertSame('', $this->reloadedConfiguration()->get('main.administrationMail'));
    }

    public function testUpdateRefusesRuntimeProtectedAndTokenKeys(): void
    {
        $token = $this->configuration->get('main.phpMyFAQToken');
        // The installer seeds this key empty, so add() would be a no-op here.
        $this->configuration->add('upgrade.lastDownloadedPackage', 'verified.zip');
        $this->configuration->set('upgrade.lastDownloadedPackage', 'verified.zip');

        $this->assertTrue($this->configuration->update([
            'main.phpMyFAQToken' => 'forged',
            'upgrade.lastDownloadedPackage' => 'https://evil.example/payload.zip',
            'core.database' => 'nonsense',
        ]));

        $reloaded = $this->reloadedConfiguration();
        $this->assertSame($token, $reloaded->get('main.phpMyFAQToken'));
        $this->assertSame('verified.zip', $reloaded->get('upgrade.lastDownloadedPackage'));
        $this->assertSame($this->configuration->getDb(), $this->configuration->getDb());
    }

    public function testGetDefaultLanguageStripsTheFilenameParts(): void
    {
        $this->configuration->set('main.language', 'language_de.php');

        $this->assertSame('de', $this->configuration->getDefaultLanguage());
    }

    public function testGetDefaultLanguageFallsBackToEnglish(): void
    {
        $this->configuration->delete('main.language');

        $this->assertSame('en', $this->reloadedConfiguration()->getDefaultLanguage());
    }

    public function testInstallationWideGettersReadTheStoredValues(): void
    {
        $this->configuration->set('main.currentVersion', '4.2.0-test');
        $this->configuration->set('main.titleFAQ', 'My FAQ');
        $this->configuration->set('main.administrationMail', 'admin@example.org');
        $this->configuration->set('main.referenceURL', 'https://faq.example.org');
        $this->configuration->set('records.allowedMediaHosts', 'youtube.com,vimeo.com');

        $this->assertSame('4.2.0-test', $this->configuration->getVersion());
        $this->assertSame('My FAQ', $this->configuration->getTitle());
        $this->assertSame('admin@example.org', $this->configuration->getAdminEmail());
        $this->assertSame('https://faq.example.org/', $this->configuration->getDefaultUrl());
        $this->assertSame(['youtube.com', 'vimeo.com'], $this->configuration->getAllowedMediaHosts());
        $this->assertSame(PMF_ROOT_DIR, $this->configuration->getRootPath());
    }

    public function testRuntimeObjectsAreRegisteredAndQueried(): void
    {
        $this->assertInstanceOf(Sqlite3::class, $this->configuration->getDb());
        $this->assertInstanceOf(Logger::class, $this->configuration->getLogger());

        $instance = $this->createStub(Instance::class);
        $this->configuration->setInstance($instance);
        $this->assertSame($instance, $this->configuration->getInstance());

        $language = $this->createStub(Language::class);
        $this->configuration->setLanguage($language);
        $this->assertSame($language, $this->configuration->getLanguage());

        $this->assertFalse($this->configuration->hasElasticsearch());
        $this->assertFalse($this->configuration->hasOpenSearch());
    }

    public function testMissingRuntimeObjectsFailLoudly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('No phpMyFAQ\Language registered under "core.language".');

        $this->configuration->getLanguage();
    }

    public function testServiceContainerIsOnlyReturnedWhenItIsAContainer(): void
    {
        $this->assertNull($this->configuration->getServiceContainer());

        $this->configuration->setContainer('not a container');
        $this->assertNull($this->configuration->getServiceContainer());

        $container = $this->createStub(ContainerInterface::class);
        $this->configuration->setContainer($container);
        $this->assertSame($container, $this->configuration->getServiceContainer());
    }

    public function testFeatureFlagsReadTheStoredSettings(): void
    {
        $this->assertFalse($this->configuration->isElasticsearchActive());
        $this->assertFalse($this->configuration->isLdapActive());
        $this->assertFalse($this->configuration->isSignInWithMicrosoftActive());
        $this->assertFalse($this->configuration->isSignInWithKeycloakActive());

        $this->configuration->set('security.enableSignInWithMicrosoft', 'true');
        $this->configuration->set('keycloak.enable', 'true');

        $this->assertTrue($this->configuration->isSignInWithMicrosoftActive());
        $this->assertTrue($this->configuration->isSignInWithKeycloakActive());
    }

    public function testNoTranslationProviderWhenNoneIsConfigured(): void
    {
        $this->configuration->set('translation.provider', 'none');

        $this->assertNull($this->configuration->getTranslationProvider());
    }

    public function testReplaceMainReferenceUrlRewritesFaqContent(): void
    {
        $db = $this->configuration->getDb();
        $db->query(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, "
            . "comment, updated, date_start, date_end, notes) VALUES (9001, 'en', 10001, 0, 'published', 0, '', 'Moved', "
            . "'<img src=\"https://old.example.org/images/a.png\"> and https://old.example.org/faq/1', 'Author', "
            . "'author@example.org', 'y', '20250101120000', '00000000000000', '99991231235959', '')",
        );
        $db->query(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, "
            . "comment, updated, date_start, date_end, notes) VALUES (9002, 'en', 10002, 0, 'published', 0, '', 'Untouched', "
            . "'No links here', 'Author', 'author@example.org', 'y', '20250101120000', '00000000000000', '99991231235959', '')",
        );

        $this->assertTrue($this->configuration->replaceMainReferenceUrl('https://old.example.org', 'https://new.example.org'));

        $result = $db->query('SELECT id, content FROM faqdata WHERE id IN (9001, 9002) ORDER BY id');
        $contents = [];
        while (($row = $db->fetchObject($result)) instanceof \stdClass) {
            $contents[(int) $row->id] = (string) $row->content;
        }

        $this->assertSame(
            '<img src="https://new.example.org/images/a.png"> and https://new.example.org/faq/1',
            $contents[9001],
        );
        $this->assertSame('No links here', $contents[9002]);
    }

    public function testPluginAccessors(): void
    {
        $this->assertInstanceOf(Plugin\PluginManager::class, $this->configuration->getPluginManager());
        $this->assertNull($this->configuration->getPluginConfig('no-such-plugin'));

        // Unknown events are ignored rather than rejected.
        $this->configuration->triggerEvent('test.event', ['payload' => true]);
    }

    public function testLayoutAndMailSettingsAreDelegated(): void
    {
        $this->configuration->set('layout.templateSet', 'classic');
        $this->configuration->set('layout.customCss', 'body { color: red; }');
        $this->configuration->set('mail.provider', 'sendgrid');
        $this->configuration->set('main.administrationMail', 'admin@example.org');
        $this->configuration->set('mail.noReplySenderAddress', '');

        $this->assertSame('classic', $this->configuration->getTemplateSet());
        $this->assertSame('body { color: red; }', $this->configuration->getCustomCss());
        $this->assertSame('sendgrid', $this->configuration->getMailProvider());
        // Without a dedicated no-reply sender the administration address is used.
        $this->assertSame('admin@example.org', $this->configuration->getNoReplyEmail());

        $this->configuration->set('mail.noReplySenderAddress', 'noreply@example.org');
        $this->assertSame('noreply@example.org', $this->configuration->getNoReplyEmail());
    }
}
