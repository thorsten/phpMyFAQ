<?php

namespace phpMyFAQ;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

class TranslationTest extends TestCase
{
    private Translation $translation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translation = Translation::create();
    }

    /**
     * @throws Exception
     */
    public function testSetLanguagesDir(): void
    {
        $this->assertEquals(
            $this->translation,
            $this->translation->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
        );
    }

    public function testSetLanguagesDirWithException(): void
    {
        $this->expectException(Exception::class);
        $this->assertEquals(
            $this->translation,
            $this->translation->setLanguagesDir(__DIR__ . '/foo/bar')
        );
    }

    /**
     * @throws Exception
     */
    public function testGet(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setCurrentLanguage('de');
        $this->assertEquals('deutsch', Translation::get(languageKey: 'language'));
    }

    public function testCreate(): void
    {
        $this->assertEquals(
            $this->translation,
            Translation::create()
        );
    }

    public function testGetInstance(): void
    {
        $this->assertEquals(
            Translation::create(),
            Translation::getInstance()
        );
    }

    /**
     * @throws Exception
     */
    public function testGetCurrentLanguage(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setCurrentLanguage('de');
        $this->assertEquals(
            'de',
            Translation::create()
                ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
                ->getCurrentLanguage()
        );
    }

    /**
     * @throws Exception
     */
    public function testSetDefaultLanguage(): void
    {
        Translation::create()
            ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
            ->setDefaultLanguage('fi');
        $this->assertEquals(
            'fi',
            Translation::create()
                ->setLanguagesDir(__DIR__ . '/../../phpmyfaq/translations')
                ->getDefaultLanguage()
        );
    }
}
