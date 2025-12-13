<?php

declare(strict_types=1);

namespace phpMyFAQ\Form; // Test namespace für direkte Klassenreferenz

use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FormsHelperTest extends TestCase
{
    private FormsHelper $helper;
    private Translation $translation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new FormsHelper();
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();
        $this->translation = new Translation();
    }

    public function testEmptyDataReturnsEmptyArray(): void
    {
        $result = $this->helper->filterAndSortFormData([], $this->translation);
        $this->assertSame([], $result);
    }

    public function testOnlyDefaultLanguageFallsBack(): void
    {
        $data = [(object) [
            'form_id' => 1,
            'input_id' => 2,
            'input_type' => 'text',
            'input_label' => 'msgNewContentName',
            'input_active' => 1,
            'input_required' => 1,
            'input_lang' => 'default',
        ]];
        $result = $this->helper->filterAndSortFormData($data, $this->translation);
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]->input_id);
    }

    public function testCurrentLanguagePreferredOverDefault(): void
    {
        $data = [
            (object) [
                'form_id' => 1,
                'input_id' => 5,
                'input_type' => 'text',
                'input_label' => 'Custom EN',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'en',
            ],
            (object) [
                'form_id' => 1,
                'input_id' => 5,
                'input_type' => 'text',
                'input_label' => 'msgNewContentName',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'default',
            ],
        ];
        $result = $this->helper->filterAndSortFormData($data, $this->translation);
        $this->assertCount(1, $result);
        $this->assertSame('en', $result[0]->input_lang);
        $this->assertSame('Custom EN', $result[0]->input_label);
    }

    public function testDefaultIncludedIfCurrentMissing(): void
    {
        $data = [
            (object) [
                'form_id' => 1,
                'input_id' => 10,
                'input_type' => 'text',
                'input_label' => 'msgNewContentName',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'default',
            ],
            (object) [
                'form_id' => 1,
                'input_id' => 11,
                'input_type' => 'text',
                'input_label' => 'ad_sess_pageviews',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'default',
            ],
        ];
        $result = $this->helper->filterAndSortFormData($data, $this->translation);
        $this->assertCount(2, $result);
        $this->assertSame(10, $result[0]->input_id);
        $this->assertSame(11, $result[1]->input_id);
    }

    public function testSortingAscending(): void
    {
        $data = [
            (object) [
                'form_id' => 1,
                'input_id' => 3,
                'input_type' => 'text',
                'input_label' => 'X',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'en',
            ],
            (object) [
                'form_id' => 1,
                'input_id' => 1,
                'input_type' => 'text',
                'input_label' => 'A',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'en',
            ],
            (object) [
                'form_id' => 1,
                'input_id' => 2,
                'input_type' => 'text',
                'input_label' => 'B',
                'input_active' => 1,
                'input_required' => 0,
                'input_lang' => 'en',
            ],
        ];
        $result = $this->helper->filterAndSortFormData($data, $this->translation);
        $this->assertSame([1, 2, 3], array_map(static fn($o) => $o->input_id, $result));
    }
}
