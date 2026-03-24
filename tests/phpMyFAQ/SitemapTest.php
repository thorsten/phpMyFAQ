<?php

namespace phpMyFAQ;

use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\DatabaseConfiguration;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database\PdoMysql;
use phpMyFAQ\Database\PdoPgsql;
use phpMyFAQ\Database\PdoSqlite;
use phpMyFAQ\Database\PdoSqlsrv;
use phpMyFAQ\Language\LanguageCodes;
use phpMyFAQ\Language\LanguageDetector;
use phpMyFAQ\Link\Strategy\GenericPathStrategy;
use phpMyFAQ\Link\Strategy\StrategyRegistry;
use phpMyFAQ\Link\Util\TitleSlugifier;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Strings\AbstractString;
use phpMyFAQ\Strings\Mbstring;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(Sitemap::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(DatabaseConfiguration::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Database::class)]
#[UsesClass(PdoMysql::class)]
#[UsesClass(PdoPgsql::class)]
#[UsesClass(PdoSqlite::class)]
#[UsesClass(PdoSqlsrv::class)]
#[UsesClass(Environment::class)]
#[UsesClass(Filter::class)]
#[UsesClass(Language::class)]
#[UsesClass(LanguageCodes::class)]
#[UsesClass(LanguageDetector::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(Strings::class)]
#[UsesClass(AbstractString::class)]
#[UsesClass(Mbstring::class)]
#[UsesClass(Link::class)]
#[UsesClass(GenericPathStrategy::class)]
#[UsesClass(StrategyRegistry::class)]
#[UsesClass(TitleSlugifier::class)]
#[UsesClass(Utils::class)]
#[UsesClass(Translation::class)]
class SitemapTest extends TestCase
{
    private const int FAQ_ID = 9990;
    private const int FAQ_ID_2 = 9991;
    private const int CAT_ID = 9990;
    private const int CAT_ID_2 = 9991;

    private Configuration $configuration;

    private PdoSqlite $db;

    /**
     * @throws Exception
     * @throws Core\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbConfig = new DatabaseConfiguration(PMF_TEST_DIR . '/content/core/config/database.php');
        Database::setTablePrefix($dbConfig->getPrefix());
        $this->db = Database::factory($dbConfig->getType());
        $this->db->connect(
            $dbConfig->getServer(),
            $dbConfig->getUser(),
            $dbConfig->getPassword(),
            $dbConfig->getDatabase(),
            $dbConfig->getPort(),
        );
        $this->configuration = new Configuration($this->db);
        $this->configuration->set('main.referenceURL', 'https://example.com/');

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->cleanupTestData();
        $this->insertTestFaq(self::FAQ_ID, 'sample question', 'sample answer', self::CAT_ID);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
    }

    private function createSitemap(): Sitemap
    {
        $sitemap = new Sitemap($this->configuration);
        $sitemap->setUser(-1);
        $sitemap->setGroups([-1]);
        return $sitemap;
    }

    private function createSitemapBasic(): Sitemap
    {
        $this->configuration->set('security.permLevel', 'basic');
        $sitemap = new Sitemap($this->configuration);
        $sitemap->setUser(-1);
        return $sitemap;
    }

    public function testConstructorWithBasicPermLevel(): void
    {
        $sitemap = $this->createSitemapBasic();

        $letters = $sitemap->getAllFirstLetters();
        $this->assertIsArray($letters);
        $this->assertNotEmpty($letters);
    }

    public function testConstructorWithMediumPermLevel(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $sitemap = new Sitemap($this->configuration);
        $sitemap->setUser(-1);
        $sitemap->setGroups([-1]);

        $letters = $sitemap->getAllFirstLetters();
        $this->assertIsArray($letters);
        $this->assertNotEmpty($letters);
    }

    public function testSetUser(): void
    {
        $sitemap = $this->createSitemapBasic();

        // Default user is -1, verify setUser changes the value used in queries.
        // Insert a record only visible to user_id=42
        $this->db->query(sprintf(
            'INSERT INTO faqdata (id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) '
            . "VALUES (%d, 'en', %d, 'yes', 'user42 question', 'answer', 'kw', 'yes', 'Author', 'a@b.com', 'date')",
            self::FAQ_ID_2,
            self::FAQ_ID_2 + 1000,
        ));
        $this->db->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', self::FAQ_ID_2));
        $this->db->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, 42)', self::FAQ_ID_2));
        $this->db->query(sprintf(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, 'en', %d, 'en')",
            self::CAT_ID,
            self::FAQ_ID_2,
        ));

        // With default user (-1), shouldn't see user42 question via user_id match
        $faqs = $sitemap->getFaqsFromLetter('U');
        $hasUser42 = false;
        foreach ($faqs as $faq) {
            if ($faq->question === 'user42 question') {
                $hasUser42 = true;
            }
        }

        $this->assertFalse($hasUser42, 'user42 question should not be visible to user -1');

        // After setUser(42), user42 question should be visible
        $sitemap->setUser(42);
        $faqs = $sitemap->getFaqsFromLetter('U');
        $hasUser42 = false;
        foreach ($faqs as $faq) {
            if ($faq->question === 'user42 question') {
                $hasUser42 = true;
            }
        }

        $this->assertTrue($hasUser42, 'user42 question should be visible to user 42');
    }

    public function testSetGroups(): void
    {
        $this->configuration->set('security.permLevel', 'medium');
        $sitemap = new Sitemap($this->configuration);
        $sitemap->setUser(-1);
        $sitemap->setGroups([-1]);

        $letters = $sitemap->getAllFirstLetters();
        $this->assertNotEmpty($letters);
    }

    public function testGetAllFirstLettersBasic(): void
    {
        $sitemap = $this->createSitemapBasic();

        $letters = $sitemap->getAllFirstLetters();

        $this->assertIsArray($letters);
        $sLetters = array_filter($letters, fn($l) => $l->letter === 'S');
        $this->assertNotEmpty($sLetters);

        $sLetter = array_values($sLetters)[0];
        $this->assertEquals('S', $sLetter->letter);
        $this->assertEquals('https://example.com/sitemap/S/en.html', $sLetter->url);
    }

    public function testGetAllFirstLettersWithGroupSupport(): void
    {
        $sitemap = $this->createSitemap();

        $letters = $sitemap->getAllFirstLetters();

        $this->assertNotEmpty($letters);
        $sLetters = array_filter($letters, fn($l) => $l->letter === 'S');
        $this->assertNotEmpty($sLetters);
    }

    public function testGetAllFirstLettersReturnsEmptyWhenNoData(): void
    {
        $this->cleanupTestData();

        $sitemap = $this->createSitemapBasic();
        // Use a language with no FAQs to ensure empty results
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('xx');
        $this->configuration->setLanguage($language);

        $letters = $sitemap->getAllFirstLetters();

        $this->assertIsArray($letters);
        $this->assertEmpty($letters);
    }

    public function testGetFaqsFromLetterBasic(): void
    {
        $sitemap = $this->createSitemapBasic();

        $faqs = $sitemap->getFaqsFromLetter('S');

        $hasSampleQuestion = false;
        foreach ($faqs as $faq) {
            if ($faq->question === 'sample question') {
                $hasSampleQuestion = true;
                $this->assertStringContainsString(sprintf('content/%d/%d/en/', self::CAT_ID, self::FAQ_ID), $faq->url);
                $this->assertNotEmpty($faq->answer);
            }
        }

        $this->assertTrue($hasSampleQuestion, 'Expected to find "sample question" in results');
    }

    public function testGetFaqsFromLetterWithGroupSupport(): void
    {
        $sitemap = $this->createSitemap();

        $faqs = $sitemap->getFaqsFromLetter('S');

        $hasSampleQuestion = false;
        foreach ($faqs as $faq) {
            if ($faq->question === 'sample question') {
                $hasSampleQuestion = true;
            }
        }

        $this->assertTrue($hasSampleQuestion, 'Expected to find "sample question" in results');
    }

    public function testGetFaqsFromLetterWithMarkdownEnabled(): void
    {
        $this->configuration->set('main.enableMarkdownEditor', true);
        $this->insertTestFaq(self::FAQ_ID_2, 'markdown question', '**bold** and *italic*', self::CAT_ID);

        $sitemap = $this->createSitemap();
        $faqs = $sitemap->getFaqsFromLetter('M');

        $hasMarkdownQuestion = false;
        foreach ($faqs as $faq) {
            if ($faq->question === 'markdown question') {
                $hasMarkdownQuestion = true;
                $this->assertStringNotContainsString('**', $faq->answer);
            }
        }

        $this->assertTrue($hasMarkdownQuestion, 'Expected to find "markdown question" in results');
    }

    public function testGetFaqsFromLetterSkipsDuplicateIds(): void
    {
        $this->db->query(sprintf(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, 'en', %d, 'en')",
            self::CAT_ID_2,
            self::FAQ_ID,
        ));

        $sitemap = $this->createSitemap();
        $faqs = $sitemap->getFaqsFromLetter('S');

        $count = 0;
        foreach ($faqs as $faq) {
            if ($faq->question === 'sample question') {
                ++$count;
            }
        }

        $this->assertEquals(1, $count, 'Duplicate FAQ IDs should be skipped');
    }

    public function testGetFaqsFromLetterReturnsEmptyForNoMatch(): void
    {
        $sitemap = $this->createSitemapBasic();
        $sitemap->setUser(99999);

        $faqs = $sitemap->getFaqsFromLetter('Z');

        $this->assertIsArray($faqs);
        $this->assertEmpty($faqs);
    }

    public function testGetFaqsFromLetterDefaultsToA(): void
    {
        $sitemap = $this->createSitemapBasic();
        $sitemap->setUser(99999);

        $faqs = $sitemap->getFaqsFromLetter();

        $this->assertIsArray($faqs);
        $this->assertEmpty($faqs);
    }

    private function insertTestFaq(int $faqId, string $thema, string $content, int $categoryId): void
    {
        $this->db->query(sprintf(
            'INSERT INTO faqdata (id, lang, solution_id, sticky, thema, content, keywords, active, author, email, updated) '
            . "VALUES (%d, 'en', %d, 'yes', '%s', '%s', 'test keywords', 'yes', 'Author', 'test@example.org', 'date')",
            $faqId,
            $faqId + 1000,
            $thema,
            $content,
        ));
        $this->db->query(sprintf('INSERT INTO faqdata_group (record_id, group_id) VALUES (%d, -1)', $faqId));
        $this->db->query(sprintf('INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)', $faqId));
        $this->db->query(sprintf(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, 'en', %d, 'en')",
            $categoryId,
            $faqId,
        ));
    }

    private function cleanupTestData(): void
    {
        $ids = [self::FAQ_ID, self::FAQ_ID_2];
        $idList = implode(',', $ids);
        $catIds = [self::CAT_ID, self::CAT_ID_2];
        $catIdList = implode(',', $catIds);

        $this->db->query("DELETE FROM faqdata WHERE id IN ($idList)");
        $this->db->query("DELETE FROM faqdata_group WHERE record_id IN ($idList)");
        $this->db->query("DELETE FROM faqdata_user WHERE record_id IN ($idList)");
        $this->db->query(
            "DELETE FROM faqcategoryrelations WHERE record_id IN ($idList) AND category_id IN ($catIdList)",
        );
    }
}
