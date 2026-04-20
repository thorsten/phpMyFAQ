<?php

/**
 * Seeds phpMyFAQ with bilingual (DE + EN) test data about phpMyFAQ itself.
 *
 * All rows created by this command carry a unique author/email marker so that
 * they can be safely removed again with the --fresh option without touching
 * any real content.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-04-19
 */

declare(strict_types=1);

namespace phpMyFAQ\Command;

use DateTime;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Entity\NewsMessage;
use phpMyFAQ\Faq;
use phpMyFAQ\Glossary;
use phpMyFAQ\News;
use phpMyFAQ\Tags;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'phpmyfaq:seed-testdata',
    description: 'Seeds phpMyFAQ with bilingual (DE + EN) test data about phpMyFAQ itself',
)]
class SeedTestDataCommand extends Command
{
    public const string AUTHOR = 'phpMyFAQ Test Seeder';

    public const string EMAIL = 'test-seeder@phpmyfaq.local';

    private const string FIXTURE_DIR = __DIR__ . '/Fixtures/testdata';

    private Configuration $configuration;

    public function __construct()
    {
        parent::__construct();
        $this->configuration = Configuration::getConfigurationInstance();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'locale',
            shortcut: null,
            mode: InputOption::VALUE_OPTIONAL,
            description: 'Comma-separated list of locales to seed (default: de,en)',
            default: 'de,en',
        )->addOption(
            name: 'fresh',
            shortcut: null,
            mode: InputOption::VALUE_NONE,
            description: 'Remove previously seeded test data before inserting new rows',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('phpMyFAQ Test Data Seeder');

        /** @var string $localeOption */
        $localeOption = $input->getOption('locale');
        $locales = array_values(array_filter(array_map('trim', explode(',', $localeOption))));
        if ($locales === []) {
            $io->error('No valid locales provided.');
            return Command::FAILURE;
        }

        $supported = ['de', 'en'];
        foreach ($locales as $locale) {
            if (in_array($locale, $supported, strict: true)) {
                continue;
            }
            $io->error(sprintf('Locale "%s" is not supported. Available: %s', $locale, implode(', ', $supported)));
            return Command::FAILURE;
        }

        try {
            if ($input->getOption('fresh')) {
                $removed = $this->purge();
                $io->note(sprintf('Removed %d previously seeded rows.', $removed));
            }

            $categories = $this->loadFixture('categories.json');
            $faqs = $this->loadFixture('faqs.json');
            $glossary = $this->loadFixture('glossary.json');
            $news = $this->loadFixture('news.json');

            $categoryIdMap = $this->seedCategories($categories, $locales);
            $io->writeln(sprintf(' <info>✓</info> %d categories seeded', count($categoryIdMap)));

            $faqCount = $this->seedFaqs($faqs, $categoryIdMap, $locales);
            $io->writeln(sprintf(' <info>✓</info> %d FAQs seeded', $faqCount));

            $glossaryCount = $this->seedGlossary($glossary, $locales);
            $io->writeln(sprintf(' <info>✓</info> %d glossary entries seeded', $glossaryCount));

            $newsCount = $this->seedNews($news, $locales);
            $io->writeln(sprintf(' <info>✓</info> %d news entries seeded', $newsCount));

            $io->success(sprintf('Test data seeded successfully for locales: %s', implode(', ', $locales)));

            return Command::SUCCESS;
        } catch (Throwable $throwable) {
            $io->error('Seeding failed: ' . $throwable->getMessage());
            if ($output->isVerbose()) {
                $io->writeln($throwable->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadFixture(string $filename): array
    {
        $path = self::FIXTURE_DIR . '/' . $filename;
        if (!is_file($path)) {
            throw new \RuntimeException(sprintf('Fixture file not found: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new \RuntimeException(sprintf('Unable to read fixture file: %s', $path));
        }

        $data = json_decode($raw, associative: true, depth: 16, flags: JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException(sprintf('Fixture file "%s" does not contain a JSON array.', $filename));
        }

        /** @var array<int, array<string, mixed>> $data */
        return $data;
    }

    /**
     * @param array<int, array<string, mixed>> $categories
     * @param array<int, string>               $locales
     *
     * @return array<string, int> map of slug -> shared category id
     */
    private function seedCategories(array $categories, array $locales): array
    {
        $category = new Category($this->configuration);
        $slugToId = [];

        foreach ($categories as $definition) {
            $slug = (string) $definition['slug'];
            $parentSlug = $definition['parent'] ?? null;
            $parentId = is_string($parentSlug) ? $slugToId[$parentSlug] ?? 0 : 0;

            $sharedId = 0;
            $primaryLocale = in_array('en', $locales, strict: true) ? 'en' : $locales[0];
            $orderedLocales = array_merge(
                [$primaryLocale],
                array_values(array_filter($locales, static fn(string $l): bool => $l !== $primaryLocale)),
            );

            foreach ($orderedLocales as $locale) {
                $translation = $definition['translations'][$locale] ?? null;
                if (!is_array($translation)) {
                    continue;
                }

                $entity = new CategoryEntity();
                $entity->setLang($locale);
                $entity->setParentId($parentId);
                $entity->setName((string) $translation['name']);
                $entity->setDescription((string) ($translation['description'] ?? ''));
                $entity->setUserId(1);
                $entity->setGroupId(-1);
                $entity->setActive(true);
                $entity->setShowHome(false);
                $entity->setImage('');

                if ($sharedId > 0) {
                    $entity->setId($sharedId);
                }

                $newId = $category->create($entity);

                if ($sharedId === 0 && is_int($newId) && $newId > 0) {
                    $sharedId = $newId;
                }
            }

            if ($sharedId > 0) {
                $slugToId[$slug] = $sharedId;
            }
        }

        return $slugToId;
    }

    /**
     * @param array<int, array<string, mixed>> $faqs
     * @param array<string, int>               $categoryIdMap
     * @param array<int, string>               $locales
     */
    private function seedFaqs(array $faqs, array $categoryIdMap, array $locales): int
    {
        $faqService = new Faq($this->configuration);
        $tagsService = new Tags($this->configuration);
        $inserted = 0;

        foreach ($faqs as $definition) {
            $categorySlug = (string) $definition['category'];
            $categoryId = $categoryIdMap[$categorySlug] ?? null;
            if ($categoryId === null) {
                continue;
            }
            /** @var array<int, string> $tags */
            $tags = $definition['tags'] ?? [];

            $sharedFaqId = 0;
            foreach ($locales as $locale) {
                $translation = $definition['translations'][$locale] ?? null;
                if (!is_array($translation)) {
                    continue;
                }

                $entity = new FaqEntity();
                if ($sharedFaqId > 0) {
                    $entity->setId($sharedFaqId);
                }
                $entity->setLanguage($locale);
                $entity->setActive(true);
                $entity->setSticky(false);
                $entity->setComment(true);
                $entity->setQuestion((string) $translation['question']);
                $entity->setAnswer((string) $translation['answer']);
                $entity->setKeywords((string) ($translation['keywords'] ?? ''));
                $entity->setAuthor(self::AUTHOR);
                $entity->setEmail(self::EMAIL);
                $entity->setNotes('');
                $entity->setCreatedDate(new DateTime());

                $saved = $faqService->create($entity);
                $faqId = $saved->getId();
                if (!is_int($faqId) || $faqId <= 0) {
                    continue;
                }

                if ($sharedFaqId === 0) {
                    $sharedFaqId = $faqId;
                }

                $this->linkFaqToCategory($faqId, $locale, $categoryId);

                if ($tags !== []) {
                    $tagsService->create($faqId, $tags);
                }

                $inserted++;
            }
        }

        return $inserted;
    }

    private function linkFaqToCategory(int $faqId, string $language, int $categoryId): void
    {
        $query = sprintf(
            "INSERT INTO %sfaqcategoryrelations (category_id, category_lang, record_id, record_lang) VALUES (%d, '%s', %d, '%s')",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($language),
            $faqId,
            $this->configuration->getDb()->escape($language),
        );

        $this->configuration->getDb()->query($query);
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<int, string>               $locales
     */
    private function seedGlossary(array $entries, array $locales): int
    {
        $inserted = 0;
        foreach ($entries as $entry) {
            foreach ($locales as $locale) {
                $translation = $entry['translations'][$locale] ?? null;
                if (!is_array($translation)) {
                    continue;
                }

                $glossary = new Glossary($this->configuration);
                $glossary->setLanguage($locale);

                if ($glossary->create((string) $translation['item'], (string) $translation['definition'])) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    /**
     * @param array<int, array<string, mixed>> $entries
     * @param array<int, string>               $locales
     */
    private function seedNews(array $entries, array $locales): int
    {
        $newsService = new News($this->configuration);
        $inserted = 0;

        foreach ($entries as $entry) {
            foreach ($locales as $locale) {
                $translation = $entry['translations'][$locale] ?? null;
                if (!is_array($translation)) {
                    continue;
                }

                $message = new NewsMessage();
                $message->setLanguage($locale);
                $message->setHeader((string) $translation['header']);
                $message->setMessage((string) $translation['message']);
                $message->setAuthor(self::AUTHOR);
                $message->setEmail(self::EMAIL);
                $message->setActive(true);
                $message->setComment(false);
                $message->setCreated(new DateTime());
                $message->setDateStart(new DateTime());
                $message->setDateEnd(new DateTime('9999-12-31 23:59:59'));
                $message->setLink('');
                $message->setLinkTitle('');
                $message->setLinkTarget('');

                if ($newsService->create($message)) {
                    $inserted++;
                }
            }
        }

        return $inserted;
    }

    /**
     * Removes all rows previously inserted by this seeder, identified by the
     * author/email marker. Returns the total number of affected rows.
     */
    private function purge(): int
    {
        $db = $this->configuration->getDb();
        $prefix = Database::getTablePrefix();
        $author = $db->escape(self::AUTHOR);
        $email = $db->escape(self::EMAIL);

        $removed = 0;

        // Collect FAQ ids so related rows (tags, relations, votes, comments) can be cleaned up.
        // Note: we fetch everything up-front to stay portable across drivers whose
        // fetchArray() implementations signal "no more rows" differently (empty array vs null vs false).
        $faqIds = [];
        $faqResult = $db->query(sprintf(
            "SELECT id, lang FROM %sfaqdata WHERE author = '%s' AND email = '%s'",
            $prefix,
            $author,
            $email,
        ));
        $rows = $db->fetchAll($faqResult) ?? [];
        foreach ($rows as $row) {
            // Drivers return either array rows or stdClass rows — normalize both shapes.
            $id = is_array($row) ? $row['id'] ?? null : $row->id ?? null;
            $lang = is_array($row) ? $row['lang'] ?? null : $row->lang ?? null;
            if ($id === null || $lang === null) {
                continue;
            }
            $faqIds[] = ['id' => (int) $id, 'lang' => (string) $lang];
        }

        foreach ($faqIds as $faq) {
            $db->query(sprintf(
                "DELETE FROM %sfaqcategoryrelations WHERE record_id = %d AND record_lang = '%s'",
                $prefix,
                $faq['id'],
                $db->escape($faq['lang']),
            ));
            $db->query(sprintf('DELETE FROM %sfaqdata_tags WHERE record_id = %d', $prefix, $faq['id']));
            $db->query(sprintf(
                "DELETE FROM %sfaqchanges WHERE beitrag = %d AND lang = '%s'",
                $prefix,
                $faq['id'],
                $db->escape($faq['lang']),
            ));
            $db->query(sprintf(
                "DELETE FROM %sfaqvisits WHERE id = %d AND lang = '%s'",
                $prefix,
                $faq['id'],
                $db->escape($faq['lang']),
            ));
        }

        $deletes = [
            sprintf("DELETE FROM %sfaqdata WHERE author = '%s' AND email = '%s'", $prefix, $author, $email),
            sprintf("DELETE FROM %sfaqnews WHERE author_name = '%s' AND author_email = '%s'", $prefix, $author, $email),
        ];

        foreach ($deletes as $query) {
            $result = $db->query($query);
            $removed += (int) $db->affectedRows($result);
        }

        // Categories and glossary entries have no author field; detect them via the fixture definitions.
        $categoryDefs = $this->loadFixture('categories.json');
        foreach ($categoryDefs as $definition) {
            foreach ($definition['translations'] ?? [] as $lang => $translation) {
                $name = $db->escape((string) $translation['name']);
                $result = $db->query(sprintf(
                    "DELETE FROM %sfaqcategories WHERE name = '%s' AND lang = '%s'",
                    $prefix,
                    $name,
                    $db->escape((string) $lang),
                ));
                $removed += (int) $db->affectedRows($result);
            }
        }

        $glossaryDefs = $this->loadFixture('glossary.json');
        foreach ($glossaryDefs as $entry) {
            foreach ($entry['translations'] ?? [] as $lang => $translation) {
                $item = $db->escape((string) $translation['item']);
                $result = $db->query(sprintf(
                    "DELETE FROM %sfaqglossary WHERE item = '%s' AND lang = '%s'",
                    $prefix,
                    $item,
                    $db->escape((string) $lang),
                ));
                $removed += (int) $db->affectedRows($result);
            }
        }

        // Prune orphaned tag definitions (tagging_name no longer referenced by any record).
        $db->query(sprintf(
            'DELETE FROM %sfaqtags WHERE tagging_id NOT IN (SELECT tagging_id FROM %sfaqdata_tags)',
            $prefix,
            $prefix,
        ));

        return $removed;
    }
}
