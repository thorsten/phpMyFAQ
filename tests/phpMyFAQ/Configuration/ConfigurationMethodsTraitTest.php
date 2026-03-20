<?php

namespace phpMyFAQ\Configuration;

use Elastic\Elasticsearch\ClientBuilder;
use Monolog\Logger;
use phpMyFAQ\ConfigurationMethodsTrait;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Environment;
use phpMyFAQ\Instance;
use phpMyFAQ\Language;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Translation\TranslationProviderInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;

#[CoversTrait(ConfigurationMethodsTrait::class)]
#[UsesClass(Environment::class)]
#[UsesClass(PdoSqlite::class)]
#[AllowMockObjectsWithoutExpectations]
class ConfigurationMethodsTraitTest extends TestCase
{
    private object $subject;

    private ConfigurationRepository $configurationRepository;

    private LayoutSettings $layoutSettings;

    private MailSettings $mailSettings;

    private UrlSettings $urlSettings;

    private LdapSettings $ldapSettings;

    private SearchSettings $searchSettings;

    private SecuritySettings $securitySettings;

    protected function setUp(): void
    {
        $this->configurationRepository = $this->createMock(ConfigurationRepository::class);
        $this->layoutSettings = $this->createMock(LayoutSettings::class);
        $this->mailSettings = $this->createMock(MailSettings::class);
        $this->urlSettings = $this->createMock(UrlSettings::class);
        $this->ldapSettings = $this->createMock(LdapSettings::class);
        $this->searchSettings = $this->createMock(SearchSettings::class);
        $this->securitySettings = $this->createMock(SecuritySettings::class);

        $this->subject = new class() {
            use ConfigurationMethodsTrait;

            public array $config = [];

            public Logger $logger;

            public PluginManager $pluginManager;

            public ConfigurationRepository $configurationRepository;

            public LayoutSettings $layoutSettings;

            public MailSettings $mailSettings;

            public UrlSettings $urlSettings;

            public LdapSettings $ldapSettings;

            public SearchSettings $searchSettings;

            public SecuritySettings $securitySettings;
        };

        $this->subject->configurationRepository = $this->configurationRepository;
        $this->subject->layoutSettings = $this->layoutSettings;
        $this->subject->mailSettings = $this->mailSettings;
        $this->subject->urlSettings = $this->urlSettings;
        $this->subject->ldapSettings = $this->ldapSettings;
        $this->subject->searchSettings = $this->searchSettings;
        $this->subject->securitySettings = $this->securitySettings;
        $this->subject->logger = new Logger('test');
    }

    // --- Database ---

    public function testSetAndGetDatabase(): void
    {
        $db = $this->createStub(DatabaseDriver::class);
        $this->subject->setDatabase($db);
        $this->assertSame($db, $this->subject->getDb());
    }

    // --- Logger ---

    public function testSetLoggerCreatesLogger(): void
    {
        $this->subject->setLogger();
        $this->assertInstanceOf(Logger::class, $this->subject->getLogger());
        $this->assertSame('phpmyfaq', $this->subject->getLogger()->getName());
    }

    public function testGetLoggerReturnsLogger(): void
    {
        $logger = new Logger('custom');
        $this->subject->logger = $logger;
        $this->assertSame($logger, $this->subject->getLogger());
    }

    // --- set() ---

    public function testSetDelegatesToRepository(): void
    {
        $repository = $this->createMock(ConfigurationRepository::class);
        $repository
            ->expects($this->once())
            ->method('updateConfigValue')
            ->with('some.key', 'some-value')
            ->willReturn(true);
        $this->subject->configurationRepository = $repository;

        $this->assertTrue($this->subject->set('some.key', 'some-value'));
    }

    public function testSetCastsValueToString(): void
    {
        $repository = $this->createMock(ConfigurationRepository::class);
        $repository->expects($this->once())->method('updateConfigValue')->with('some.key', '42')->willReturn(true);
        $this->subject->configurationRepository = $repository;

        $this->assertTrue($this->subject->set('some.key', 42));
    }

    // --- Instance ---

    public function testSetAndGetInstance(): void
    {
        $instance = $this->createStub(Instance::class);
        $this->subject->setInstance($instance);
        $this->assertSame($instance, $this->subject->getInstance());
    }

    // --- Language ---

    public function testSetAndGetLanguage(): void
    {
        $language = $this->createStub(Language::class);
        $this->subject->setLanguage($language);
        $this->assertSame($language, $this->subject->getLanguage());
    }

    // --- Container ---

    public function testSetContainer(): void
    {
        $container = $this->createStub(ContainerInterface::class);
        $this->subject->setContainer($container);
        $this->assertSame($container, $this->subject->config['core.container']);
    }

    // --- getDefaultLanguage ---

    public function testGetDefaultLanguageReturnsEnWhenConfigMissing(): void
    {
        $this->subject->config = [];
        $this->assertSame('en', $this->subject->getDefaultLanguage());
    }

    public function testGetDefaultLanguageReturnsEnWhenNull(): void
    {
        $this->subject->config = ['main.language' => null];
        $this->assertSame('en', $this->subject->getDefaultLanguage());
    }

    public function testGetDefaultLanguageExtractsLanguageCode(): void
    {
        $this->subject->config['main.language'] = 'language_de.php';
        $this->assertSame('de', $this->subject->getDefaultLanguage());
    }

    public function testGetDefaultLanguageExtractsFrenchCode(): void
    {
        $this->subject->config['main.language'] = 'language_fr.php';
        $this->assertSame('fr', $this->subject->getDefaultLanguage());
    }

    // --- Simple getters ---

    public function testGetVersion(): void
    {
        $this->subject->config['main.currentVersion'] = '4.1.0';
        $this->assertSame('4.1.0', $this->subject->getVersion());
    }

    public function testGetTitle(): void
    {
        $this->subject->config['main.titleFAQ'] = 'My FAQ';
        $this->assertSame('My FAQ', $this->subject->getTitle());
    }

    public function testGetAdminEmail(): void
    {
        $this->subject->config['main.administrationMail'] = 'admin@example.com';
        $this->assertSame('admin@example.com', $this->subject->getAdminEmail());
    }

    // --- Delegated settings getters ---

    public function testGetTemplateSet(): void
    {
        $this->layoutSettings->method('getTemplateSet')->willReturn('default');
        $this->assertSame('default', $this->subject->getTemplateSet());
    }

    public function testGetNoReplyEmail(): void
    {
        $this->mailSettings->method('getNoReplyEmail')->willReturn('noreply@example.com');
        $this->assertSame('noreply@example.com', $this->subject->getNoReplyEmail());
    }

    public function testGetMailProvider(): void
    {
        $this->mailSettings->method('getProvider')->willReturn('smtp');
        $this->assertSame('smtp', $this->subject->getMailProvider());
    }

    public function testGetDefaultUrl(): void
    {
        $this->urlSettings->method('getDefaultUrl')->willReturn('https://example.com');
        $this->assertSame('https://example.com', $this->subject->getDefaultUrl());
    }

    public function testGetRootPath(): void
    {
        $this->assertSame(PMF_ROOT_DIR, $this->subject->getRootPath());
    }

    public function testGetAllowedMediaHosts(): void
    {
        $hosts = ['youtube.com', 'vimeo.com'];
        $this->urlSettings->method('getAllowedMediaHosts')->willReturn($hosts);
        $this->assertSame($hosts, $this->subject->getAllowedMediaHosts());
    }

    public function testGetCustomCss(): void
    {
        $this->layoutSettings->method('getCustomCss')->willReturn('.custom { color: red; }');
        $this->assertSame('.custom { color: red; }', $this->subject->getCustomCss());
    }

    // --- get() ---

    public function testGetReturnsTrueForStringTrue(): void
    {
        $this->subject->config['some.flag'] = 'true';
        $this->assertTrue($this->subject->get('some.flag'));
    }

    public function testGetReturnsFalseForStringFalse(): void
    {
        $this->subject->config['some.flag'] = 'false';
        $this->assertFalse($this->subject->get('some.flag'));
    }

    public function testGetReturnsValueForOtherStrings(): void
    {
        $this->subject->config['some.key'] = 'hello';
        $this->assertSame('hello', $this->subject->get('some.key'));
    }

    public function testGetReturnsNullForMissingKeyAfterGetAll(): void
    {
        $this->configurationRepository->method('fetchAll')->willReturn([]);
        $this->assertNull($this->subject->get('nonexistent.key'));
    }

    public function testGetCallsGetAllWhenKeyMissing(): void
    {
        $repository = $this->createMock(ConfigurationRepository::class);
        $row = (object) ['config_name' => 'lazy.key', 'config_value' => 'loaded'];
        $repository->expects($this->once())->method('fetchAll')->willReturn([$row]);
        $this->subject->configurationRepository = $repository;

        $this->assertSame('loaded', $this->subject->get('lazy.key'));
    }

    public function testGetCallsGetAllWhenKeyIsNull(): void
    {
        $this->subject->config['null.key'] = null;

        $repository = $this->createMock(ConfigurationRepository::class);
        $row = (object) ['config_name' => 'null.key', 'config_value' => 'resolved'];
        $repository->expects($this->once())->method('fetchAll')->willReturn([$row]);
        $this->subject->configurationRepository = $repository;

        $this->assertSame('resolved', $this->subject->get('null.key'));
    }

    // --- getAll() ---

    public function testGetAllPopulatesConfig(): void
    {
        $rows = [
            (object) ['config_name' => 'a', 'config_value' => '1'],
            (object) ['config_name' => 'b', 'config_value' => '2'],
        ];
        $this->configurationRepository->method('fetchAll')->willReturn($rows);

        $result = $this->subject->getAll();
        $this->assertSame('1', $result['a']);
        $this->assertSame('2', $result['b']);
    }

    public function testGetAllReturnsEmptyWhenNoRows(): void
    {
        $this->configurationRepository->method('fetchAll')->willReturn([]);
        $result = $this->subject->getAll();
        $this->assertSame([], $result);
    }

    // --- LDAP ---

    public function testSetLdapConfig(): void
    {
        $ldapConfig = $this->createStub(LdapConfiguration::class);
        $servers = [['host' => 'ldap.example.com']];
        $config = ['base_dn' => 'dc=example,dc=com'];

        $this->ldapSettings
            ->expects($this->once())
            ->method('buildServers')
            ->with($ldapConfig)
            ->willReturn($servers);
        $this->ldapSettings->method('buildConfig')->willReturn($config);

        $this->subject->setLdapConfig($ldapConfig);

        $this->assertSame($servers, $this->subject->config['core.ldapServer']);
        $this->assertSame($config, $this->subject->config['core.ldapConfig']);
    }

    public function testGetLdapMapping(): void
    {
        $mapping = ['name' => 'cn', 'mail' => 'mail'];
        $this->ldapSettings->method('getLdapMapping')->willReturn($mapping);
        $this->assertSame($mapping, $this->subject->getLdapMapping());
    }

    public function testGetLdapOptions(): void
    {
        $options = ['option1' => 'value1'];
        $this->ldapSettings->method('getLdapOptions')->willReturn($options);
        $this->assertSame($options, $this->subject->getLdapOptions());
    }

    public function testGetLdapGroupConfig(): void
    {
        $groupConfig = ['group_dn' => 'cn=group'];
        $this->ldapSettings->method('getLdapGroupConfig')->willReturn($groupConfig);
        $this->assertSame($groupConfig, $this->subject->getLdapGroupConfig());
    }

    public function testGetLdapConfigReturnsArrayFromConfig(): void
    {
        $this->subject->config['core.ldapConfig'] = ['key' => 'val'];
        $this->assertSame(['key' => 'val'], $this->subject->getLdapConfig());
    }

    public function testGetLdapConfigReturnsEmptyArrayWhenNotSet(): void
    {
        $this->assertSame([], $this->subject->getLdapConfig());
    }

    public function testGetLdapServerReturnsArrayFromConfig(): void
    {
        $this->subject->config['core.ldapServer'] = [['host' => 'ldap.test']];
        $this->assertSame([['host' => 'ldap.test']], $this->subject->getLdapServer());
    }

    public function testGetLdapServerReturnsEmptyArrayWhenNotSet(): void
    {
        $this->assertSame([], $this->subject->getLdapServer());
    }

    public function testIsLdapActive(): void
    {
        $this->ldapSettings->method('isActive')->willReturn(true);
        $this->assertTrue($this->subject->isLdapActive());
    }

    // --- Elasticsearch ---

    public function testIsElasticsearchActive(): void
    {
        $this->searchSettings->method('isElasticsearchActive')->willReturn(false);
        $this->assertFalse($this->subject->isElasticsearchActive());
    }

    public function testSetAndGetElasticsearch(): void
    {
        $client = ClientBuilder::create()->setHosts(['http://localhost:9200'])->build();

        $this->subject->setElasticsearch($client);
        $this->assertSame($client, $this->subject->getElasticsearch());
    }

    public function testSetAndGetElasticsearchConfig(): void
    {
        $esConfig = $this->createMock(ElasticsearchConfiguration::class);
        $this->subject->setElasticsearchConfig($esConfig);
        $this->assertSame($esConfig, $this->subject->getElasticsearchConfig());
    }

    // --- OpenSearch ---

    public function testSetAndGetOpenSearch(): void
    {
        $client = $this->createMock(\OpenSearch\Client::class);
        $this->subject->setOpenSearch($client);
        $this->assertSame($client, $this->subject->getOpenSearch());
    }

    public function testSetAndGetOpenSearchConfig(): void
    {
        $osConfig = $this->createMock(OpenSearchConfiguration::class);
        $this->subject->setOpenSearchConfig($osConfig);
        $this->assertSame($osConfig, $this->subject->getOpenSearchConfig());
    }

    // --- isSignInWithMicrosoftActive ---

    public function testIsSignInWithMicrosoftActive(): void
    {
        $this->securitySettings->method('isSignInWithMicrosoftActive')->willReturn(true);
        $this->assertTrue($this->subject->isSignInWithMicrosoftActive());
    }

    // --- Translation Provider ---

    public function testSetAndGetTranslationProvider(): void
    {
        $provider = $this->createMock(TranslationProviderInterface::class);
        $this->subject->setTranslationProvider($provider);
        $this->assertSame($provider, $this->subject->getTranslationProvider());
    }

    public function testGetTranslationProviderReturnsNullWhenProviderIsNone(): void
    {
        $this->subject->config['translation.provider'] = 'none';
        $this->assertNull($this->subject->getTranslationProvider());
    }

    public function testGetTranslationProviderReturnsNullWhenNoContainer(): void
    {
        $this->configurationRepository
            ->method('fetchAll')
            ->willReturn([
                (object) ['config_name' => 'translation.provider', 'config_value' => 'deepl'],
            ]);

        // No container set, so initializeTranslationProvider won't set a provider
        $this->assertNull($this->subject->getTranslationProvider());
    }

    public function testGetTranslationProviderWithContainerMissingHttpClient(): void
    {
        $this->configurationRepository
            ->method('fetchAll')
            ->willReturn([
                (object) ['config_name' => 'translation.provider', 'config_value' => 'deepl'],
            ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('has')->with('phpmyfaq.http-client')->willReturn(false);
        $this->subject->config['core.container'] = $container;

        $this->assertNull($this->subject->getTranslationProvider());
    }

    public function testGetTranslationProviderSkipsInitWhenAlreadySet(): void
    {
        $provider = $this->createMock(TranslationProviderInterface::class);
        $this->subject->config['core.translationProvider'] = $provider;

        // Should return without calling initializeTranslationProvider
        $this->assertSame($provider, $this->subject->getTranslationProvider());
    }

    // --- add() ---

    public function testAddInsertsWhenKeyMissing(): void
    {
        $this->configurationRepository
            ->expects($this->once())
            ->method('insert')
            ->with('new.key', 'value')
            ->willReturn(true);

        $this->assertTrue($this->subject->add('new.key', 'value'));
    }

    public function testAddInsertsWhenKeyIsNull(): void
    {
        $this->subject->config['null.key'] = null;

        $this->configurationRepository
            ->expects($this->once())
            ->method('insert')
            ->with('null.key', 'value')
            ->willReturn(true);

        $this->assertTrue($this->subject->add('null.key', 'value'));
    }

    public function testAddReturnsTrueWhenKeyExists(): void
    {
        $this->subject->config['existing.key'] = 'existing-value';

        $this->configurationRepository->expects($this->never())->method('insert');

        $this->assertTrue($this->subject->add('existing.key', 'new-value'));
    }

    // --- delete() ---

    public function testDelete(): void
    {
        $this->configurationRepository
            ->expects($this->once())
            ->method('delete')
            ->with('some.key')
            ->willReturn(true);

        $this->assertTrue($this->subject->delete('some.key'));
    }

    // --- rename() ---

    public function testRename(): void
    {
        $this->configurationRepository
            ->expects($this->once())
            ->method('renameKey')
            ->with('old.key', 'new.key')
            ->willReturn(true);

        $this->assertTrue($this->subject->rename('old.key', 'new.key'));
    }

    // --- update() ---

    public function testUpdateSkipsRuntimeConfigs(): void
    {
        $this->configurationRepository->expects($this->never())->method('updateConfigValue');

        $this->assertTrue($this->subject->update([
            'core.database' => 'should-skip',
            'core.instance' => 'should-skip',
            'core.language' => 'should-skip',
            'core.ldapServer' => 'should-skip',
            'core.ldapConfig' => 'should-skip',
            'core.elasticsearch' => 'should-skip',
            'core.opensearch' => 'should-skip',
            'core.elasticsearchConfig' => 'should-skip',
            'core.openSearchConfig' => 'should-skip',
            'core.translationProvider' => 'should-skip',
            'core.pluginManager' => 'should-skip',
            'core.container' => 'should-skip',
        ]));
    }

    public function testUpdateSkipsPhpMyFAQToken(): void
    {
        $this->configurationRepository->expects($this->never())->method('updateConfigValue');

        $this->assertTrue($this->subject->update([
            'main.phpMyFAQToken' => 'should-skip',
        ]));
    }

    public function testUpdateProcessesRegularConfig(): void
    {
        $this->configurationRepository
            ->expects($this->once())
            ->method('updateConfigValue')
            ->with('main.titleFAQ', 'New Title');

        $this->assertTrue($this->subject->update(['main.titleFAQ' => 'New Title']));
    }

    public function testUpdateUnsetsExistingConfigEntry(): void
    {
        $this->subject->config['main.titleFAQ'] = 'Old Title';

        $this->configurationRepository->method('updateConfigValue')->willReturn(true);

        $this->subject->update(['main.titleFAQ' => 'New Title']);

        $this->assertArrayNotHasKey('main.titleFAQ', $this->subject->config);
    }

    public function testUpdateHandlesNullValue(): void
    {
        $this->configurationRepository
            ->expects($this->once())
            ->method('updateConfigValue')
            ->with('main.titleFAQ', '');

        $this->assertTrue($this->subject->update(['main.titleFAQ' => null]));
    }

    public function testUpdateProcessesMultipleConfigs(): void
    {
        $this->configurationRepository->expects($this->exactly(2))->method('updateConfigValue');

        $this->assertTrue($this->subject->update([
            'main.titleFAQ' => 'Title',
            'main.language' => 'de',
        ]));
    }

    // --- replaceMainReferenceUrl ---

    public function testReplaceMainReferenceUrlReplacesMatchingContent(): void
    {
        $contentItems = [
            (object) ['id' => 1, 'lang' => 'en', 'content' => '<img src="https://old.com/image.png">'],
            (object) ['id' => 2, 'lang' => 'de', 'content' => 'No URL here'],
        ];

        $this->configurationRepository->method('getFaqDataContents')->willReturn($contentItems);

        $this->configurationRepository
            ->expects($this->once())
            ->method('updateFaqDataContentById')
            ->with(1, 'en', '<img src="https://new.com/image.png">');

        $this->assertTrue($this->subject->replaceMainReferenceUrl('https://old.com', 'https://new.com'));
    }

    public function testReplaceMainReferenceUrlHandlesEmptyContent(): void
    {
        $this->configurationRepository->method('getFaqDataContents')->willReturn([]);

        $this->configurationRepository->expects($this->never())->method('updateFaqDataContentById');

        $this->assertTrue($this->subject->replaceMainReferenceUrl('https://old.com', 'https://new.com'));
    }

    public function testReplaceMainReferenceUrlSkipsNonMatchingContent(): void
    {
        $contentItems = [
            (object) ['id' => 1, 'lang' => 'en', 'content' => 'No match here'],
        ];

        $this->configurationRepository->method('getFaqDataContents')->willReturn($contentItems);

        $this->configurationRepository->expects($this->never())->method('updateFaqDataContentById');

        $this->assertTrue($this->subject->replaceMainReferenceUrl('https://old.com', 'https://new.com'));
    }

    // --- Plugin Manager ---

    public function testGetPluginManager(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $this->subject->config['core.pluginManager'] = $pluginManager;
        $this->assertSame($pluginManager, $this->subject->getPluginManager());
    }

    public function testTriggerEvent(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginManager->expects($this->once())->method('triggerEvent')->with('onFaqCreate', ['id' => 42]);

        $this->subject->pluginManager = $pluginManager;
        $this->subject->triggerEvent('onFaqCreate', ['id' => 42]);
    }

    public function testGetPluginConfig(): void
    {
        $pluginManager = $this->createMock(PluginManager::class);
        $pluginConfiguration = $this->createMock(PluginConfigurationInterface::class);
        $pluginManager
            ->expects($this->once())
            ->method('getPluginConfig')
            ->with('myPlugin')
            ->willReturn($pluginConfiguration);

        $this->subject->pluginManager = $pluginManager;
        $this->assertSame($pluginConfiguration, $this->subject->getPluginConfig('myPlugin'));
    }
}
