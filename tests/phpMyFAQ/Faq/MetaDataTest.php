<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission as CategoryPermission;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Faq\Permission as FaqPermission;
use phpMyFAQ\Language;
use phpMyFAQ\Visits;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(MetaData::class)]
#[UsesNamespace('phpMyFAQ')]
final class MetaDataTest extends TestCase
{
    private string $databaseFile;

    private Configuration $configuration;

    protected function setUp(): void
    {
        $this->databaseFile = tempnam(sys_get_temp_dir(), 'phpmyfaq-faq-metadata-');
        copy(PMF_TEST_DIR . '/test.db', $this->databaseFile);

        $dbHandle = new Sqlite3();
        $dbHandle->connect($this->databaseFile, '', '');

        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
        $this->configuration->set('security.permLevel', 'medium');

        $_SERVER['REQUEST_TIME'] = time();
    }

    protected function tearDown(): void
    {
        if (file_exists($this->databaseFile)) {
            @unlink($this->databaseFile);
        }
    }

    public function testSettersAreFluent(): void
    {
        $metaData = new MetaData($this->configuration);

        $this->assertSame($metaData, $metaData->setFaqId(1));
        $this->assertSame($metaData, $metaData->setFaqLanguage('en'));
        $this->assertSame($metaData, $metaData->setCategories([1]));
    }

    public function testSaveLinksCategoriesAndStartsVisitCounter(): void
    {
        new MetaData($this->configuration)
            ->setFaqId(10)
            ->setFaqLanguage('en')
            ->setCategories([1, 2])
            ->save();

        $relations = new Relation($this->configuration, new Category($this->configuration))->getCategories(10, 'en');
        $this->assertSame([1, 2], array_keys($relations));
        $this->assertSame('en', $relations[1]['category_lang']);

        $visits = array_values(array_filter(
            new Visits($this->configuration)->getAllData(),
            static fn(array $row): bool => (int) $row['id'] === 10 && $row['lang'] === 'en',
        ));
        $this->assertCount(1, $visits);
        $this->assertSame(1, (int) $visits[0]['visits']);
    }

    public function testSaveCopiesUserAndGroupPermissionsFromTheCategories(): void
    {
        $categoryPermission = new CategoryPermission($this->configuration);
        $categoryPermission->add(CategoryPermission::USER, [1], [5]);
        $categoryPermission->add(CategoryPermission::GROUP, [1], [7]);

        new MetaData($this->configuration)
            ->setFaqId(10)
            ->setFaqLanguage('en')
            ->setCategories([1, 2])
            ->save();

        $faqPermission = new FaqPermission($this->configuration);
        $this->assertSame([5], $faqPermission->get(FaqPermission::USER, 10));
        $this->assertSame([7], $faqPermission->get(FaqPermission::GROUP, 10));

        // The permissions are also propagated to every linked category, so all of them agree.
        $this->assertSame([5], $categoryPermission->get(CategoryPermission::USER, [2]));
        $this->assertSame([7], $categoryPermission->get(CategoryPermission::GROUP, [2]));
    }

    public function testSaveSkipsGroupPermissionsOnBasicPermissionLevel(): void
    {
        $this->configuration->set('security.permLevel', 'basic');

        $categoryPermission = new CategoryPermission($this->configuration);
        $categoryPermission->add(CategoryPermission::USER, [1], [5]);
        $categoryPermission->add(CategoryPermission::GROUP, [1], [7]);

        new MetaData($this->configuration)
            ->setFaqId(10)
            ->setFaqLanguage('en')
            ->setCategories([1])
            ->save();

        $faqPermission = new FaqPermission($this->configuration);
        $this->assertSame([5], $faqPermission->get(FaqPermission::USER, 10));
        $this->assertSame([], $faqPermission->get(FaqPermission::GROUP, 10));
    }

    public function testSavingTwiceDoesNotDuplicateRelationsOrPermissions(): void
    {
        new CategoryPermission($this->configuration)->add(CategoryPermission::USER, [1], [5]);

        $metaData = new MetaData($this->configuration)->setFaqId(10)->setFaqLanguage('en')->setCategories([1]);
        $metaData->save();
        $metaData->save();

        $this->assertCount(1, new Relation($this->configuration, new Category($this->configuration))->getCategories(10, 'en'));
        $this->assertSame([5], new FaqPermission($this->configuration)->get(FaqPermission::USER, 10));

        $visits = array_values(array_filter(
            new Visits($this->configuration)->getAllData(),
            static fn(array $row): bool => (int) $row['id'] === 10,
        ));
        $this->assertCount(1, $visits);
        $this->assertSame(2, (int) $visits[0]['visits']);
    }
}
