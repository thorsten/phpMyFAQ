<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;

class FormsTest extends TestCase
{
    private Forms $forms;

    /**
     * @throws Exception
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Translation::create()
            ->setLanguagesDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguage(false, 'en');
        $configuration->setLanguage($language);

        $this->forms = new Forms($configuration);
    }

    public function testSaveActivateInputStatus(): void
    {
        // Test activation
        $this->forms->saveActivateInputStatus(1, 1, 1);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int)$input->input_id === 1) {
                $this->assertEquals(1, (int)$input->input_active);
            }
        }
        // Test deactivation
        $this->forms->saveActivateInputStatus(1, 1, 0);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int)$input->input_id === 1) {
                $this->assertEquals(0, (int)$input->input_active);
            }
        }
    }

    public function testSaveRequiredInputStatus(): void
    {
        // Test set requirement to true
        $this->forms->saveRequiredInputStatus(1, 1, 1);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int)$input->input_id === 1) {
                $this->assertEquals(1, (int)$input->input_required);
            }
        }
        // Test set requirement to false
        $this->forms->saveRequiredInputStatus(1, 1, 0);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int)$input->input_id === 1) {
                $this->assertEquals(0, (int)$input->input_required);
            }
        }
    }

    public function testInsertInputIntoDatabase(): void
    {
        $input = [
            'form_id' => 3,
            'input_id' => 1,
            'input_type' => 'text',
            'input_lang' => 'default',
            'input_label' => 'msgEditForms',
            'input_active' => 1,
            'input_required' => 1
        ];
        $this->forms->insertInputIntoDatabase($input);
        $result = $this->forms->getFormData(3);
        $this->assertEquals(3, (int)$result[0]->form_id);
        $this->assertEquals(1, (int)$result[0]->input_id);
        $this->assertEquals('text', $result[0]->input_type);
        $this->assertEquals('default', $result[0]->input_lang);
        $this->assertEquals(1, (int)$result[0]->input_active);
        $this->assertEquals(1, (int)$result[0]->input_required);
    }

    public function testGetFormData(): void
    {
        $result = $this->forms->getFormData(1);
        $this->assertCount(6, $result);
    }

    public function testGetTranslatedLanguages(): void
    {
        $result = $this->forms->getTranslatedLanguages(2, 1);
        $this->assertCount(1, $result);
        $this->assertEquals('default', $result[0]);
    }

    public function testGetTranslations(): void
    {
        $result = $this->forms->getTranslations(1, 1);
        $this->assertEquals('Question', $result[0]->input_label);
        $this->assertEquals('default', $result[0]->input_lang);
    }

    public function testAddTranslation(): void
    {
        $this->forms->addTranslation(1, 1, 'German', 'Test');
        $translations = $this->forms->getTranslations(1, 1);
        foreach ($translations as $translation) {
            if ($translation->input_lang === 'de') {
                $this->assertEquals('Test', $translation->input_label);
            }
        }
    }

    public function testEditTranslation(): void
    {
        $this->forms->addTranslation(1, 2, 'German', 'Test');
        $this->forms->editTranslation('TestAdjusted', 1, 2, 'de');
        $translations = $this->forms->getTranslations(1, 2);
        foreach ($translations as $translation) {
            if ($translation->input_lang === 'de') {
                $this->assertEquals('TestAdjusted', $translation->input_label);
            }
        }
    }

    public function testDeleteTranslation(): void
    {
        $this->forms->addTranslation(1, 3, 'German', 'Test');
        $translations = $this->forms->getTranslations(1, 3);
        foreach ($translations as $translation) {
            if ($translation->input_lang === 'de') {
                $this->assertEquals('Test', $translation->input_label);
            }
        }
        $this->forms->deleteTranslation(1, 3, 'de');
        $translations = $this->forms->getTranslations(1, 3);
        $this->assertCount(1, $translations);
    }

    public function testCheckIfRequired(): void
    {
        $result = $this->forms->checkIfRequired(2, 1);
        $this->assertTrue($result);
    }
}
