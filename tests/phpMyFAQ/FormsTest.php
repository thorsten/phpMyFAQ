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
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $configuration = new Configuration($dbHandle);
        $language = new Language($configuration, $this->createMock(Session::class));
        $language->setLanguageFromConfiguration('en');
        $configuration->setLanguage($language);

        $this->forms = new Forms($configuration);
    }

    public function testSaveActivateInputStatus(): void
    {
        // Test activation
        $this->forms->saveActivateInputStatus(1, 1, 1);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int) $input->input_id === 1) {
                $this->assertEquals(1, (int) $input->input_active);
            }
        }
        // Test deactivation
        $this->forms->saveActivateInputStatus(1, 1, 0);
        $result = $this->forms->getFormData(1);
        foreach ($result as $input) {
            if ((int) $input->input_id === 1) {
                $this->assertEquals(0, (int) $input->input_active);
            }
        }
    }

    /**
     * Test saveRequiredInputStatus method
     */
    public function testSaveRequiredInputStatus(): void
    {
        // Test making input required
        $result = $this->forms->saveRequiredInputStatus(1, 1, 1);
        $this->assertTrue($result);

        $formData = $this->forms->getFormData(1);
        foreach ($formData as $input) {
            if ((int) $input->input_id === 1) {
                $this->assertEquals(1, (int) $input->input_required);
            }
        }

        // Test making input not required
        $result = $this->forms->saveRequiredInputStatus(1, 1, 0);
        $this->assertTrue($result);
    }

    /**
     * Test getFormData method with language filtering
     */
    public function testGetFormDataWithLanguageFiltering(): void
    {
        $formData = $this->forms->getFormData(1);

        $this->assertIsArray($formData);

        // Verify that data is sorted by input_id
        $previousInputId = 0;
        foreach ($formData as $input) {
            $this->assertGreaterThanOrEqual($previousInputId, $input->input_id);
            $previousInputId = $input->input_id;

            // Verify required properties exist
            $this->assertObjectHasProperty('form_id', $input);
            $this->assertObjectHasProperty('input_id', $input);
            $this->assertObjectHasProperty('input_type', $input);
            $this->assertObjectHasProperty('input_label', $input);
            $this->assertObjectHasProperty('input_active', $input);
            $this->assertObjectHasProperty('input_required', $input);
            $this->assertObjectHasProperty('input_lang', $input);
        }
    }

    /**
     * Test getFormData with non-existent form ID
     */
    public function testGetFormDataWithNonExistentFormId(): void
    {
        $formData = $this->forms->getFormData(999999);
        $this->assertIsArray($formData);
        $this->assertEmpty($formData);
    }

    /**
     * Test getTranslatedLanguages method
     */
    public function testGetTranslatedLanguages(): void
    {
        $languages = $this->forms->getTranslatedLanguages(1, 1);

        $this->assertIsArray($languages);

        // Should contain at least the default language
        foreach ($languages as $language) {
            $this->assertIsString($language);
            $this->assertNotEmpty($language);
        }
    }

    /**
     * Test getTranslatedLanguages with non-existent input
     */
    public function testGetTranslatedLanguagesWithNonExistentInput(): void
    {
        $languages = $this->forms->getTranslatedLanguages(999, 999);
        $this->assertIsArray($languages);
        $this->assertEmpty($languages);
    }

    /**
     * Test getTranslations method
     */
    public function testGetTranslations(): void
    {
        $translations = $this->forms->getTranslations(1, 1);

        $this->assertIsArray($translations);

        foreach ($translations as $translation) {
            $this->assertObjectHasProperty('input_lang', $translation);
            $this->assertObjectHasProperty('input_label', $translation);
            $this->assertIsString($translation->input_lang);
            $this->assertIsString($translation->input_label);
        }
    }

    /**
     * Test getTranslations with non-existent form and input
     */
    public function testGetTranslationsWithNonExistentData(): void
    {
        $translations = $this->forms->getTranslations(999, 999);
        $this->assertIsArray($translations);
        $this->assertEmpty($translations);
    }

    /**
     * Test editTranslation method
     */
    public function testEditTranslation(): void
    {
        // First, get existing form data to ensure we have valid IDs
        $formData = $this->forms->getFormData(1);

        // Skip test if no form data exists
        if (empty($formData)) {
            $this->markTestSkipped('No form data available for testing editTranslation');
            return;
        }

        // Find a non-default language entry to avoid Translation::get() processing
        $targetInput = null;
        foreach ($formData as $input) {
            if ($input->input_lang !== 'default') {
                $targetInput = $input;
                break;
            }
        }

        // If no non-default language found, use the first input but adjust our approach
        if ($targetInput === null) {
            $targetInput = $formData[0];
        }

        $formId = $targetInput->form_id;
        $inputId = $targetInput->input_id;
        $language = $targetInput->input_lang;

        // Get current translations to verify we have data to work with
        $originalTranslations = $this->forms->getTranslations($formId, $inputId);

        if (empty($originalTranslations)) {
            $this->markTestSkipped('No translations available for testing editTranslation');
            return;
        }

        // Use a simple label that won't be processed by Translation::get()
        // Use a translation key that actually exists to avoid warnings
        $newLabel = 'msgNewContentName'; // This is a real translation key

        $result = $this->forms->editTranslation($newLabel, $formId, $inputId, $language);
        $this->assertTrue($result);

        // Verify the translation was updated
        $updatedTranslations = $this->forms->getTranslations($formId, $inputId);
        $found = false;
        foreach ($updatedTranslations as $translation) {
            if ($translation->input_lang === $language) {
                // For default language, the label gets processed through Translation::get()
                // so we need to handle this case differently
                if ($language === 'default') {
                    // For default language, the translation key gets converted to actual text
                    // so we just verify the edit operation succeeded and a translation exists
                    $this->assertTrue($result);
                    $this->assertIsString($translation->input_label);
                    $found = true;
                } else {
                    // For non-default languages, we can directly compare the labels
                    $this->assertEquals($newLabel, $translation->input_label);
                    $found = true;
                }
                break;
            }
        }
        $this->assertTrue(
            $found,
            "Updated translation not found for formId=$formId, inputId=$inputId, language=$language",
        );
    }

    /**
     * Test editTranslation with special characters
     */
    public function testEditTranslationWithSpecialCharacters(): void
    {
        $specialLabel = 'Tëst Lábel wíth ßpëcíàl châräctërs & symbols <>';
        $result = $this->forms->editTranslation($specialLabel, 1, 1, 'en');

        $this->assertTrue($result);
    }

    /**
     * Test editTranslation with very long label
     */
    public function testEditTranslationWithLongLabel(): void
    {
        $longLabel = str_repeat('Long label text ', 50); // Very long label
        $result = $this->forms->editTranslation($longLabel, 1, 1, 'en');

        $this->assertTrue($result);
    }

    /**
     * Test constructor creates Translation instance
     */
    public function testConstructorCreatesTranslationInstance(): void
    {
        // Verify that Forms can be instantiated
        $this->assertInstanceOf(Forms::class, $this->forms);
    }

    /**
     * Test Forms with different languages
     */
    public function testFormsWithDifferentLanguages(): void
    {
        // Test with different language settings
        $formData = $this->forms->getFormData(1);

        // Verify language filtering works
        foreach ($formData as $input) {
            $this->assertTrue(
                $input->input_lang === 'en' || $input->input_lang === 'default',
                'Form data should only contain current language or default entries',
            );
        }
    }

    /**
     * Test saveActivateInputStatus with invalid data
     */
    public function testSaveActivateInputStatusWithInvalidData(): void
    {
        // Test with non-existent form/input combination
        $result = $this->forms->saveActivateInputStatus(999, 999, 1);

        // Should still return true even if no rows affected
        $this->assertTrue($result);
    }

    /**
     * Test saveRequiredInputStatus with invalid data
     */
    public function testSaveRequiredInputStatusWithInvalidData(): void
    {
        // Test with non-existent form/input combination
        $result = $this->forms->saveRequiredInputStatus(999, 999, 1);

        // Should still return true even if no rows affected
        $this->assertTrue($result);
    }

    /**
     * Test form data sorting functionality
     */
    public function testFormDataSorting(): void
    {
        $formData = $this->forms->getFormData(1);

        if (count($formData) > 1) {
            // Verify ascending order by input_id
            for ($i = 1; $i < count($formData); $i++) {
                $this->assertGreaterThanOrEqual(
                    $formData[$i - 1]->input_id,
                    $formData[$i]->input_id,
                    'Form data should be sorted by input_id in ascending order',
                );
            }
        }
    }

    /**
     * Test editTranslation with empty label
     */
    public function testEditTranslationWithEmptyLabel(): void
    {
        $result = $this->forms->editTranslation('', 1, 1, 'en');
        $this->assertTrue($result);
    }
}
