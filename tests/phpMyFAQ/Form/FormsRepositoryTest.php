<?php

/**
 * Forms Repository
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-11-04
 */

namespace phpMyFAQ\Form;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use phpMyFAQ\Language;

#[AllowMockObjectsWithoutExpectations]
class FormsRepositoryTest extends TestCase
{
    private Configuration $configuration;
    private FormsRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($dbHandle);
        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);

        $this->repository = new FormsRepository($this->configuration);

        // Seed minimal form row in default language if not exists (portable SQL)
        $prefix = Database::getTablePrefix();
        $check = $this->configuration->getDb()->query(
            "SELECT COUNT(*) AS cnt FROM {$prefix}faqforms WHERE form_id = 1 AND input_id = 1 AND input_lang = 'default'"
        );
        $countObj = $this->configuration->getDb()->fetchObject($check);
        $count = $countObj ? (int) $countObj->cnt : 0;
        if ($count === 0) {
            $this->configuration->getDb()->query(
                "INSERT INTO {$prefix}faqforms (form_id, input_id, input_type, input_label, input_lang, input_active, input_required)"
                . " VALUES (1, 1, 'text', 'msgContactName', 'default', 1, 1)"
            );
        }
    }

    public function testFetchFormDataAndTranslationsAndUpdates(): void
    {
        $rows = $this->repository->fetchFormDataByFormId(1);
        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        // Add a translation for 'en'
        $default = $this->repository->fetchDefaultInputData(1, 1);
        $this->assertNotNull($default);
        $this->assertTrue($this->repository->insertTranslationRow(1, 1, $default->input_type, 'Name', (int) $default->input_active, (int) $default->input_required, 'en'));

        $translations = $this->repository->fetchTranslationsByFormAndInput(1, 1);
        $this->assertNotEmpty($translations);

        // Update translation
        $this->assertTrue($this->repository->updateTranslation('Full Name', 1, 1, 'en'));
        $translations = $this->repository->fetchTranslationsByFormAndInput(1, 1);
        $found = false;
        foreach ($translations as $t) {
            if ($t->input_lang === 'en') {
                $this->assertSame('Full Name', $t->input_label);
                $found = true;
            }
        }
        $this->assertTrue($found);

        // Update active/required
        $this->assertTrue($this->repository->updateInputActive(1, 1, 0));
        $this->assertTrue($this->repository->updateInputRequired(1, 1, 0));

        // Delete translation
        $this->assertTrue($this->repository->deleteTranslation(1, 1, 'en'));
    }

    public function testInsertInputAndBuildQuery(): void
    {
        $input = [
            'form_id' => 2,
            'input_id' => 1,
            'input_type' => 'email',
            'input_label' => 'Email',
            'input_lang' => 'default',
            'input_active' => 1,
            'input_required' => 1,
        ];

        $query = $this->repository->buildInsertQuery($input);
        $this->assertIsString($query);
        $this->assertStringContainsString('INSERT INTO', $query);

        $this->assertTrue($this->repository->insertInput($input));

        $rows = $this->repository->fetchFormDataByFormId(2);
        $this->assertNotEmpty($rows);
        $this->assertSame(1, (int) $rows[0]->input_id);
    }
}
